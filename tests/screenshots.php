<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;

use NastrojePrace\{Cli, Failure, Gate, Issues, Policy, ScreenshotException};
use function NastrojePrace\{git, readFile, readJson, writeFile, writeJson};

function photoApproval(FakeClient $client, array $approval): void
{
    $client->handler = static fn($method, $suffix): ?array => $suffix === '/issues/comments/42' ? $approval : null;
}

function photoFixture(string $root, string $missing = 'pred'): array
{
    $base = git($root, 'rev-parse', 'HEAD');
    $folder = 'docs/snimky/1-detail/';
    $paths = $available = [];
    foreach (['pred', 'po'] as $kind) {
        $path = $folder . $kind . '-formular.png';
        if ($missing === 'both' || $missing === $kind) { $paths[] = $path; }
        else {
            // Syntetické podpisy pouze pro izolované testy, nikdy důkaz aplikace.
            writeFile($root . '/' . $path, "\x89PNG\r\n\x1a\n\0fixture-" . $kind);
            $available[] = $path;
        }
    }
    writeFile($root . '/docs/prilohy/1-pokus.txt', "Izolovaný záznam: testovací služba odmítla spojení při pokusu o načtení obrazovky.\n");
    $meta = readJson($root . '/.tasks/1.json');
    $meta['visual'] = true;
    $meta['screenshot_exception'] = ['missing' => $paths,
        'reason' => 'Původní testovací služba je vypnutá a nelze načíst požadovaný pohled.',
        'attempt' => 'Ověřeno spuštění služby a opakované otevření pohledu; spojení bylo odmítnuto.',
        'evidence' => ['docs/prilohy/1-pokus.txt'], 'approval_comment' => 42];
    writeJson($root . '/.tasks/1.json', $meta);
    file_put_contents($root . '/docs/ukoly/1.md', "\nDoložen pokus o získání konkrétního snímku.\n", FILE_APPEND);
    git($root, 'add', '.');
    $request = ScreenshotException::request($root, 1);
    $sha = commit($root);
    $url = 'https://github.com/Terms4Ever/nastroje-prace/issues/1#issuecomment-42';
    $approval = ['id' => 42, 'html_url' => $url, 'user' => ['login' => 'Terms4Ever', 'type' => 'User'],
        'performed_via_github_app' => null, 'body' => $request['approval_text']];
    $client = new FakeClient();
    $client->sha = $sha;
    $client->item['labels'] = ['enhancement', 'rozhrani'];
    $client->item['body'] = str_replace('[ ]', '[x]', BODY) . "\n## Snímky\n\nDoložená výjimka: [souhlas vlastníka](" . $url . ").\n";
    foreach ($available as $path) {
        $client->item['body'] .= '![Ověřený pohled](https://github.com/Terms4Ever/nastroje-prace/blob/' . $sha . '/' . $path . "?raw=true)\n";
    }
    photoApproval($client, $approval);
    return [$client, $meta, $approval, $base];
}

test('Vlastník může schválit konkrétní chybějící snímek před, po nebo oba', fn() => fixture(function ($root, $temp): void {
    foreach (['pred', 'po', 'both'] as $missing) {
        $temp->remove('docs/snimky');
        [$client] = photoFixture($root, $missing);
        Policy::issue($root, $client->item, true, $client);
        expect($client->writes() === []);
    }
}));

test('Štítek ani žádost bez výslovného souhlasu nenahrazují snímek', fn() => fixture(function ($root): void {
    [$client, $meta] = photoFixture($root);
    $client->item['labels'][] = 'bez snímku po';
    $plain = $meta; unset($plain['screenshot_exception']); writeJson($root . '/.tasks/1.json', $plain);
    fails(fn() => Policy::issue($root, $client->item, true, $client), 'dvojice');
    unset($meta['screenshot_exception']['approval_comment']); writeJson($root . '/.tasks/1.json', $meta);
    fails(fn() => Policy::issue($root, $client->item, true, $client), 'výslovné schválení');
    expect($client->writes() === []);
}));

test('Žádost potřebuje konkrétní překážku, pokus a existující verzovaný důkaz', fn() => fixture(function ($root): void {
    [, $meta] = photoFixture($root);
    foreach (['reason' => '', 'attempt' => 'Nejde to', 'evidence' => [], 'missing' => []] as $key => $value) {
        $bad = $meta; $bad['screenshot_exception'][$key] = $value; writeJson($root . '/.tasks/1.json', $bad);
        fails(fn() => Policy::taskRecord($root, 1));
    }
    foreach (['docs/prilohy/chybi.txt', '../mimo.txt', '.local/pokus.txt'] as $path) {
        $bad = $meta; $bad['screenshot_exception']['evidence'] = [$path]; writeJson($root . '/.tasks/1.json', $bad);
        fails(fn() => ScreenshotException::request($root, 1));
    }
    writeJson($root . '/.tasks/1.json', $meta);
    writeFile($root . '/docs/prilohy/1-pokus.txt', " \n");
    fails(fn() => ScreenshotException::request($root, 1), 'Prázdný');
}));

test('Souhlas nesmí patřit cizímu účtu, aplikaci, jinému issue ani jinému znění', fn() => fixture(function ($root): void {
    [$client, , $approval] = photoFixture($root);
    foreach ([['user' => ['login' => 'Kolega', 'type' => 'User']], ['user' => ['login' => 'Terms4Ever', 'type' => 'Bot']],
        ['performed_via_github_app' => ['id' => 1]], ['html_url' => str_replace('/issues/1', '/issues/2', $approval['html_url'])],
        ['id' => 43], ['body' => 'Výjimku nyní neschvaluji.'], ['body' => "Citace rozhodnutí:\n" . $approval['body']]] as $change) {
        photoApproval($client, [...$approval, ...$change]);
        fails(fn() => Policy::issue($root, $client->item, true, $client));
    }
    photoApproval($client, $approval);
    $client->item['body'] = str_replace($approval['html_url'], 'https://example.invalid/souhlas', $client->item['body']);
    fails(fn() => Policy::issue($root, $client->item, true, $client), 'Sekce Snímky');
}));

test('Změna překážky, pokusu, rozsahu nebo obsahu důkazu zneplatní starý souhlas', fn() => fixture(function ($root): void {
    [$client, $meta] = photoFixture($root);
    foreach (['reason' => 'Nová nesouvisející překážka vyžaduje nové rozhodnutí vlastníka.',
        'attempt' => 'Nový pokus má jiný výsledek než původně schválený záznam.',
        'missing' => ['docs/snimky/1-jiny/pred-formular.png']] as $key => $value) {
        $changed = $meta; $changed['screenshot_exception'][$key] = $value; writeJson($root . '/.tasks/1.json', $changed);
        fails(fn() => Policy::issue($root, $client->item, true, $client), 'aktuálním podkladům');
    }
    writeJson($root . '/.tasks/1.json', $meta);
    writeFile($root . '/docs/prilohy/1-pokus.txt', 'Jiný výsledek provedeného pokusu.');
    fails(fn() => ScreenshotException::request($root, 1), 'po přidání do Gitu');
    git($root, 'add', '.');
    fails(fn() => Policy::issue($root, $client->item, true, $client), 'aktuálním podkladům');
}));

test('Výjimka nepromine chybějící dostupný protějšek ani chybný odkaz na snímek', fn() => fixture(function ($root): void {
    [$client] = photoFixture($root);
    $original = $client->item['body'];
    $client->item['body'] = preg_replace('/!\[[^\]]*\]\([^)]*\)/', '', $original);
    fails(fn() => Policy::issue($root, $client->item, true, $client), 'dvojice');
    $client->item['body'] = str_replace('/blob/' . $client->sha, '/blob/main', $original);
    fails(fn() => Policy::issue($root, $client->item, true, $client), 'neměnný Git commit');
    $client->item['body'] = $original;
    writeFile($root . '/docs/snimky/1-detail/po-formular.png', 'Toto není obrázek.');
    fails(fn() => Policy::issue($root, $client->item, true, $client), 'PNG/JPEG/WebP');
}));

test('Existující snímek ani nevizuální úkol nemohou použít výjimku', fn() => fixture(function ($root): void {
    [$client, $meta] = photoFixture($root);
    $client->item['labels'] = ['enhancement', 'bez-rozhrani'];
    fails(fn() => Policy::issue($root, $client->item, true, $client), 'Nevizuální');
    $invalid = $meta; $invalid['visual'] = false; writeJson($root . '/.tasks/1.json', $invalid);
    fails(fn() => Policy::taskRecord($root, 1), 'vizuálnímu');
    writeJson($root . '/.tasks/1.json', $meta);
    writeFile($root . '/docs/snimky/1-detail/pred-formular.png', "\x89PNG\r\n\x1a\n\0fixture");
    fails(fn() => ScreenshotException::request($root, 1), 'Snímek existuje');
}));

test('Nedostupné API a smazaný souhlas zastaví výjimku', fn() => fixture(function ($root): void {
    [$client] = photoFixture($root);
    fails(fn() => Policy::issue($root, $client->item, true), 'živé ověření');
    $client->available = false;
    fails(fn() => Policy::issue($root, $client->item, true, $client), 'Nedostupné API');
    $client->available = true;
    $client->handler = static function ($method, $suffix): ?array {
        if ($suffix === '/issues/comments/42') { throw new Failure('Komentář neexistuje: HTTP 404.'); }
        return null;
    };
    fails(fn() => Policy::issue($root, $client->item, true, $client), '404');
}));

test('Stejné názvy v různých pohledech netvoří pár a výjimka nepromine shodné obrázky', fn() => fixture(function ($root): void {
    [$client] = photoFixture($root);
    foreach (['pred', 'po'] as $kind) {
        writeFile($root . '/docs/snimky/1-jiny/' . $kind . '-formular.png', "\x89PNG\r\n\x1a\n\0stejny");
    }
    $sha = commit($root);
    $before = '![Před](https://github.com/Terms4Ever/nastroje-prace/blob/' . $sha . '/docs/snimky/1-jiny/pred-formular.png)';
    $after = '![Po](https://github.com/Terms4Ever/nastroje-prace/blob/' . $sha . '/docs/snimky/1-jiny/po-formular.png)';
    $body = $client->item['body'];
    $client->item['body'] = $body . $after;
    fails(fn() => Policy::issue($root, $client->item, true, $client), 'dvojice');
    $client->item['body'] = $body . $before . "\n" . $after;
    fails(fn() => Policy::issue($root, $client->item, true, $client), 'totožný');
}));

test('Příkaz žádosti jen připraví podklady a nezapíše schválení', fn() => fixture(function ($root): void {
    [, $meta] = photoFixture($root);
    unset($meta['screenshot_exception']['approval_comment']); writeJson($root . '/.tasks/1.json', $meta);
    $before = readFile($root . '/.tasks/1.json');
    ob_start();
    try { Cli::main(['--root', $root, 'snimky-zadost', '1']); $output = ob_get_contents(); }
    finally { ob_end_clean(); }
    $request = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    expect($request['missing'] === ['docs/snimky/1-detail/pred-formular.png']);
    expect(str_contains($request['approval_text'], $request['hash']));
    expect(readFile($root . '/.tasks/1.json') === $before);
}));

test('Souhlas používá obsah Gitu i při jiných místních koncích řádků', fn() => fixture(function ($root): void {
    [$client] = photoFixture($root);
    $request = ScreenshotException::request($root, 1);
    writeFile($root . '/.gitattributes', "docs/prilohy/* text eol=lf\n");
    $path = $root . '/docs/prilohy/1-pokus.txt';
    writeFile($path, str_replace("\n", "\r\n", readFile($path)));
    git($root, 'add', '.');
    expect(ScreenshotException::request($root, 1)['hash'] === $request['hash']);
    Policy::issue($root, $client->item, true, $client);
}));

test('Dokončení znovu čte souhlas a při jeho změně nic nezapisuje', fn() => fixture(function ($root): void {
    [$client, , $approval, $base] = photoFixture($root);
    quiet(fn() => Gate::verify($root, $base, true, $client));
    photoApproval($client, [...$approval, 'body' => 'Souhlas s touto výjimkou odvolávám.']);
    fails(fn() => Issues::close($root, 1, 'Dokončené ověření obsahuje schválenou výjimku snímku.', $client), 'aktuálním podkladům');
    expect($client->writes() === []);
    photoApproval($client, $approval);
    expect(Issues::close($root, 1, 'Dokončené ověření obsahuje schválenou výjimku snímku.', $client)['state'] === 'closed');
    expect(count($client->writes()) === 2);
    $client->item['state'] = 'closed';
    expect(Issues::audit($root, $client, 1) === 1);
    Issues::update($root, 1, $client->item, $client);
}));
