<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;

use NastrojePrace\{Gate, Issues, Upstream};
use function NastrojePrace\{git, readFile, readJson, writeFile, writeJson};

test('Správná změna má doklad přesného commitu', fn() => fixture(function ($root): void {
    $base = git($root, 'rev-parse', 'HEAD'); $sha = change($root);
    $value = quiet(fn() => Gate::verify($root, $base));
    expect($value['commit'] === $sha && Gate::receipt($root)['commit'] === $sha);
}));
test('Samotný kód ani nesouvisející obrázek nenahradí textový záznam', fn() => fixture(function ($root): void {
    $base = git($root, 'rev-parse', 'HEAD'); change($root, false);
    fails(fn() => Gate::verify($root, $base), 'textový záznam');
    writeFile($root . '/docs/snimky/unrelated.png', 'fixture'); commit($root);
    fails(fn() => Gate::verify($root, $base), 'textový záznam');
}));
test('Neznámý základ a ROOT po prvním commitu nezpůsobí přeskočení', fn() => fixture(function ($root): void {
    change($root);
    foreach ([str_repeat('1', 40), 'ROOT'] as $base) { fails(fn() => Gate::verify($root, $base)); }
}));
test('Špinavý strom a nový commit zneplatní doklad', fn() => fixture(function ($root): void {
    $base = git($root, 'rev-parse', 'HEAD'); change($root); quiet(fn() => Gate::verify($root, $base));
    writeFile($root . '/src/main.txt', 'další změna');
    fails(fn() => Gate::receipt($root));
    commit($root); fails(fn() => Gate::receipt($root));
}));
test('Neúspěšný povinný test nevydá doklad', fn() => fixture(function ($root): void {
    $base = git($root, 'rev-parse', 'HEAD');
    $settings = readJson($root . '/.prace.json'); $settings['tests'] = [['{php}', '-r', 'exit(7);']];
    writeJson($root . '/.prace.json', $settings); $sha = change($root);
    fails(fn() => quiet(fn() => Gate::verify($root, $base)), 'Povinné ověření');
    expect(!file_exists(Gate::receiptPath($root, $sha)));
}));
test('Neúspěšné opakované ověření odstraní starý úspěch', fn() => fixture(function ($root): void {
    $base = git($root, 'rev-parse', 'HEAD'); $sha = change($root);
    $client = new FakeClient(); quiet(fn() => Gate::verify($root, $base, true, $client));
    $client->available = false;
    fails(fn() => Gate::verify($root, $base, true, $client), 'Nedostupné API');
    expect(!file_exists(Gate::receiptPath($root, $sha)));
}));
test('Jiná verze společných nástrojů je odmítnuta', fn() => fixture(fn($root) => fails(fn() => Upstream::verify($root), 'verze společných')));
test('Každé issue musí změnit vlastní textový záznam', fn() => fixture(function ($root): void {
    $record = readJson($root . '/.tasks/1.json'); $record['issue'] = 2;
    writeJson($root . '/.tasks/2.json', $record);
    writeFile($root . '/docs/ukoly/2.md', readFile($root . '/docs/ukoly/1.md'));
    $base = commit($root);
    writeFile($root . '/src/main.txt', 'Jiný výsledek');
    file_put_contents($root . '/docs/ukoly/1.md', "\nZměna patří k jinému úkolu.\n", FILE_APPEND);
    commit($root, str_replace('(#1)', '(#2)', MESSAGE));
    fails(fn() => Gate::verify($root, $base), 'Každý úkol');
}));
test('Úplná kontrola skutečně blokuje chybné pořadí README', fn() => fixture(function ($root): void {
    $base = git($root, 'rev-parse', 'HEAD');
    $text = readFile($root . '/README.md');
    preg_match('/## 📚 Dokumentace\n.*?(?=## 🚀 Instalace)/su', $text, $match);
    writeFile($root . '/README.md', str_replace($match[0], '', $text) . $match[0]);
    change($root);
    fails(fn() => Gate::verify($root, $base), 'jednotné pořadí');
}));
test('Dokončení issue vyžaduje úspěšné CI přesného main', fn() => fixture(function ($root): void {
    $base = git($root, 'rev-parse', 'HEAD'); $sha = change($root);
    $client = new FakeClient(); $client->sha = $sha;
    quiet(fn() => Gate::verify($root, $base, true, $client));
    $client->item['body'] = str_replace('[ ]', '[x]', BODY);
    $client->conclusion = 'failure';
    fails(fn() => Issues::close($root, 1, 'Ověření odpovídá dokončenému úkolu.', $client), 'úspěšné GitHub CI');
    expect($client->writes() === []);
    $client->conclusion = 'success'; $client->sha = str_repeat('a', 40);
    fails(fn() => Issues::close($root, 1, 'Ověření odpovídá dokončenému úkolu.', $client), 'aktuálním main');
    expect($client->writes() === []);
    $client->sha = $sha;
    $closed = Issues::close($root, 1, 'Ověření odpovídá dokončenému úkolu.', $client);
    expect($closed['state'] === 'closed' && count($client->writes()) === 2);
}));
