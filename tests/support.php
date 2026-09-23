<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;

use NastrojePrace\{ApiClient, Failure, Gate, Upstream};
use function NastrojePrace\{git, run, writeFile, writeJson};

const MESSAGE = "Kontrola chrání předání ověřené změny (#1)\n\nDůvod: Zachovat dohledatelnost konkrétního úkolu.\nOvěření: Prošla izolovaná sada kontrolních scénářů.\n";
const BODY = "## Cíl\n\nPředání změny vyžaduje doložené ověření a záznam úkolu.\n\n## Hotovo, když\n\n- [ ] Neověřená změna se nepředá do dalšího kroku.\n";

function draft(): array
{
    return ['title' => 'Předání změny vyžaduje doložené ověření', 'body' => BODY, 'labels' => ['enhancement', 'bez-rozhrani'], 'assignees' => ['Terms4Ever']];
}

function expect(bool $condition, string $message = 'Neočekávaný výsledek testu.'): void
{
    if (!$condition) {
        throw new \RuntimeException($message);
    }
}

function fails(callable $action, string $contains = ''): Failure
{
    try {
        $action();
    } catch (Failure $error) {
        expect($contains === '' || str_contains($error->getMessage(), $contains), 'Chyba neodpovídá očekávání: ' . $error->getMessage());
        return $error;
    }
    throw new \RuntimeException('Neplatný vstup nebyl odmítnut.');
}

function quiet(callable $action): mixed
{
    ob_start();
    try { return $action(); } finally { ob_end_clean(); }
}

function test(string $name, callable $action, bool $svn = false): void
{
    $GLOBALS['tests'][] = [$name, $action, $svn];
}

function commit(string $root, string $message = MESSAGE): string
{
    git($root, 'add', '.');
    // Pouze izolovaná zkušební kopie, nikoli skutečný projekt.
    run(['git', '-C', $root, '-c', 'core.hooksPath=', 'commit', '-m', $message]);
    return git($root, 'rev-parse', 'HEAD');
}

final class TempTree
{
    public readonly string $root;

    public function __construct()
    {
        $this->root = str_replace('\\', '/', realpath(sys_get_temp_dir())) . '/nastroje-prace-test-' . bin2hex(random_bytes(8));
        expect(mkdir($this->root), 'Nelze vytvořit izolovanou zkušební složku.');
    }

    public function remove(string $relative = ''): void
    {
        $path = $relative === '' ? $this->root : \NastrojePrace\safePath($this->root, $relative);
        $resolved = realpath($path);
        if ($resolved === false) { return; }
        $resolved = str_replace('\\', '/', $resolved);
        $tempPrefix = str_replace('\\', '/', realpath(sys_get_temp_dir())) . '/nastroje-prace-test-';
        expect(str_starts_with(strtolower($this->root), strtolower($tempPrefix)), 'Úklid mimo zkušební složku.');
        expect($resolved === $this->root || str_starts_with($resolved, $this->root . '/'), 'Cíl úklidu opustil zkušební složku.');
        self::removeTree($path);
    }

    private static function removeTree(string $path): void
    {
        if (is_dir($path) && !is_link($path)) {
            foreach (scandir($path) as $entry) {
                if ($entry !== '.' && $entry !== '..') { self::removeTree($path . '/' . $entry); }
            }
            expect(@rmdir($path), 'Nelze odstranit zkušební složku.');
        } else {
            @chmod($path, 0666);
            expect(@unlink($path), 'Nelze odstranit zkušební soubor.');
        }
    }
}

function fixture(callable $action): mixed
{
    $temp = new TempTree();
    $root = $temp->root;
    try {
        git($root, 'init', '-b', 'main');
        git($root, 'config', 'user.name', 'Zkouska');
        git($root, 'config', 'user.email', 'test@example.invalid');
        git($root, 'config', 'core.autocrlf', 'false');
        writeFile($root . '/.gitignore', ".local/\n.cache/\n");
        writeFile($root . '/AGENTS.md', "# Pravidla\n\nZměny vyžadují doklad ověření.\n");
        writeFile($root . '/CLAUDE.md', "@AGENTS.md\n");
        writeJson($root . '/.prace.json', ['version' => 1, 'repository' => 'Terms4Ever/nastroje-prace', 'topics' => ['php', 'tooling'],
            'tests' => [['{php}', '-r', 'echo "Overeno\n";']], 'svn' => ['enabled' => false, 'url' => '', 'allow' => []]]);
        writeJson($root . '/.readme-kontrola.json', ['profil' => 'plny', 'docs-kontrola' => true, 'docs-pomlcky' => 'blokovat']);
        writeJson($root . '/.tasks/1.json', ['issue' => 1, 'mantis' => null, 'technical_reason' => 'Izolované ověření společných kontrol.', 'visual' => false, 'delivery' => 'git']);
        foreach (['00-stav-projektu.md', '03-rozhodovaci-dennik.md'] as $file) {
            writeFile($root . '/docs/' . $file, "# Přehled projektu\n\nProjekt slouží k izolovanému ověření.\n");
        }
        $record = "# Úkol 1\n\n";
        foreach (['Zadání', 'Změna', 'Ověření', 'Předání'] as $heading) {
            $record .= '## ' . $heading . "\n\nKonkrétní ověřený popis dané části úkolu.\n\n";
        }
        writeFile($root . '/docs/ukoly/1.md', $record);
        $readme = "# 🧰 Zkušební projekt\n\n**Izolované ověření pravidel**\n\nProjekt pro kontrolní scénáře.\n\n![Test](https://img.shields.io/badge/test-local-blue)\n\n---\n\n";
        foreach (\NastrojePrace\Policy::README_HEADINGS as $heading) {
            $readme .= '## ' . $heading . "\n\n";
            $readme .= $heading === '📚 Dokumentace'
                ? "| Dokument | Účel |\n|---|---|\n| `docs/00-stav-projektu.md` | Současný stav. |\n| `docs/03-rozhodovaci-dennik.md` | Důvody řešení. |\n| `docs/ukoly/` | Záznamy úkolů. |\n\n"
                : "Izolované ověření bez produkčních služeb.\n\n";
        }
        writeFile($root . '/README.md', $readme);
        writeFile($root . '/src/main.txt', "původní obsah\n");
        commit($root);
        Upstream::check('stav-projektu.php', [$root, '--zapsat']);
        commit($root);
        return $action($root, $temp);
    } finally {
        $temp->remove();
    }
}

function change(string $root, bool $document = true): string
{
    writeFile($root . '/src/main.txt', "nový obsah\n");
    if ($document) { file_put_contents($root . '/docs/ukoly/1.md', "\nDoplněno ověření konkrétní provedené změny.\n", FILE_APPEND); }
    return commit($root);
}

class FakeClient implements ApiClient
{
    public array $calls = [];
    public array $rows = [];
    public array $item;
    public ?\Closure $handler = null;
    public bool $available = true;
    public bool $private = true;
    public array $topics = ['php', 'tooling'];
    public string $sha = '';
    public string $conclusion = 'success';
    public function __construct() { $this->item = [...draft(), 'number' => 1, 'state' => 'open']; }
    public function request(string $method, string $suffix, ?array $data = null): mixed
    {
        $this->calls[] = [$method, $suffix, $data];
        if (!$this->available) { throw new Failure('Nedostupné API'); }
        if ($this->handler !== null) { return ($this->handler)($method, $suffix, $data); }
        if ($suffix === '') { return ['private' => $this->private, 'full_name' => 'Terms4Ever/nastroje-prace']; }
        if ($suffix === '/topics') { return ['names' => $this->topics]; }
        if ($suffix === '/commits/main') { return ['sha' => $this->sha]; }
        if (str_contains($suffix, '/check-runs?')) { return ['check_runs' => [['id' => 1, 'name' => 'Povinne kontroly', 'app' => ['slug' => 'github-actions'], 'conclusion' => $this->conclusion]]]; }
        return [...$this->item, ...($data ?? []), 'html_url' => 'https://example.invalid/issue'];
    }
    public function pages(string $suffix): array
    {
        $this->calls[] = ['PAGES', $suffix];
        if (!$this->available) { throw new Failure('Nedostupné API'); }
        return $this->rows;
    }
    public function issue(int $number): array
    {
        $this->calls[] = ['ISSUE', $number];
        if (!$this->available) { throw new Failure('Nedostupné API'); }
        return [...$this->item, 'number' => $number];
    }
    public function writes(): array { return array_values(array_filter($this->calls, fn($c) => in_array($c[0], ['POST', 'PATCH', 'PUT', 'DELETE'], true))); }
}
