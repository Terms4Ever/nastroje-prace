<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;

use NastrojePrace\{Cli, Failure, GitHub, Issues, Policy, Upstream};
use function NastrojePrace\{git, readFile, readJson, run, safePath, writeFile, writeJson};
use const NastrojePrace\TOOL_ROOT;

test('Správné issue používá skutečný společný PHP validátor', fn() => Policy::issue(TOOL_ROOT, draft()));
test('Neuspořádaný návrh issue je odmítnut', function (): void {
    $item = draft(); $item['body'] = 'Prosím něco upravit v aplikaci.';
    fails(fn() => Policy::issue(TOOL_ROOT, $item));
});
test('Chybějící odpovědný nebo druh issue je odmítnut', function (): void {
    foreach (['labels', 'assignees'] as $key) {
        $item = draft(); $item[$key] = [];
        fails(fn() => Policy::issue(TOOL_ROOT, $item));
    }
});
test('Dlouhá pomlčka v názvu i těle issue je odmítnuta', function (): void {
    foreach (['title', 'body'] as $key) {
        $item = draft(); $item[$key] .= "\u{2014} nový obsah";
        fails(fn() => Policy::issue(TOOL_ROOT, $item));
    }
});
test('Neznámá sekce a zástupný checklist jsou odmítnuty', function (): void {
    foreach (["\n## Něco navíc\nDalší obsah.", "\n- [ ] TODO"] as $extra) {
        $item = draft(); $item['body'] .= $extra;
        fails(fn() => Policy::issue(TOOL_ROOT, $item));
    }
});
test('Příliš dlouhé issue je odmítnuto', function (): void {
    $item = draft(); $item['body'] .= str_repeat("\nDalší konkrétní řádek.", 45);
    fails(fn() => Policy::issue(TOOL_ROOT, $item));
});
test('Nesplněný checklist brání dokončení issue', function (): void {
    $item = [...draft(), 'number' => 1];
    fails(fn() => Policy::issue(TOOL_ROOT, $item, true));
});
test('Krátký komentář projde, šest řádků neprojde', function (): void {
    Policy::comment('Ověření potvrdilo očekávaný výsledek.');
    fails(fn() => Policy::comment(str_repeat("Ověření potvrdilo výsledek.\n", 6)));
});
test('Neplatný návrh issue vůbec nezavolá GitHub', function (): void {
    $client = new FakeClient(); $item = draft(); $item['body'] = '';
    fails(fn() => Issues::create(TOOL_ROOT, $item, $client));
    expect($client->calls === []);
});
test('Duplicitní otevřené issue se nezaloží', function (): void {
    $client = new FakeClient(); $client->rows = [draft()];
    fails(fn() => Issues::create(TOOL_ROOT, draft(), $client));
    expect($client->writes() === []);
});
test('Platné založení posílá jen povolená strukturovaná pole', function (): void {
    $client = new FakeClient();
    Issues::create(TOOL_ROOT, [...draft(), 'state' => 'closed'], $client);
    expect($client->writes() === [['POST', '/issues', draft()]]);
});
test('Úprava issue nepřijme skryté uzavření ve vstupu', function (): void {
    $client = new FakeClient();
    Issues::update(TOOL_ROOT, 1, [...draft(), 'state' => 'closed'], $client);
    expect($client->writes() === [['PATCH', '/issues/1', draft()]]);
});
test('Nedostupné API neznamená úspěšný audit', function (): void {
    $client = new FakeClient(); $client->available = false;
    fails(fn() => Issues::audit(TOOL_ROOT, $client));
});
test('Commit vyžaduje issue, důvod a ověření', function (): void {
    expect(Policy::commit(MESSAGE) === 1);
    foreach (['Oprava', str_replace(' (#1)', '', MESSAGE), str_replace('Ověření:', 'Poznámka:', MESSAGE), MESSAGE . "\nCloses #1"] as $bad) {
        fails(fn() => Policy::commit($bad));
    }
});
test('CLI odmítá chybějící a neznámé parametry', function (): void {
    expect(Cli::parse(['check', '--base', 'HEAD^', '--online'])[3] === ['base' => 'HEAD^', 'online' => true]);
    foreach ([['check'], ['check', '--base'], ['doctor', '--unknown'], ['issue-close', '1'], ['check', '--base', 'HEAD', '--base', 'HEAD^']] as $args) {
        fails(fn() => Cli::parse($args));
    }
});
test('PHP proces zachová Unicode, argumenty shellu a nenulový kód', function (): void {
    $argument = 'český text "uvozovky" $(text) `příkaz` & text';
    $result = run([PHP_BINARY, '-r', 'echo $argv[1]; fwrite(STDERR, "chyba"); exit(7);', $argument], check: false);
    expect($result === ['code' => 7, 'stdout' => $argument, 'stderr' => 'chyba']);
});
test('Velký výstup procesu nezablokuje Windows', function (): void {
    $result = run([PHP_BINARY, '-r', 'echo str_repeat("a", 200000); fwrite(STDERR, str_repeat("b", 200000));']);
    expect(strlen($result['stdout']) === 200000 && strlen($result['stderr']) === 200000);
});
test('Stránkování GitHubu kontroluje i druhou stránku', function (): void {
    $client = new class('Terms4Ever/nastroje-prace') extends GitHub {
        public int $count = 0;
        public function request(string $method, string $suffix, ?array $data = null): mixed {
            $this->count++;
            return $this->count === 1 ? array_fill(0, 100, ['id' => 1]) : [['id' => 101]];
        }
    };
    expect(count($client->pages('/issues?state=all')) === 101 && $client->count === 2);
});
test('API odmítne odkaz mimo repozitář před čtením přihlášení', function (): void {
    $client = new GitHub('Terms4Ever/nastroje-prace');
    foreach (['https://example.invalid', '//example.invalid', '/../other', '/%2E%2E/other'] as $path) {
        fails(fn() => $client->request('GET', $path), 'cesta GitHub API');
    }
});

test('Chybějící dokumentace je chyba', fn() => fixture(function ($root, $temp): void {
    $temp->remove('docs');
    fails(fn() => Policy::repositoryContent($root), 'docs');
}));
test('Zachycený token se nevypíše do diagnostiky', fn() => fixture(function ($root): void {
    $credential = 'gh' . 'p_' . str_repeat('a', 40);
    writeFile($root . '/credentials.txt', $credential); git($root, 'add', '.');
    $error = fails(fn() => Policy::repositoryContent($root));
    expect(!str_contains($error->getMessage(), $credential));
}));
test('Profil prohlížeče nesmí do Gitu', fn() => fixture(function ($root): void {
    writeFile($root . '/tests/asc-profile/History', 'example'); git($root, 'add', '.');
    fails(fn() => Policy::repositoryContent($root));
}));
test('Soubor lokálního prostředí nesmí do Gitu', fn() => fixture(function ($root): void {
    writeFile($root . '/.env.production', 'RUNTIME=production'); git($root, 'add', '.');
    fails(fn() => Policy::repositoryContent($root), 'Lokální prostředí');
}));
test('Dokumentaci nelze vypnout změnou profilu', fn() => fixture(function ($root): void {
    $data = readJson($root . '/.readme-kontrola.json'); $data['docs-kontrola'] = false;
    writeJson($root . '/.readme-kontrola.json', $data);
    fails(fn() => Policy::repositoryContent($root), 'zapnutou dokumentaci');
}));
test('Cesty mimo projekt a do metadat jsou odmítnuty', fn() => fixture(function ($root): void {
    foreach (['../secret', '/outside', 'C:/outside', '.git/config', '.svn/wc.db', 'src/../../file', '.GIT/config', 'src/./file'] as $path) {
        fails(fn() => safePath($root, $path));
    }
}));
test('Snímky vyžadují páry, správné issue a obsah konkrétního commitu', fn() => fixture(function ($root): void {
    $folder = $root . '/docs/snimky/1-zkouska';
    // Výhradně syntetické testovací podpisy, nikoli důkazy z pracovní aplikace.
    writeFile($folder . '/pred-formular.png', "\x89PNG\r\n\x1a\nfixture-before");
    writeFile($folder . '/po-formular.png', "\x89PNG\r\n\x1a\nfixture-after");
    $sha = commit($root);
    $prefix = 'https://github.com/Terms4Ever/nastroje-prace/blob/' . $sha . '/docs/snimky/1-zkouska/';
    $before = '![Před](' . $prefix . 'pred-formular.png?raw=true)';
    $after = '![Po](' . $prefix . 'po-formular.png?raw=true)';
    Policy::pictures($root, 1, $before . "\n" . $after, true, 'Terms4Ever/nastroje-prace');
    foreach ([$before, $after, '', str_replace('/1-zkouska/', '/2-zkouska/', $before . $after)] as $body) {
        fails(fn() => Policy::pictures($root, 1, $body, true, 'Terms4Ever/nastroje-prace'));
    }
    writeFile($folder . '/po-formular.png', 'changed');
    fails(fn() => Policy::pictures($root, 1, $before . $after, true, 'Terms4Ever/nastroje-prace'));
}));
test('Textový odkaz nenahrazuje snímek', fn() => fails(fn() => Policy::pictures(TOOL_ROOT, 1, '[Po](https://example.invalid/po.png)', true, 'Terms4Ever/nastroje-prace')));
test('Nevizuální úkol nepotřebuje snímky', fn() => Policy::pictures(TOOL_ROOT, 1, BODY, false, 'Terms4Ever/nastroje-prace'));

test('README projde se stejným pořadím jako společné nastroje', fn() => fixture(fn($root) => Policy::readme($root)));
test('Dokumentace za Nasazením je odmítnuta i když upstream projde', fn() => fixture(function ($root): void {
    $text = readFile($root . '/README.md');
    preg_match('/## 📚 Dokumentace\n.*?(?=## 🚀 Instalace)/su', $text, $match);
    $text = str_replace($match[0], '', $text);
    $text = str_replace('## 📄 Licence', $match[0] . '## 📄 Licence', $text);
    writeFile($root . '/README.md', $text);
    Upstream::check('kontrola-readme.php', [$root]);
    fails(fn() => Policy::readme($root), 'jednotné pořadí');
}));
test('README vyžaduje dokumentaci v tabulce', fn() => fixture(function ($root): void {
    $text = preg_replace('/^\| `docs\/00-stav-projektu\.md` \|.*$/m', 'Současný stav projektu.', readFile($root . '/README.md'));
    writeFile($root . '/README.md', $text);
    fails(fn() => Policy::readme($root));
}));
test('Zdvojená sekce README neprojde', fn() => fixture(function ($root): void {
    file_put_contents($root . '/README.md', "\n## 📚 Dokumentace\n\nNeplatná druhá sekce.\n", FILE_APPEND);
    fails(fn() => Policy::readme($root));
}));
test('Správné privátní GitHub metadata projdou', fn() => fixture(fn($root) => Policy::repositoryMetadata($root, new FakeClient())));
test('Chybějící topics a veřejný repozitář jsou odmítnuty', fn() => fixture(function ($root): void {
    $client = new FakeClient(); $client->topics = [];
    fails(fn() => Policy::repositoryMetadata($root, $client), 'topics');
    $client->topics = ['php', 'tooling', 'pravidla-nastroje-prace']; $client->private = false;
    fails(fn() => Policy::repositoryMetadata($root, $client), 'privátní');
}));
test('Instalace hooků jiný repozitář nepřepíše', fn() => fixture(function ($root): void {
    $before = readFile($root . '/.git/config');
    fails(fn() => Cli::main(['--root', $root, 'install-hooks']), 'pouze do repozitáře');
    expect(readFile($root . '/.git/config') === $before);
}));
