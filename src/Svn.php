<?php
declare(strict_types=1);

namespace NastrojePrace;

final class Svn
{
    private const DENY = ['.git', '.svn', '.github', '.tasks', '.cache', '.local', 'docs', 'tests/fixtures',
        'hooky', 'prace.php', 'prace.ps1', 'nastroje-prace.lock.json', 'README.md'];

    public static function settings(string $root): array
    {
        $value = config($root)['svn'];
        ensure(($value['enabled'] ?? false) === true, 'SVN není pro tento projekt zapnuté.');
        $url = rtrim($value['url'] ?? '', '/');
        $parsed = parse_url($url);
        ensure(in_array($parsed['scheme'] ?? '', ['https', 'svn', 'svn+ssh', 'file'], true) && !isset($parsed['user']) && !isset($parsed['pass']),
            'SVN URL musí používat podporovaný protokol a nesmí obsahovat přihlašovací údaje.');
        ensure(is_array($value['allow'] ?? null) && $value['allow'] !== [], 'Chybí explicitní seznam cest povolených k předání.');
        foreach ($value['allow'] as $pattern) {
            ensure(is_string($pattern) && $pattern !== '' && !str_contains($pattern, '..'), 'Neplatná povolená cesta SVN.');
        }
        $value['url'] = $url;
        ksort($value);
        return $value;
    }

    public static function allowed(string $relative, array $options): bool
    {
        foreach (self::DENY as $prefix) {
            if ($relative === $prefix || str_starts_with($relative, $prefix . '/')) {
                return false;
            }
        }
        if (str_starts_with($relative, '.') || in_array(basename($relative), ['AGENTS.md', 'CLAUDE.md'], true)) {
            return false;
        }
        foreach ($options['allow'] as $pattern) {
            if (fnmatch($pattern, $relative)) {
                return true;
            }
        }
        return false;
    }

    public static function command(string ...$args): string
    {
        // Tento adaptér neobsahuje žádnou zapisující operaci SVN.
        ensure(in_array($args[0] ?? '', ['info', 'list', 'cat'], true), 'Povoleno je pouze čtení SVN.');
        return run([getenv('NASTROJE_SVN') ?: 'svn', '--non-interactive', ...$args])['stdout'];
    }

    private static function xml(string $data): \SimpleXMLElement
    {
        ensure(!str_contains(strtoupper($data), '<!DOCTYPE'), 'Nepovolená XML deklarace.');
        $previous = libxml_use_internal_errors(true);
        try {
            $value = simplexml_load_string($data, \SimpleXMLElement::class, LIBXML_NONET);
            ensure($value !== false, 'SVN nevrátilo platné XML.');
            return $value;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    public static function remoteState(array $options, ?array $paths = null, int|string $revision = 'HEAD'): array
    {
        $url = $options['url'];
        ensure($revision === 'HEAD' || (is_int($revision) && $revision > 0), 'Neplatná čtená revize SVN.');
        $info = self::xml(self::command('info', '--xml', '-r', (string) $revision, $url . '@' . $revision));
        $number = (string) ($info->entry['revision'] ?? '');
        ensure(ctype_digit($number), 'Nelze určit revizi SVN.');
        $revision = (int) $number;
        $listing = self::xml(self::command('list', '--xml', '-R', '-r', (string) $revision, $url . '@' . $revision));
        $existing = [];
        foreach ($listing->xpath('//entry') as $entry) {
            if ((string) $entry['kind'] === 'file') {
                $existing[] = (string) $entry->name;
            }
        }
        $selected = $paths ?? array_values(array_filter($existing, fn($path) => self::allowed($path, $options)));
        sort($selected);
        $files = [];
        foreach ($selected as $name) {
            safePath(getcwd(), $name);
            if (!in_array($name, $existing, true)) {
                $files[$name] = null;
                continue;
            }
            $target = $url . '/' . implode('/', array_map('rawurlencode', explode('/', $name))) . '@' . $revision;
            $files[$name] = digest(self::command('cat', '-r', (string) $revision, $target));
        }
        return ['url' => $url, 'revision' => $revision, 'files' => $files];
    }

    public static function baseline(string $root, int $number): array
    {
        $options = self::settings($root);
        Policy::taskRecord($root, $number);
        clean($root);
        $state = self::remoteState($options);
        $local = [];
        foreach (tracked($root) as $path) {
            if (self::allowed($path, $options)) {
                $local[$path] = digest(readFile(safePath($root, $path)));
            }
        }
        ksort($local);
        ensure($local === $state['files'], 'Git kopie neodpovídá aktuálnímu SVN základu. Nejprve převzít a ověřit původní stav.');
        $value = [...$state, 'issue' => $number, 'git_commit' => resolveCommit($root, 'HEAD'), 'time' => now(), 'settings' => digest(json($options))];
        $path = $root . '/.local/svn/' . $number . '-zaklad.json';
        ensure(!file_exists($path), 'Výchozí stav již existuje; nesmí se tiše přepsat novějším stavem.');
        writeJson($path, $value);
        return $value;
    }

    public static function prepare(string $root, int $number): string
    {
        $options = self::settings($root);
        $proof = Gate::receipt($root);
        ensure(in_array($number, $proof['issues'], true), 'Ověření neobsahuje tento úkol.');
        $record = Policy::taskRecord($root, $number);
        ensure($record['delivery'] === 'svn', 'Úkol není určen k předání do SVN.');
        $base = readJson($root . '/.local/svn/' . $number . '-zaklad.json');
        ensure($base['url'] === $options['url'] && $base['settings'] === digest(json($options)), 'Nastavení SVN se od zachycení základu změnilo.');
        ensure($proof['base'] === $base['git_commit'], 'Ověřený rozsah nezačíná zaznamenaným SVN/Git základem; použij celé SHA.');
        $edits = array_values(array_filter(changed($root, $proof['base']), fn($entry) => self::allowed($entry[1], $options)));
        ensure($edits !== [], 'Není co předat z explicitně povolených cest.');
        $current = self::remoteState($options, array_column($edits, 1));
        $files = [];
        foreach ($edits as [$status, $path]) {
            ensure($current['files'][$path] === ($base['files'][$path] ?? null), 'Kolega změnil soubor ' . $path . '; nejprve sloučit a znovu ověřit.');
            $after = $status === 'D' ? null : digest(readFile(safePath($root, $path)));
            $files[] = ['path' => $path, 'action' => $status === 'D' ? 'delete' : 'write', 'before' => $base['files'][$path] ?? null, 'after' => $after];
        }
        $directory = $root . '/.local/predani/' . $number . '-' . $proof['commit'];
        ensure(!file_exists($directory), 'Balíček již existuje; pro kontrolu použij predani-over.');
        ensure(mkdir($directory, 0777, true), 'Nelze vytvořit složku předání.');
        $archive = $directory . '/zmeny.zip';
        $writes = array_values(array_filter($files, fn($item) => $item['action'] === 'write'));
        if ($writes === []) {
            // Platný prázdný ZIP pro změnu složenou pouze z odstranění.
            writeFile($archive, "PK\x05\x06" . str_repeat("\0", 18));
        } else {
            $zip = new \ZipArchive();
            ensure($zip->open($archive, \ZipArchive::CREATE | \ZipArchive::EXCL) === true, 'Nelze vytvořit ZIP.');
            try {
                foreach ($writes as $item) {
                    ensure($zip->addFromString($item['path'], readFile(safePath($root, $item['path']))), 'Nelze přidat soubor do ZIPu.');
                }
            } finally {
                ensure($zip->close(), 'Nelze dokončit ZIP.');
            }
        }
        $manifest = ['version' => 1, 'issue' => $number, 'mantis' => $record['mantis'] ?? null, 'commit' => $proof['commit'],
            'policy' => $proof['policy'], 'url' => $options['url'], 'baseline_revision' => $base['revision'],
            'checked_revision' => $current['revision'], 'files' => $files, 'archive_sha256' => digest(readFile($archive)), 'time' => now()];
        writeJson($directory . '/manifest.json', $manifest);
        return $directory . '/manifest.json';
    }

    private static function validateFiles(string $root, array $manifest, array $proof, array $options): void
    {
        ensure(in_array($manifest['issue'], $proof['issues'], true), 'Balíček nepatří ověřenému úkolu.');
        $record = Policy::taskRecord($root, $manifest['issue']);
        ensure($record['delivery'] === 'svn' && ($record['mantis'] ?? null) === $manifest['mantis'], 'Nesouhlasí metadata předání.');
        $base = readJson($root . '/.local/svn/' . $manifest['issue'] . '-zaklad.json');
        ensure($proof['base'] === $base['git_commit'] && $manifest['baseline_revision'] === $base['revision'] && $base['settings'] === digest(json($options)), 'Základ balíčku neodpovídá ověřenému SVN/Git základu.');
        $expected = [];
        foreach (changed($root, $proof['base']) as [$status, $path]) {
            if (self::allowed($path, $options)) {
                $expected[$path] = $status;
            }
        }
        $entries = $manifest['files'] ?? [];
        $actualPaths = array_column($entries, 'path');
        $expectedPaths = array_keys($expected);
        sort($actualPaths);
        sort($expectedPaths);
        ensure($entries !== [] && $actualPaths === $expectedPaths, 'Seznam balíčku neodpovídá skutečnému Git rozdílu.');
        foreach ($entries as $item) {
            $path = safePath($root, $item['path']);
            $action = $expected[$item['path']] === 'D' ? 'delete' : 'write';
            ensure($item['action'] === $action, 'Operace balíčku neodpovídá Git rozdílu.');
            $after = $action === 'delete' ? null : digest(readFile($path));
            ensure($item['after'] === $after, 'Otisk balíčku neodpovídá ověřenému Git obsahu.');
            ensure($item['before'] === ($base['files'][$item['path']] ?? null), 'Výchozí obsah balíčku neodpovídá zaznamenanému základu.');
        }
    }

    public static function verifyPackage(string $root, string $manifestPath): array
    {
        $proof = Gate::receipt($root);
        $options = self::settings($root);
        $manifest = readJson($manifestPath);
        ensure($manifest['commit'] === $proof['commit'] && $manifest['policy'] === $proof['policy'], 'Balíček patří jiné ověřené verzi.');
        ensure($manifest['url'] === $options['url'], 'Nesedí cílové SVN.');
        self::validateFiles($root, $manifest, $proof, $options);
        $archive = dirname($manifestPath) . '/zmeny.zip';
        ensure(digest(readFile($archive)) === $manifest['archive_sha256'], 'Balíček byl změněn.');
        $expected = array_column(array_filter($manifest['files'], fn($item) => $item['action'] === 'write'), 'path');
        $zip = new \ZipArchive();
        ensure($zip->open($archive) === true, 'Nelze načíst ZIP předání.');
        try {
            $actual = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $actual[] = $zip->getNameIndex($i);
            }
            sort($actual);
            sort($expected);
            ensure($actual === $expected, 'Obsah archivu neodpovídá manifestu.');
            foreach ($manifest['files'] as $item) {
                $path = safePath($root, $item['path']);
                ensure(self::allowed($item['path'], $options), 'Balíček obsahuje nepovolený soubor.');
                if ($item['action'] === 'write') {
                    $data = $zip->getFromName($item['path']);
                    ensure($data !== false && digest($data) === $item['after'] && digest(readFile($path)) === $item['after'], 'Obsah neodpovídá ověřenému commitu.');
                } else {
                    ensure($item['action'] === 'delete' && !file_exists($path), 'Neplatné odstranění souboru.');
                }
            }
        } finally {
            $zip->close();
        }
        $current = self::remoteState($options, array_column($manifest['files'], 'path'));
        foreach ($manifest['files'] as $item) {
            ensure($current['files'][$item['path']] === $item['before'], 'SVN se od přípravy balíčku změnilo. Předání je zastaveno.');
        }
        return $manifest;
    }

    public static function recordDelivery(string $root, string $manifestPath, int $revision): array
    {
        $proof = Gate::receipt($root);
        $manifest = readJson($manifestPath);
        ensure($proof['commit'] === $manifest['commit'] && $manifest['policy'] === $proof['policy'], 'Předání patří jinému commitu.');
        ensure($revision > $manifest['baseline_revision'], 'Neplatná výsledná revize.');
        $options = self::settings($root);
        ensure($options['url'] === $manifest['url'], 'Nesedí cílové SVN.');
        self::validateFiles($root, $manifest, $proof, $options);
        $actual = self::remoteState($options, array_column($manifest['files'], 'path'), $revision);
        foreach ($manifest['files'] as $item) {
            ensure($actual['files'][$item['path']] === $item['after'], 'Výsledná SVN revize neobsahuje ověřené změny.');
        }
        $value = ['issue' => $manifest['issue'], 'mantis' => $manifest['mantis'], 'commit' => $proof['commit'],
            'svn_url' => $options['url'], 'svn_revision' => $revision, 'time' => now(), 'files' => $manifest['files']];
        writeJson($root . '/.local/svn/' . $manifest['issue'] . '-predano.json', $value);
        return $value;
    }
}
