<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;

use NastrojePrace\{Issues, Policy};
use function NastrojePrace\config;
use const NastrojePrace\TOOL_ROOT;

function visitor(array $override = []): array
{
    return [...['number' => 9001, 'title' => 'Nápad', 'body' => 'Prosím přidat další kontrolu.',
        'state' => 'open', 'user' => ['login' => 'navstevnik', 'type' => 'User'], 'author_association' => 'NONE'], ...$override];
}

test('Centrální nástroje přijmou veřejnou i původní privátní viditelnost', function (): void {
    $client = new FakeClient(); $client->topics = config(TOOL_ROOT)['topics'];
    foreach ([false, true] as $private) {
        $client->private = $private;
        expect(Policy::repositoryMetadata(TOOL_ROOT, $client)['private'] === $private, 'Výstup musí uvádět skutečnou viditelnost.');
    }
    expect($client->writes() === []);
});

test('Veřejná výjimka nepromine chybnou identitu, chybějící viditelnost ani topics', function (): void {
    foreach ([['full_name' => 'nekdo/jiny', 'private' => false], ['full_name' => 'Terms4Ever/nastroje-prace'],
        ['full_name' => 'Terms4Ever/nastroje-prace', 'private' => 'false']] as $meta) {
        $client = new FakeClient(); $client->handler = fn($method, $suffix) => $suffix === '' ? $meta : null;
        fails(fn() => Policy::repositoryMetadata(TOOL_ROOT, $client), 'identita nebo viditelnost');
    }
    $client = new FakeClient(); $client->private = false; $client->topics = [];
    fails(fn() => Policy::repositoryMetadata(TOOL_ROOT, $client), 'topics');
});

test('Aplikace zůstává privátní i když má v konfiguraci název centrálních nástrojů', fn() => fixture(function ($root): void {
    expect(!Policy::centralTools($root));
    $client = new FakeClient(); $client->private = false;
    fails(fn() => Policy::repositoryMetadata($root, $client), 'aplikační repozitář musí být privátní');
    $client->private = true;
    Policy::repositoryMetadata($root, $client);
}));

test('Veřejný nepřevzatý podnět návštěvníka nevyžaduje pracovní šablonu', function (): void {
    foreach (['open', 'closed'] as $state) {
        $client = new FakeClient(); $client->rows = [visitor(['state' => $state])];
        expect(quiet(fn() => Issues::audit(TOOL_ROOT, $client)) === 1);
        expect($client->writes() === []);
    }
});

test('Vlastník, spolupracovník a převzatý veřejný podnět zůstávají kontrolované', function (): void {
    foreach ([['number' => 1], ['user' => ['login' => 'Terms4Ever']], ['author_association' => 'COLLABORATOR'],
        ['author_association' => 'MEMBER'], ['author_association' => 'OWNER'], ['author_association' => ''],
        ['user' => ['login' => '']]] as $override) {
        $client = new FakeClient(); $client->rows = [visitor([...$override, 'body' => "## Cíl\n\nKrátké neúplné zadání."])];
        fails(fn() => Issues::audit(TOOL_ROOT, $client));
        expect($client->writes() === []);
    }
});

test('Výjimka veřejného podnětu neplatí v aplikačním repozitáři', fn() => fixture(function ($root): void {
    $client = new FakeClient(); $client->rows = [visitor()];
    fails(fn() => Issues::audit($root, $client));
}));

test('Agent nesmí použít veřejný podnět pro neúplné založení ani úpravu', function (): void {
    $draft = [...draft(), ...visitor()];
    foreach (['create', 'update'] as $action) {
        $client = new FakeClient(); $client->item = visitor();
        fails(fn() => $action === 'create' ? Issues::create(TOOL_ROOT, $draft, $client) : Issues::update(TOOL_ROOT, 9001, $draft, $client));
        expect($client->writes() === []);
    }
});

test('Komentář návštěvníka nemění pravidla komentářů vlastníka a spolupracovníků', function (): void {
    $comment = visitor(['body' => str_repeat("Delší řádek veřejného komentáře.\n", 6)]);
    $make = static function (array $comments): FakeClient {
        return new class($comments) extends FakeClient {
            public function __construct(private array $comments) {
                parent::__construct();
                $this->rows = [[...draft(), 'number' => 1, 'state' => 'open']];
            }
            public function pages(string $suffix): array {
                return str_ends_with($suffix, '/comments') ? $this->comments : parent::pages($suffix);
            }
        };
    };
    expect(quiet(fn() => Issues::audit(TOOL_ROOT, $make([$comment]))) === 1);
    foreach ([['user' => ['login' => 'Terms4Ever']], ['author_association' => 'COLLABORATOR'], ['author_association' => '']] as $override) {
        fails(fn() => Issues::audit(TOOL_ROOT, $make([[...$comment, ...$override]])));
    }
    fixture(fn($root) => fails(fn() => Issues::audit($root, $make([$comment]))));
});
