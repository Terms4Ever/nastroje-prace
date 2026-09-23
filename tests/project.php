<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;

use NastrojePrace\{Project, Policy, Upstream, Gate};
use function NastrojePrace\{git, readFile, readJson, writeFile, writeJson, run};

function projectSpec(): array
{
    return ['repository' => 'Terms4Ever/zkouska-napojeni', 'name' => 'Zkušební aplikace',
        'description' => 'Izolované ověření napojení pracovních pravidel.', 'stack' => 'PHP 8.3 a souborové ověření výpočtu.',
        'topics' => ['php', 'tooling'], 'tests' => [['{php}', 'tests/app.php']]];
}

function projectFixture(callable $action): void
{
    $temp = new TempTree();
    try {
        $source = $temp->root . '/source';
        $target = $temp->root . '/project';
        mkdir($source); mkdir($target);
        // Izolovaný čistý commit právě testované implementace, i před commitem hlavního repozitáře.
        foreach (['src/*.php', 'scripts/*.php', 'scripts/*.ps1', 'hooky/*', 'pravidla/*.md', 'prace.php', 'prace.ps1', 'upstream.lock.json', '.prace.json'] as $pattern) {
            foreach (glob(\NastrojePrace\TOOL_ROOT . '/' . $pattern) as $file) {
                $relative = str_replace('\\', '/', substr($file, strlen(\NastrojePrace\TOOL_ROOT) + 1));
                writeFile($source . '/' . $relative, str_replace("\r\n", "\n", readFile($file)));
            }
        }
        $base = \NastrojePrace\TOOL_ROOT . '/sablony/project';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
            writeFile($source . '/sablony/project/' . $relative, str_replace("\r\n", "\n", readFile($file->getPathname())));
        }
        writeFile($source . '/.gitignore', ".cache/\n.local/\n");
        git($source, 'init', '-b', 'main');
        git($source, 'config', 'core.autocrlf', 'false');
        git($source, 'config', 'user.name', 'Zkouska');
        git($source, 'config', 'user.email', 'test@example.invalid');
        $sha = commit($source);
        $action($target, $source, $sha, $temp);
    } finally { $temp->remove(); }
}

function application(string $target, string $source): void
{
    Project::initialize(projectSpec(), $target, $source);
    git($target, 'init', '-b', 'main');
    git($target, 'config', 'core.autocrlf', 'false');
    git($target, 'config', 'user.name', 'Zkouska');
    git($target, 'config', 'user.email', 'test@example.invalid');
    git($target, 'remote', 'add', 'origin', 'https://github.com/Terms4Ever/zkouska-napojeni');
    writeFile($target . '/src/app.php', "<?php\nfunction soucet(int \$a, int \$b): int { return \$a + \$b; }\n");
    writeFile($target . '/src/puvodni.txt', "Původní řádky.\r\nBeze změny bajtů.\r\n");
    writeFile($target . '/tests/app.php', "<?php\nrequire __DIR__ . '/../src/app.php';\nif (soucet(3, 4) !== 7 || soucet(-1, 2) !== 1) { exit(1); }\necho \"Vypocet overen.\\n\";\n");
    writeJson($target . '/.tasks/7.json', ['issue' => 7, 'mantis' => null, 'technical_reason' => 'Izolované ověření založení projektu.', 'visual' => false, 'delivery' => 'git']);
    $record = "# Úkol 7\n\n";
    foreach (['Zadání', 'Změna', 'Ověření', 'Předání'] as $heading) { $record .= "## $heading\n\nIzolované ověření skutečného napojení aplikace.\n\n"; }
    writeFile($target . '/docs/ukoly/7.md', $record);
    // Pouze veřejné upstream nástroje se převezmou z již ověřené cache. Žádný token.
    $cache = $target . '/.nastroje-prace/.cache/nastroje';
    run(['git', 'clone', '--no-hardlinks', realpath(Upstream::cache()), $cache]);
    git($cache, 'checkout', '--detach', Upstream::verify());
    run([PHP_BINARY, 'prace.php', 'install-hooks'], cwd: $target);
    git($target, 'add', '.');
    git($target, 'update-index', '--chmod=+x', 'hooky/commit-msg', 'hooky/pre-push');
    run(['git', '-C', $target, 'commit', '-m', str_replace('(#1)', '(#7)', MESSAGE)]);
    run([PHP_BINARY, 'prace.php', 'state-update'], cwd: $target);
    git($target, 'add', '.');
    run(['git', '-C', $target, 'commit', '--amend', '--no-edit']);
}

test('Export odmítne neúplné zadání, neprázdný cíl, vnořený projekt a změněný zdroj', fn() => projectFixture(function ($target, $source, $sha, $temp): void {
    $spec = projectSpec(); $spec['tests'] = [];
    fails(fn() => Project::initialize($spec, $target, $source), 'ověřovací');
    expect(count(scandir($target)) === 2);
    writeFile($target . '/existing.txt', 'Zachovat tento soubor.');
    fails(fn() => Project::initialize(projectSpec(), $target, $source), 'prázdná');
    expect(readFile($target . '/existing.txt') === 'Zachovat tento soubor.');
    mkdir($source . '/nested');
    fails(fn() => Project::initialize(projectSpec(), $source . '/nested', $source), 'uvnitř');
    mkdir($temp->root . '/svn/.svn', 0777, true); mkdir($temp->root . '/svn/child');
    fails(fn() => Project::initialize(projectSpec(), $temp->root . '/svn/child', $source), 'uvnitř');
    writeFile($source . '/src/Other.php', "<?php\n");
    fails(fn() => Project::initialize(projectSpec(), $target, $source), 'čistý');
}));

test('Připnutá aplikace projde hooky, kontrolou dokumentace i testy v nové kopii', fn() => projectFixture(function ($target, $source, $sha, $temp): void {
    $global = run(['git', 'config', '--global', '--list'], check: false)['stdout'];
    application($target, $source);
    $lock = readJson($target . '/nastroje-prace.lock.json');
    expect($lock['commit'] === $sha && !file_exists($target . '/.nastroje-prace/.git'));
    expect(run(['git', '-C', $target, 'commit', '--allow-empty', '-m', 'neplatne'], check: false)['code'] !== 0);
    foreach (['project-check', 'readme-check'] as $command) { run([PHP_BINARY, 'prace.php', $command], cwd: $target); }
    run([PHP_BINARY, 'prace.php', 'check', '--base', 'ROOT'], cwd: $target);
    expect(run(['git', 'config', '--global', '--list'], check: false)['stdout'] === $global);
    $clone = $temp->root . '/clone';
    run(['git', 'clone', '--no-hardlinks', $target, $clone]);
    expect(readFile($clone . '/src/puvodni.txt') === "Původní řádky.\r\nBeze změny bajtů.\r\n");
    git($clone, 'remote', 'set-url', 'origin', 'https://github.com/Terms4Ever/zkouska-napojeni');
    $cache = $clone . '/.nastroje-prace/.cache/nastroje';
    run(['git', 'clone', '--no-hardlinks', realpath(Upstream::cache()), $cache]);
    git($cache, 'checkout', '--detach', Upstream::verify());
    expect(run([PHP_BINARY, 'prace.php', 'project-check'], cwd: $clone, check: false)['code'] !== 0);
    run([PHP_BINARY, 'prace.php', 'bootstrap'], cwd: $clone);
    run([PHP_BINARY, 'prace.php', 'install-hooks'], cwd: $clone);
    run([PHP_BINARY, 'prace.php', 'project-check'], cwd: $clone);
    run([PHP_BINARY, 'prace.php', 'check', '--base', 'ROOT'], cwd: $clone);
    if (PHP_OS_FAMILY === 'Windows') {
        run(['powershell', '-NoProfile', '-ExecutionPolicy', 'Bypass', '-File', $clone . '/prace.ps1', 'doctor'], cwd: $clone);
    }
    git($clone, 'config', '--local', 'core.hooksPath', 'vlastni-hooky');
    expect(run([PHP_BINARY, 'prace.php', 'install-hooks'], cwd: $clone, check: false)['code'] !== 0);
    expect(git($clone, 'config', '--local', '--get', 'core.hooksPath') === 'vlastni-hooky');
}));

test('Napojení odmítne změněné kontroly, chybějící lock a neúspěšný test aplikace', fn() => projectFixture(function ($target, $source): void {
    application($target, $source);
    $file = $target . '/.nastroje-prace/pravidla/projekt.md';
    $old = readFile($file); writeFile($file, $old . "\nZměněná pravidla.\n");
    expect(run([PHP_BINARY, 'prace.php', 'doctor'], cwd: $target, check: false)['code'] !== 0);
    writeFile($file, $old);
    rename($target . '/nastroje-prace.lock.json', $target . '/lock.saved');
    expect(run([PHP_BINARY, 'prace.php', 'doctor'], cwd: $target, check: false)['code'] !== 0);
    rename($target . '/lock.saved', $target . '/nastroje-prace.lock.json');
    writeFile($target . '/.nastroje-prace/src/Extra.php', "<?php\n");
    expect(run([PHP_BINARY, 'prace.php', 'doctor'], cwd: $target, check: false)['code'] !== 0);
    unlink($target . '/.nastroje-prace/src/Extra.php');
    writeFile($target . '/src/app.php', "<?php\nfunction soucet(int \$a, int \$b): int { return 0; }\n");
    file_put_contents($target . '/docs/ukoly/7.md', "\nZáporný test chybného výpočtu.\n", FILE_APPEND);
    $before = git($target, 'rev-parse', 'HEAD');
    commit($target, str_replace('(#1)', '(#7)', MESSAGE));
    $result = run([PHP_BINARY, 'prace.php', 'check', '--base', $before], cwd: $target, check: false);
    expect($result['code'] !== 0 && str_contains($result['stderr'], 'Povinné ověření'));
    expect(!file_exists(Gate::receiptPath($target, git($target, 'rev-parse', 'HEAD'))));
}));

test('Rozcestník zvládne 500 záznamů bez výčtu úkolů v README', fn() => fixture(function ($root): void {
    for ($i = 2; $i <= 500; $i++) { writeFile($root . '/docs/ukoly/' . $i . '.md', "# Úkol $i\n\nKonkrétní záznam ověřované práce.\n"); }
    Policy::readme($root);
    writeFile($root . '/README.md', str_replace('`docs/ukoly/`', '`docs/ukoly/1.md`', readFile($root . '/README.md')));
    fails(fn() => Policy::readme($root), 'každý jednotlivý');
}));

test('Dokončení zavedení vyžaduje štítky, ochranu main a CI přesného commitu', fn() => fixture(function ($root): void {
    $base = git($root, 'rev-parse', 'HEAD'); change($root);
    quiet(fn() => Gate::verify($root, $base, true, new FakeClient()));
    $client = new FakeClient();
    $client->rows = ['bug', 'enhancement', 'documentation', 'rozhrani', 'bez-rozhrani'];
    $protection = ['required_status_checks' => ['strict' => true, 'checks' => [['context' => 'Povinne kontroly', 'app_id' => 15368]]],
        'enforce_admins' => ['enabled' => true], 'required_linear_history' => ['enabled' => true],
        'allow_force_pushes' => ['enabled' => false], 'allow_deletions' => ['enabled' => false]];
    $conclusion = 'success'; $main = git($root, 'rev-parse', 'HEAD');
    $client->handler = function ($method, $suffix) use (&$protection, &$conclusion, &$main): array {
        return match (true) {
            $suffix === '' => ['private' => true, 'full_name' => 'Terms4Ever/nastroje-prace', 'default_branch' => 'main', 'has_issues' => true, 'description' => 'Zkušební projekt.'],
            $suffix === '/topics' => ['names' => ['php', 'tooling']],
            $suffix === '/branches/main/protection' => $protection,
            $suffix === '/commits/main' => ['sha' => $main],
            str_contains($suffix, '/check-runs?') => ['check_runs' => [['id' => 1, 'name' => 'Povinne kontroly', 'app' => ['slug' => 'github-actions'], 'conclusion' => $conclusion]]],
            default => throw new \RuntimeException('Neočekávané API v testu.'),
        };
    };
    Project::remoteReadiness($root, $client);
    $client->rows = []; fails(fn() => Project::remoteReadiness($root, $client), 'štítky');
    $client->rows = ['bug', 'enhancement', 'documentation', 'rozhrani', 'bez-rozhrani'];
    $protection['enforce_admins']['enabled'] = false; fails(fn() => Project::remoteReadiness($root, $client), 'Ochrana main');
    $protection['enforce_admins']['enabled'] = true;
    $conclusion = 'failure'; fails(fn() => Project::remoteReadiness($root, $client), 'CI');
    $conclusion = 'success'; $main = $base; fails(fn() => Project::remoteReadiness($root, $client), 'main');
    expect($client->writes() === []);
}));
