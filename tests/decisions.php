<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;

use NastrojePrace\{Failure, Gate, Issues, Policy, Project, Upstream};
use function NastrojePrace\{git, readFile, writeFile};
use const NastrojePrace\TOOL_ROOT;

test('Vlastník může ponechat stručný otevřený nápad bez šablony a štítků', function (): void {
    $client = new FakeClient();
    $client->rows = [['number' => 9001, 'title' => 'Nápad', 'body' => '', 'state' => 'open', 'user' => ['login' => 'Terms4Ever']]];
    expect(quiet(fn() => Issues::audit(TOOL_ROOT, $client)) === 1);
    expect($client->writes() === []);
});

test('Výjimka nápadu neplatí pro cizího autora, převzaté ani zavřené issue', fn() => fixture(function ($root): void {
    $idea = ['number' => 9001, 'title' => 'Nápad', 'body' => 'Prosím opravit výpočet.', 'state' => 'open', 'user' => ['login' => 'Terms4Ever']];
    foreach ([['user' => ['login' => 'jiny-autor']], ['number' => 1], ['state' => 'closed'], ['performed_via_github_app' => ['id' => 1]]] as $override) {
        $client = new FakeClient(); $client->rows = [[...$idea, ...$override]];
        fails(fn() => Issues::audit($root, $client));
        expect($client->writes() === []);
    }
}));

test('Agent nesmí použít syrový nápad jako vstup založení nebo aktualizace', function (): void {
    $idea = [...draft(), 'title' => 'Stručný nápad napsaný vlastníkem', 'body' => 'Prosím opravit výpočet.', 'user' => ['login' => 'Terms4Ever']];
    foreach (['create', 'update'] as $action) {
        $client = new FakeClient();
        fails(fn() => $action === 'create' ? Issues::create(TOOL_ROOT, $idea, $client) : Issues::update(TOOL_ROOT, 1, $idea, $client));
        expect($client->writes() === []);
    }
});

test('Citace dlouhé pomlčky projde skutečnou kontrolou dokumentace i pracovním profilem', fn() => fixture(function ($root): void {
    $quote = "\nZnak `\u{2014}` je zde citován v řádkovém kódu.\n";
    file_put_contents($root . '/docs/03-rozhodovaci-dennik.md', $quote, FILE_APPEND);
    file_put_contents($root . '/docs/ukoly/1.md', $quote, FILE_APPEND);
    file_put_contents($root . '/README.md', $quote, FILE_APPEND);
    Policy::repositoryContent($root);
    Policy::taskRecord($root, 1);
    Policy::readme($root);
    Upstream::check('kontrola-dokumentace.php', [$root]);
}));

test('Pomlčka v běžném textu, neuzavřené citaci a bloku kódu stále neprojde', fn() => fixture(function ($root): void {
    $path = $root . '/docs/ukoly/1.md'; $original = readFile($path);
    foreach (["Text \u{2014} věta.", "Neuzavřený `znak \u{2014}.", "```text\n`\u{2014}`\n```", "~~~text\n`\u{2013}`\n~~~"] as $text) {
        writeFile($path, $original . "\n" . $text . "\n");
        fails(fn() => Policy::repositoryContent($root), 'pomlčk');
    }
}));

test('Kontroly issues nemají denní plán ani v šabloně a reagují na změny', function (): void {
    foreach (['.github/workflows/issues.yml', 'sablony/project/.github/workflows/issues.yml.tpl'] as $file) {
        $text = readFile(TOOL_ROOT . '/' . $file);
        expect(!preg_match('/^\s*(schedule|cron):/m', $text), 'Denní plán zůstal v ' . $file);
        foreach (['issues:', 'issue_comment:', 'workflow_dispatch:'] as $event) { expect(str_contains($text, $event), 'Chybí ' . $event); }
    }
});

test('Připravenost main nevyžaduje ochranu ani oprávnění číst její nastavení', fn() => fixture(function ($root): void {
    $base = git($root, 'rev-parse', 'HEAD'); change($root);
    quiet(fn() => Gate::verify($root, $base, true, new FakeClient()));
    $client = new FakeClient(); $client->sha = git($root, 'rev-parse', 'HEAD');
    $client->rows = ['bug', 'enhancement', 'documentation', 'rozhrani', 'bez-rozhrani'];
    $client->handler = function ($method, $suffix): ?array {
        if (str_contains($suffix, 'protection') || str_contains($suffix, 'rules')) { throw new Failure('Ochrana není nastavená nebo dostupná.'); }
        return $suffix === '' ? ['private' => true, 'full_name' => 'Terms4Ever/nastroje-prace', 'default_branch' => 'main', 'has_issues' => true, 'description' => 'Zkušební projekt.'] : null;
    };
    Project::remoteReadiness($root, $client);
    expect($client->writes() === []);
}));
