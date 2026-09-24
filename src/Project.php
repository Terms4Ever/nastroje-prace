<?php
declare(strict_types=1);

namespace NastrojePrace;

final class Project
{
    public const REPOSITORY = 'Terms4Ever/nastroje-prace';
    public const DIRECTORY = '.nastroje-prace';
    public const ENTRYPOINTS = ['prace.php', 'prace.ps1', 'hooky/commit-msg', 'hooky/pre-push'];

    public static function runtimeFile(string $path): bool
    {
        return in_array($path, ['prace.php', 'prace.ps1', 'upstream.lock.json', 'pravidla/projekt.md',
            'scripts/ci.php', 'scripts/pre-push.php', 'scripts/install-php.ps1', 'hooky/commit-msg', 'hooky/pre-push'], true)
            || (bool) preg_match('~^src/[A-Za-z]+\.php$~D', $path);
    }

    /** Exportuje jen verzované soubory přesného čistého commitu, bez historie a místní cache. */
    public static function initialize(array $spec, string $destination, string $source = TOOL_ROOT): array
    {
        $source = realpath($source);
        ensure($source !== false && realpath(git($source, 'rev-parse', '--show-toplevel')) === $source, 'Zdroj musí být kořen repozitáře nástrojů.');
        ensure(config($source)['repository'] === self::REPOSITORY && !is_file($source . '/nastroje-prace.lock.json'), 'Export vyžaduje centrální nastroje-prace.');
        clean($source);
        $sha = resolveCommit($source, 'HEAD');
        ensure((bool) preg_match('~^(?:[A-Za-z]:[\\\\/]|/)~', $destination), 'Cíl musí být absolutní cesta.');
        $target = realpath($destination);
        ensure($target !== false && is_dir($target) && !is_link($destination), 'Připrav existující prázdnou cílovou složku.');
        ensure(count(scandir($target)) === 2, 'Cílová složka musí být prázdná; existující soubory se nepřepisují.');
        for ($parent = $target; ; $parent = dirname($parent)) {
            ensure(!file_exists($parent . '/.git') && !file_exists($parent . '/.svn'), 'Cíl nesmí ležet uvnitř existujícího Git nebo SVN projektu.');
            if ($parent === dirname($parent)) { break; }
        }
        foreach (['name', 'description', 'stack'] as $key) {
            meaningful($spec[$key] ?? null, 'Nastavení projektu ' . $key, $key === 'name' ? 3 : 12);
            ensure(!preg_match('/[\r\n{}]/u', $spec[$key]), 'Nastavení ' . $key . ' musí být jeden řádek bez zástupných značek.');
            noLongDash($spec[$key], 'Nastavení ' . $key);
        }
        $settings = validateConfig(['version' => 1, 'repository' => $spec['repository'] ?? '', 'topics' => $spec['topics'] ?? [],
            'tests' => $spec['tests'] ?? [], 'svn' => ['enabled' => false, 'url' => '', 'allow' => []]]);
        ensure($settings['repository'] !== self::REPOSITORY, 'Aplikační projekt musí mít vlastní název repozitáře.');
        ensure(is_array($settings['topics']) && array_is_list($settings['topics']) && count($settings['topics']) > 0 && count($settings['topics']) <= 20, 'Projekt vyžaduje seznam GitHub topics.');
        foreach ($settings['topics'] as $topic) {
            ensure(is_string($topic) && (bool) preg_match('/^[a-z0-9][a-z0-9-]{0,49}$/D', $topic), 'Neplatné GitHub topic.');
        }
        $settings['topics'] = array_values(array_unique([...$settings['topics'], RuleSet::TOPIC]));
        ensure(count($settings['topics']) <= 20, 'Pro pracovní topic musí zbýt místo v limitu GitHub topics.');
        RuleSet::topics($settings['topics']);
        // Validaci i načtení dokončíme před prvním zápisem do cíle.
        $output = $hashes = [];
        $tokens = ['{{NAME}}' => $spec['name'], '{{DESCRIPTION}}' => $spec['description'],
            '{{STACK}}' => $spec['stack'], '{{REPOSITORY}}' => $settings['repository']];
        foreach (tracked($source) as $file) {
            if (self::runtimeFile($file)) {
                $data = run(['git', '-C', $source, 'show', $sha . ':' . $file])['stdout'];
                $output[self::DIRECTORY . '/' . $file] = $data;
                $hashes[$file] = digest($data);
            } elseif (str_starts_with($file, 'sablony/project/') && str_ends_with($file, '.tpl')) {
                $path = substr($file, strlen('sablony/project/'), -4);
                $output[$path] = strtr(run(['git', '-C', $source, 'show', $sha . ':' . $file])['stdout'], $tokens);
            }
        }
        foreach (['src/load.php', 'src/Project.php', 'pravidla/projekt.md'] as $file) {
            ensure(isset($hashes[$file]), 'Zdrojový commit neobsahuje úplnou verzi pro napojení projektu.');
        }
        $entries = [];
        foreach (self::ENTRYPOINTS as $file) {
            ensure(isset($output[$file]), 'Chybí šablona vstupního bodu.');
            $entries[$file] = digest($output[$file]);
        }
        foreach (['.pravidla.json', 'AGENTS.md', 'README.md', '.github/workflows/kontroly.yml', '.github/workflows/issues.yml'] as $file) {
            ensure(isset($output[$file]), 'Chybí šablona projektu: ' . $file);
        }
        ksort($hashes);
        $output['.prace.json'] = json($settings);
        $output['nastroje-prace.lock.json'] = json(['version' => 1, 'repository' => self::REPOSITORY,
            'commit' => $sha, 'directory' => self::DIRECTORY, 'files' => $hashes, 'entrypoints' => $entries]);
        foreach ($output as $path => $data) { writeFile(safePath($target, $path), $data); }
        foreach (['hooky/commit-msg', 'hooky/pre-push'] as $file) {
            ensure(chmod($target . '/' . $file, 0755), 'Nelze nastavit spustitelný hook.');
        }
        return ['directory' => $target, 'repository' => $settings['repository'], 'commit' => $sha,
            'files' => count($output), 'ready' => false, 'next' => 'docs/05-napojeni-pravidel.md'];
    }

    public static function verifyIfBound(string $root): void
    {
        if (is_file($root . '/nastroje-prace.lock.json') || basename(realpath(TOOL_ROOT)) === self::DIRECTORY) {
            self::binding($root);
        }
    }

    public static function binding(string $root): array
    {
        $runtime = safePath($root, self::DIRECTORY);
        ensure(realpath($runtime) !== false && realpath($runtime) === realpath(TOOL_ROOT), 'Projekt musí spouštět vlastní připnutou kopii nástrojů.');
        $lock = readJson($root . '/nastroje-prace.lock.json');
        ensure(($lock['version'] ?? null) === 1 && ($lock['repository'] ?? '') === self::REPOSITORY
            && ($lock['directory'] ?? '') === self::DIRECTORY && preg_match('/^[a-f0-9]{40}$/D', $lock['commit'] ?? ''), 'Neplatné připnutí nástrojů.');
        ensure(is_array($lock['files'] ?? null) && isset($lock['files']['src/load.php'], $lock['files']['src/Project.php'], $lock['files']['pravidla/projekt.md']), 'Neúplný seznam souborů připnuté kopie.');
        foreach ($lock['files'] as $path => $hash) {
            ensure(self::runtimeFile($path) && is_string($hash) && preg_match('/^[a-f0-9]{64}$/D', $hash), 'Neplatný soubor v připnutí nástrojů.');
            ensure(digest(readFile(safePath($runtime, $path))) === $hash, 'Připnutá kopie nástrojů byla změněna: ' . $path);
        }
        $directory = new \RecursiveDirectoryIterator($runtime, \FilesystemIterator::SKIP_DOTS);
        $filter = new \RecursiveCallbackFilterIterator($directory, fn($file) => $file->getPathname() !== $runtime . DIRECTORY_SEPARATOR . '.cache');
        $iterator = new \RecursiveIteratorIterator($filter);
        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', substr($file->getPathname(), strlen($runtime) + 1));
            if (str_starts_with($path, '.cache/')) { continue; }
            ensure(isset($lock['files'][$path]), 'Soubor navíc v připnuté kopii: ' . $path);
        }
        ensure(is_array($lock['entrypoints'] ?? null) && array_keys($lock['entrypoints']) === self::ENTRYPOINTS, 'Neúplné vstupní body připnuté kopie.');
        foreach ($lock['entrypoints'] as $path => $hash) {
            ensure(digest(readFile(safePath($root, $path))) === $hash, 'Změněný vstupní bod napojení: ' . $path);
        }
        ensure(str_contains(readFile($root . '/AGENTS.md'), '.nastroje-prace/pravidla/projekt.md'), 'AGENTS.md neodkazuje na společná pravidla.');
        return $lock;
    }

    public static function installHooks(string $root): array
    {
        ensure(realpath(git($root, 'rev-parse', '--show-toplevel')) === realpath($root), 'Hooky se instalují jen v kořeni projektu.');
        $central = realpath($root) === realpath(TOOL_ROOT);
        ensure($central || realpath($root . '/' . self::DIRECTORY) === realpath(TOOL_ROOT), 'Hooky lze instalovat pouze do repozitáře nástrojů nebo napojeného projektu.');
        if (!$central) { self::binding($root); }
        $current = trim(run(['git', '-C', $root, 'config', '--local', '--get', 'core.hooksPath'], check: false)['stdout']);
        ensure(in_array($current, $central ? ['', '.githooks', 'hooky'] : ['', 'hooky'], true), 'Projekt již má jiné hooky; nebudou přepsány.');
        foreach (['commit-msg', 'pre-push'] as $name) {
            ensure(is_file($root . '/hooky/' . $name), 'Chybí soubor hooku.');
            ensure(chmod($root . '/hooky/' . $name, 0755), 'Nelze nastavit spustitelný hook.');
        }
        git($root, 'config', '--local', 'core.hooksPath', 'hooky');
        git($root, 'config', '--local', 'nastrojePrace.php', PHP_BINARY);
        if ($central) { run(['git', '-C', $root, 'config', '--local', '--unset-all', 'nastrojePrace.python'], check: false); }
        return ['hooks' => 'hooky', 'scope' => 'local', 'php' => PHP_BINARY];
    }

    public static function check(string $root, ?ApiClient $client = null): array
    {
        MainBranch::local($root);
        $lock = self::binding($root);
        ensure(realpath(git($root, 'rev-parse', '--show-toplevel')) === realpath($root), 'Projekt nemá vlastní Git kořen.');
        $repo = config($root)['repository'];
        $origin = git($root, 'remote', 'get-url', 'origin');
        ensure(in_array($origin, ['https://github.com/' . $repo, 'https://github.com/' . $repo . '.git', 'git@github.com:' . $repo . '.git'], true), 'Origin neodpovídá nastavení projektu.');
        ensure(git($root, 'config', '--local', '--get', 'core.hooksPath') === 'hooky', 'Nejsou instalovány místní hooky.');
        ensure(realpath(git($root, 'config', '--local', '--get', 'nastrojePrace.php')) === realpath(PHP_BINARY), 'Hooky používají jiné nebo chybějící PHP.');
        foreach (['.github/workflows/kontroly.yml', '.github/workflows/issues.yml', 'docs/05-napojeni-pravidel.md'] as $file) {
            ensure(is_file($root . '/' . $file), 'Chybí soubor napojení: ' . $file);
        }
        $workflow = readFile($root . '/.github/workflows/kontroly.yml');
        ensure(str_contains($workflow, 'Povinne kontroly') && str_contains($workflow, '.nastroje-prace/scripts/ci.php'), 'Workflow neobsahuje povinný vstup kontrol.');
        Policy::repositoryContent($root);
        Policy::readme($root);
        Upstream::check('kontrola-dokumentace.php', [$root]);
        if ($client !== null) { self::remoteReadiness($root, $client); }
        return ['commit' => $lock['commit'], 'local' => true, 'online' => $client !== null, 'ready' => $client !== null];
    }

    public static function remoteReadiness(string $root, ApiClient $client): void
    {
        Policy::repositoryMetadata($root, $client);
        $meta = $client->request('GET', '');
        ensure(($meta['default_branch'] ?? '') === 'main' && ($meta['has_issues'] ?? false) === true, 'Výchozí větev musí být main a issues musí být zapnuté.');
        ensure(trim($meta['description'] ?? '') !== '', 'Repozitář musí mít popis.');
        $labels = Policy::names($client->pages('/labels'));
        ensure(array_diff(['bug', 'enhancement', 'documentation', 'rozhrani', 'bez-rozhrani'], $labels) === [], 'Chybí povinné štítky issues.');
        ensure(array_column($client->pages('/branches'), 'name') === ['main'], 'Repozitář smí obsahovat pouze větev main.');
        Gate::published($root, $client);
    }
}
