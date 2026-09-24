<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;

use NastrojePrace\{Cli, Gate, Policy, Project, RuleSet};
use function NastrojePrace\{git, readFile, readJson, run, writeFile, writeJson};

test('Pracovní volba se ověřuje místně a v samostatném CLI', fn() => fixture(function ($root): void {
    expect(RuleSet::verify($root) === 'nastroje-prace');
    $result = run([PHP_BINARY, \NastrojePrace\TOOL_ROOT . '/prace.php', '--root', $root, 'rules-check']);
    expect(json_decode($result['stdout'], true)['sada'] === 'nastroje-prace');
}));

test('Pracovní kontrola odmítne osobní i chybějící výběr', fn() => fixture(function ($root, $temp): void {
    writeJson($root . '/.pravidla.json', ['sada' => 'nastroje']);
    fails(fn() => RuleSet::verify($root), 'Očekávaná sada');
    $temp->remove('.pravidla.json');
    fails(fn() => RuleSet::verify($root), 'Chybí .pravidla.json');
}));

test('Plný check nevystaví doklad při chybné sadě', fn() => fixture(function ($root): void {
    $base = git($root, 'rev-parse', 'HEAD');
    writeJson($root . '/.pravidla.json', ['sada' => 'nastroje']);
    change($root);
    fails(fn() => quiet(fn() => Gate::verify($root, $base)), 'Očekávaná sada');
    expect(!file_exists(Gate::receiptPath($root, git($root, 'rev-parse', 'HEAD'))));
}));

test('Metadata odmítnou současné označení dvěma sadami', fn() => fixture(function ($root): void {
    $client = new FakeClient();
    $client->topics[] = 'pravidla-nastroje';
    fails(fn() => Policy::repositoryMetadata($root, $client), 'topic');
    expect($client->writes() === []);
}));

test('Očekávaná topics nesmějí odebrat nebo obrátit hlavní sadu', fn() => fixture(function ($root): void {
    $settings = readJson($root . '/.prace.json');
    foreach ([['php'], ['php', 'pravidla-nastroje']] as $topics) {
        writeJson($root . '/.prace.json', [...$settings, 'topics' => $topics]);
        fails(fn() => RuleSet::verify($root), 'topic');
    }
}));

test('Export přidá pracovní topic a odmítne osobní sadu před zápisem', fn() => projectFixture(function ($target, $source): void {
    $spec = projectSpec();
    $spec['topics'][] = 'pravidla-nastroje';
    fails(fn() => Project::initialize($spec, $target, $source), 'topic');
    expect(count(scandir($target)) === 2);
    Project::initialize(projectSpec(), $target, $source);
    expect(readJson($target . '/.pravidla.json') === ['sada' => 'nastroje-prace']);
    expect(in_array('pravidla-nastroje-prace', readJson($target . '/.prace.json')['topics'], true));
}));

test('Připnuté napojení odhalí smazaný výběr i přímé osobní workflow', fn() => projectFixture(function ($target, $source): void {
    application($target, $source);
    $path = $target . '/.pravidla.json';
    $saved = readFile($path);
    unlink($path);
    $result = run([PHP_BINARY, 'prace.php', 'project-check'], cwd: $target, check: false);
    expect($result['code'] !== 0 && str_contains($result['stderr'], '.pravidla.json'));
    writeFile($path, $saved);
    writeFile($target . '/.github/workflows/osobni.yml', "name: Osobni\non: [push]\njobs:\n  readme:\n    uses: Terms4Ever/nastroje/.github/workflows/readme.yml@main\n");
    $result = run([PHP_BINARY, 'prace.php', 'project-check'], cwd: $target, check: false);
    expect($result['code'] !== 0 && str_contains($result['stderr'], 'Osobní workflow'));
}));
