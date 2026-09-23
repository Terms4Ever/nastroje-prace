<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;

use NastrojePrace\{Failure, Gate, Svn};
use function NastrojePrace\{git, readFile, readJson, run, writeFile, writeJson};

function hasSvn(): bool
{
    static $available = null;
    if ($available === null) {
        try {
            $available = run([getenv('NASTROJE_SVN') ?: 'svn', '--version', '--quiet'], check: false)['code'] === 0
                && run(['svnadmin', '--version', '--quiet'], check: false)['code'] === 0;
        } catch (Failure) { $available = false; }
    }
    return $available;
}

function svnFixture(callable $action): mixed
{
    return fixture(function ($root) use ($action): mixed {
        $server = new TempTree();
        try {
            $repository = $server->root . '/repo';
            run(['svnadmin', 'create', $repository]);
            $url = 'file://' . (PHP_OS_FAMILY === 'Windows' ? '/' : '') . implode('/', array_map('rawurlencode', explode('/', $repository)));
            $url = str_replace('%3A', ':', $url);
            $import = $server->root . '/import';
            writeFile($import . '/src/main.txt', "původní obsah\n");
            $exe = getenv('NASTROJE_SVN') ?: 'svn';
            run([$exe, 'import', $import, $url, '-m', 'Testovaci zaklad', '--non-interactive']);
            $wc = $server->root . '/wc';
            run([$exe, 'checkout', $url, $wc, '--non-interactive']);
            $settings = readJson($root . '/.prace.json');
            $settings['svn'] = ['enabled' => true, 'url' => $url, 'allow' => ['src/**']];
            writeJson($root . '/.prace.json', $settings);
            $record = readJson($root . '/.tasks/1.json'); $record['delivery'] = 'svn';
            writeJson($root . '/.tasks/1.json', $record);
            $base = commit($root);
            Svn::baseline($root, 1);
            return $action($root, $base, $wc, $exe);
        } finally { $server->remove(); }
    });
}

function svnProof(string $root, string $base): FakeClient
{
    $client = new FakeClient(); $client->sha = git($root, 'rev-parse', 'HEAD');
    quiet(fn() => Gate::verify($root, $base, true, $client));
    return $client;
}

function svnChange(string $root, string $base): FakeClient
{
    change($root);
    return svnProof($root, $base);
}

function colleagueChange(string $wc, string $exe): void
{
    writeFile($wc . '/src/main.txt', "změna kolegy\n");
    run([$exe, 'commit', $wc, '-m', 'Soubezna testovaci zmena', '--non-interactive']);
}

test('Linux CI skutečně vyžaduje SVN', function (): void {
    if (getenv('NASTROJE_REQUIRE_SVN') === '1') {
        expect(hasSvn(), 'CI musí skutečně spustit SVN integrační scénáře.');
    }
});
test('SVN adaptér odmítá zapisující příkazy', fn() => fails(fn() => Svn::command('commit'), 'pouze čtení'));
test('Osobní soubory se nepředají ani při širokém allow', function (): void {
    foreach (['.git/config', '.github/workflows/test.yml', '.tasks/1.json', 'docs/ukoly/1.md', 'AGENTS.md', 'src/CLAUDE.md',
        'hooky/pre-push', 'hooky/commit-msg', '.nastroje-prace/src/load.php', 'prace.php', 'prace.ps1', 'nastroje-prace.lock.json', 'README.md'] as $path) {
        expect(!Svn::allowed($path, ['allow' => ['*']]));
    }
    expect(Svn::allowed('src/main.txt', ['allow' => ['src/**']]));
});
test('SVN připraví balíček a zaznamená skutečnou výslednou revizi', fn() => svnFixture(function ($root, $base, $wc, $exe): void {
    $client = svnChange($root, $base);
    $manifest = Svn::prepare($root, 1, $client);
    $value = Svn::verifyPackage($root, $manifest, $client);
    expect(array_column($value['files'], 'path') === ['src/main.txt']);
    expect(readFile($wc . '/src/main.txt') === "původní obsah\n");
    copy($root . '/src/main.txt', $wc . '/src/main.txt');
    run([$exe, 'commit', $wc, '-m', 'Predani testovaneho obsahu', '--non-interactive']);
    expect(Svn::recordDelivery($root, $manifest, 2, $client)['svn_revision'] === 2);
}), true);
test('Změna kolegy blokuje přípravu SVN balíčku', fn() => svnFixture(function ($root, $base, $wc, $exe): void {
    $client = svnChange($root, $base); colleagueChange($wc, $exe);
    fails(fn() => Svn::prepare($root, 1, $client), 'Kolega změnil');
}), true);
test('Změna kolegy po přípravě blokuje předání', fn() => svnFixture(function ($root, $base, $wc, $exe): void {
    $client = svnChange($root, $base); $manifest = Svn::prepare($root, 1, $client); colleagueChange($wc, $exe);
    fails(fn() => Svn::verifyPackage($root, $manifest, $client), 'Předání je zastaveno');
}), true);
test('Poškozený ZIP a špatná revize jsou odmítnuty', fn() => svnFixture(function ($root, $base): void {
    $client = svnChange($root, $base); $manifest = Svn::prepare($root, 1, $client);
    fails(fn() => Svn::recordDelivery($root, $manifest, 1, $client), 'výsledná revize');
    file_put_contents(dirname($manifest) . '/zmeny.zip', 'corruption', FILE_APPEND);
    fails(fn() => Svn::verifyPackage($root, $manifest, $client), 'Balíček byl změněn');
}), true);
test('Upravený manifest nedoloží neověřený obsah', fn() => svnFixture(function ($root, $base): void {
    $client = svnChange($root, $base); $manifest = Svn::prepare($root, 1, $client);
    $value = readJson($manifest); $value['files'][0]['after'] = str_repeat('a', 64); writeJson($manifest, $value);
    fails(fn() => Svn::recordDelivery($root, $manifest, 2, $client), 'Git obsahu');
}), true);
test('Počáteční Git kopie se musí shodovat se SVN', fn() => svnFixture(function ($root): void {
    writeFile($root . '/src/main.txt', 'zastaralý výchozí obsah'); commit($root);
    fails(fn() => Svn::baseline($root, 1), 'neodpovídá aktuálnímu SVN');
}), true);
test('Předání samotného odstranění vytvoří platný prázdný ZIP', fn() => svnFixture(function ($root, $base, $wc, $exe): void {
    unlink($root . '/src/main.txt');
    file_put_contents($root . '/docs/ukoly/1.md', "\nOvěřeno úplné odstranění zdrojového souboru.\n", FILE_APPEND);
    commit($root); $client = svnProof($root, $base);
    $manifest = Svn::prepare($root, 1, $client);
    $value = Svn::verifyPackage($root, $manifest, $client);
    expect($value['files'][0]['action'] === 'delete');
    run([$exe, 'delete', $wc . '/src/main.txt', '--non-interactive']);
    run([$exe, 'commit', $wc, '-m', 'Test odstraneni souboru', '--non-interactive']);
    expect(Svn::recordDelivery($root, $manifest, 2, $client)['files'][0]['after'] === null);
}), true);
test('Nový soubor se předá se skutečně ověřeným obsahem', fn() => svnFixture(function ($root, $base, $wc, $exe): void {
    writeFile($root . '/src/novy.txt', "přidaný obsah\n");
    file_put_contents($root . '/docs/ukoly/1.md', "\nOvěřeno přidání nového zdrojového souboru.\n", FILE_APPEND);
    commit($root); $client = svnProof($root, $base);
    $manifest = Svn::prepare($root, 1, $client); $value = Svn::verifyPackage($root, $manifest, $client);
    expect($value['files'][0]['before'] === null);
    copy($root . '/src/novy.txt', $wc . '/src/novy.txt');
    run([$exe, 'add', $wc . '/src/novy.txt', '--non-interactive']);
    run([$exe, 'commit', $wc, '-m', 'Test pridani souboru', '--non-interactive']);
    expect(Svn::recordDelivery($root, $manifest, 2, $client)['svn_revision'] === 2);
}), true);

test('Všechny vstupy předání odmítnou chybějící online důkaz i CI před zápisem', fn() => fixture(function ($root): void {
    $settings = readJson($root . '/.prace.json');
    $settings['svn'] = ['enabled' => true, 'url' => 'file:///nepouzita-testovaci-cesta', 'allow' => ['src/**']];
    writeJson($root . '/.prace.json', $settings); $base = commit($root); $sha = change($root);
    $client = new FakeClient(); $client->sha = $sha;
    $manifest = $root . '/.local/neexistujici-manifest.json';
    $actions = [fn() => Svn::prepare($root, 1, $client), fn() => Svn::verifyPackage($root, $manifest, $client),
        fn() => Svn::recordDelivery($root, $manifest, 2, $client)];
    quiet(fn() => Gate::verify($root, $base));
    foreach ($actions as $action) { fails($action, 'online ověření'); }
    quiet(fn() => Gate::verify($root, $base, true, $client));
    $client->conclusion = 'failure';
    foreach ($actions as $action) { fails($action, 'úspěšné GitHub CI'); }
    $client->conclusion = 'success'; $client->status = 'queued';
    foreach ($actions as $action) { fails($action, 'úspěšné GitHub CI'); }
    $client->status = 'completed'; $client->sha = $base;
    foreach ($actions as $action) { fails($action, 'aktuálním main'); }
    $client->sha = $sha; $client->available = false;
    foreach ($actions as $action) { fails($action, 'Nedostupné API'); }
    expect(!is_dir($root . '/.local/predani') && !is_dir($root . '/.local/svn') && $client->writes() === []);
}));

test('Nový neúspěch CI po přípravě balíčku blokuje další předání a jeho zápis', fn() => svnFixture(function ($root, $base): void {
    $client = svnChange($root, $base); $manifest = Svn::prepare($root, 1, $client);
    $client->conclusion = 'failure';
    fails(fn() => Svn::verifyPackage($root, $manifest, $client), 'úspěšné GitHub CI');
    fails(fn() => Svn::recordDelivery($root, $manifest, 2, $client), 'úspěšné GitHub CI');
    expect(!file_exists($root . '/.local/svn/1-predano.json'));
    $client->conclusion = 'success'; Svn::verifyPackage($root, $manifest, $client);
}), true);
