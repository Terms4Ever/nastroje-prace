<?php
declare(strict_types=1);

namespace NastrojePrace;

final class Policy
{
    public const README_HEADINGS = ['✨ Hlavní funkce', '🛠️ Tech Stack', '📁 Struktura projektu',
        '📚 Dokumentace', '🚀 Instalace (lokální vývoj)', '📦 Nasazení', '📄 Licence'];

    public static function names(array $values): array
    {
        return array_map(fn($value) => is_array($value) ? $value['name'] : $value, $values);
    }

    public static function sections(string $body): array
    {
        preg_match_all('/^## (.+)$/m', $body, $matches, PREG_OFFSET_CAPTURE);
        $result = [];
        foreach ($matches[0] as $i => [$heading, $start]) {
            $end = $matches[0][$i + 1][1] ?? strlen($body);
            $result[trim($matches[1][$i][0])] = trim(substr($body, $start + strlen($heading), $end - $start - strlen($heading)));
        }
        return $result;
    }

    public static function issue(string $root, array $item, ?bool $closed = null): void
    {
        $title = $item['title'] ?? '';
        $body = $item['body'] ?? '';
        $labels = self::names($item['labels'] ?? []);
        $assignees = array_map(fn($a) => is_array($a) ? $a['login'] : $a, $item['assignees'] ?? []);
        $closed ??= ($item['state'] ?? '') === 'closed';
        meaningful($title, 'Název issue', 15);
        ensure(mb_strlen($title, 'UTF-8') <= 100 && !preg_match('/[\r\n]/', $title), 'Název issue musí mít jeden řádek do 100 znaků.');
        noLongDash($title, 'Název issue');
        ensure(!preg_match('/^(opravy|úpravy|aktualizace|fix|update)( systému| projektu)?$/iu', trim($title)), 'Název issue je příliš obecný.');
        ensure(count(array_intersect(array_unique($labels), ['rozhrani', 'bez-rozhrani'])) === 1, 'Issue musí mít právě jeden štítek rozhrani / bez-rozhrani.');
        $args = ['-', '--prisne', '--titulek', $title, '--stitky', implode(',', $labels), '--odpovedni', implode(',', $assignees)];
        if ($closed) {
            $args[] = '--zavrene';
        }
        Upstream::check('kontrola-tvaru-issue.php', $args, $body);
        $parts = self::sections($body);
        meaningful($parts['Problém'] ?? $parts['Cíl'] ?? '', 'Zadání issue');
        preg_match_all('/^\s*- \[[ xX]\]\s*(.*)$/m', $body, $tasks);
        foreach ($tasks[1] as $task) {
            meaningful($task, 'Podmínka dokončení');
        }
        if ($closed) {
            ensure(is_int($item['number'] ?? null) && $item['number'] > 0, 'Uzavřené issue musí mít číslo.');
            self::pictures($root, $item['number'], $body, in_array('rozhrani', $labels, true), config($root)['repository']);
        }
    }

    public static function pictures(string $root, int $number, string $body, bool $visual, string $repository): void
    {
        preg_match_all('/!\[[^\]]*\]\(([^\s)]+)\)/', $body, $images);
        $before = $after = [];
        foreach ($images[1] as $url) {
            $parsed = parse_url($url);
            ensure(($parsed['scheme'] ?? '') === 'https' && ($parsed['host'] ?? '') === 'github.com' && !isset($parsed['user']), 'Snímky musí být uložené v tomto GitHub repozitáři.');
            $pattern = '~^/' . preg_quote($repository, '~') . '/blob/([0-9a-f]{40})/(docs/snimky/' . $number . '-[^/]+/(pred|po)-([^/]+))$~D';
            ensure((bool) preg_match($pattern, rawurldecode($parsed['path'] ?? ''), $matches), 'Snímek musí mít správné issue, cestu a neměnný Git commit.');
            [, $sha, $path, $kind, $suffix] = $matches;
            $file = safePath($root, $path);
            ensure(is_file($file), 'Snímek z issue v projektu neexistuje.');
            $data = readFile($file);
            $valid = str_starts_with($data, "\x89PNG\r\n\x1a\n") || str_starts_with($data, "\xff\xd8\xff") ||
                (substr($data, 0, 4) === 'RIFF' && substr($data, 8, 4) === 'WEBP');
            ensure($valid, 'Soubor snímku není rozpoznaný PNG/JPEG/WebP.');
            ensure(run(['git', '-C', $root, 'show', $sha . ':' . $path])['stdout'] === $data, 'Odkaz na snímek neodpovídá souboru v ověřované verzi.');
            if ($kind === 'pred') {
                $before[$suffix] = digest($data);
            } else {
                $after[$suffix] = digest($data);
            }
        }
        if ($visual) {
            ksort($before);
            ksort($after);
            ensure($before !== [] && array_keys($before) === array_keys($after), 'Chybí úplné dvojice snímků před a po.');
            foreach ($before as $key => $hash) {
                ensure($hash !== $after[$key], 'Snímek před a po nesmí být totožný soubor.');
            }
        }
    }

    public static function comment(string $text): void
    {
        meaningful($text, 'Komentář', 5);
        Upstream::check('kontrola-tvaru-issue.php', ['-', '--komentar'], $text);
    }

    public static function commit(string $message): int
    {
        $lines = preg_split('/\R/u', trim($message));
        noLongDash($message, 'Commit');
        ensure((bool) preg_match('/^.{15,100} \(#([1-9][0-9]*)\)$/uD', $lines[0] ?? '', $matches), 'Nadpis commitu má být konkrétní česká věta zakončená (#číslo).');
        ensure(!preg_match('/^(fix|feat|chore|update|opravy|úpravy)[:! ]/iu', $lines[0]), 'Commit má popisovat konkrétní výsledný stav.');
        ensure(!preg_match('/\b(close[sd]?|fix(?:e[sd])?|resolve[sd]?)\s+(?:#\d|https:\/\/github\.com\/)/iu', $message), 'Commit nesmí automaticky uzavírat issue bez kontroly dokončení.');
        foreach (['Důvod', 'Ověření'] as $section) {
            ensure((bool) preg_match('/^' . $section . ':\h*(.+)$/mu', $message, $part), 'Commit nemá řádek ' . $section . ':');
            meaningful($part[1], 'Commit ' . $section);
        }
        return (int) $matches[1];
    }

    public static function taskRecord(string $root, int $number): array
    {
        $meta = readJson($root . '/.tasks/' . $number . '.json');
        ensure(($meta['issue'] ?? null) === $number, 'Nesedí číslo v záznamu úkolu.');
        $mantis = $meta['mantis'] ?? null;
        if ($mantis === null) {
            meaningful($meta['technical_reason'] ?? '', 'Důvod technického úkolu bez Mantis');
        } else {
            ensure(is_int($mantis) && $mantis > 0, 'Neplatné Mantis ID.');
        }
        ensure(is_bool($meta['visual'] ?? null), 'Záznam musí určit, zda mění rozhraní.');
        ensure(in_array($meta['delivery'] ?? '', ['git', 'svn'], true), 'Neznámý způsob předání.');
        $path = $root . '/docs/ukoly/' . $number . '.md';
        ensure(is_file($path), 'Chybí textový záznam úkolu.');
        $text = readFile($path);
        noLongDash($text, 'Záznam úkolu');
        $parts = self::sections($text);
        foreach (['Zadání', 'Změna', 'Ověření', 'Předání'] as $section) {
            meaningful($parts[$section] ?? '', 'Záznam ' . $section);
        }
        return $meta;
    }

    /** Sdílený validátor kontroluje obsah. Zde se navíc hlídá Dokumentace ve stejném pořadí jako v nastroje. */
    public static function readme(string $root): void
    {
        Upstream::check('kontrola-readme.php', [$root]);
        $headings = [];
        $fence = null;
        foreach (preg_split('/\R/u', readFile($root . '/README.md')) as $line) {
            if ($fence !== null) {
                if (preg_match('/^ {0,3}' . preg_quote($fence[0], '/') . '{' . strlen($fence) . ',}\s*$/', $line)) {
                    $fence = null;
                }
                continue;
            }
            if (preg_match('/^ {0,3}(`{3,}|~{3,})/', $line, $match)) {
                $fence = $match[1];
                continue;
            }
            if (preg_match('/^## (.+)$/u', $line, $match) && in_array(trim($match[1]), self::README_HEADINGS, true)) {
                $headings[] = trim($match[1]);
            }
        }
        ensure($headings === self::README_HEADINGS, 'README musí mít jednotné pořadí: Hlavní funkce, Tech Stack, Struktura projektu, Dokumentace, Instalace, Nasazení, Licence.');
        $documentation = self::sections(readFile($root . '/README.md'))['📚 Dokumentace'] ?? '';
        ensure(!preg_match('~docs/ukoly/[0-9]+\.md~u', $documentation), 'README odkazuje na složku docs/ukoly/, nikoli na každý jednotlivý úkol.');
    }

    public static function repositoryMetadata(string $root, ApiClient $client): void
    {
        $settings = config($root);
        $meta = $client->request('GET', '');
        ensure(($meta['private'] ?? false) === true && ($meta['full_name'] ?? '') === $settings['repository'], 'Pracovní repozitář musí být správný privátní repozitář.');
        $expected = $settings['topics'] ?? [];
        ensure(is_array($expected) && $expected !== [], 'Chybí očekávané GitHub topics v .prace.json.');
        $topics = $client->request('GET', '/topics');
        ensure(array_diff($expected, $topics['names'] ?? []) === [], 'Na GitHubu chybí některé povinné topics.');
    }

    public static function repositoryContent(string $root): void
    {
        ensure(is_dir($root . '/docs'), 'Povinná složka docs/ neexistuje.');
        $docConfig = readJson($root . '/.readme-kontrola.json');
        ensure(($docConfig['docs-kontrola'] ?? null) === true && ($docConfig['docs-pomlcky'] ?? '') === 'blokovat' && ($docConfig['profil'] ?? '') === 'plny',
            'Pracovní profil vyžaduje zapnutou dokumentaci, plný README a kontrolu krátkých pomlček.');
        ensure(is_file($root . '/AGENTS.md'), 'Chybí AGENTS.md.');
        ensure(trim(readFile($root . '/CLAUDE.md')) === '@AGENTS.md', 'CLAUDE.md musí odkazovat na AGENTS.md.');
        foreach (tracked($root) as $file) {
            $path = safePath($root, $file);
            ensure(is_file($path), 'Verzovaný soubor neexistuje: ' . $file);
            $parts = explode('/', $file);
            $forbidden = ['.env', '.local', '.cache', 'node_modules', 'asc-profile', '__pycache__', 'backups'];
            foreach ($parts as $part) {
                ensure(!str_starts_with($part, '.env.') || $part === '.env.example', 'Lokální prostředí nesmí být verzované: ' . $file);
            }
            ensure(array_intersect($parts, $forbidden) === [] && !preg_match('/\.(sqlite3?|db|dump|pfx|p12|local\.json)$/i', $file), 'Nechtěný nebo lokální soubor v Gitu: ' . $file);
            ensure(filesize($path) <= 10 * 1024 * 1024, 'Soubor přesahuje limit 10 MiB: ' . $file);
            $text = readFile($path);
            if (str_contains($text, "\0")) {
                continue;
            }
            ensure(mb_check_encoding($text, 'UTF-8'), 'Text není UTF-8: ' . $file);
            if (str_ends_with($file, '.md')) {
                noLongDash($text, $file);
            }
            $patterns = [
                '/gh[pousr]_[A-Za-z0-9]{30,}/', '/github_pat_[A-Za-z0-9_]{40,}/',
                '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
                '/^\s*["\']?(?:password|passwd|api_key|secret|token)["\']?\s*[:=]\s*["\']?(?!\$|<|example|dummy|test|\{)[A-Za-z0-9\/+_=.-]{12,}/im',
            ];
            foreach ($patterns as $pattern) {
                ensure(!preg_match($pattern, $text), 'Možné přihlašovací údaje v souboru: ' . $file);
            }
        }
    }
}
