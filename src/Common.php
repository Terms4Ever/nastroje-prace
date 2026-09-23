<?php
declare(strict_types=1);

namespace NastrojePrace;

class Failure extends \RuntimeException {}

function ensure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new Failure($message);
    }
}

/** Argumenty se nikdy neskládají do příkazu shellu. Dočasné proudy brání ucpání výstupu na Windows. */
function run(array $args, ?string $cwd = null, string $input = '', int $timeout = 120, bool $check = true): array
{
    ensure($args !== [], 'Chybí spouštěný příkaz.');
    $streams = [tmpfile(), tmpfile(), tmpfile()];
    ensure(!in_array(false, $streams, true), 'Nelze vytvořit dočasné proudy procesu.');
    $process = null;
    try {
        fwrite($streams[0], $input);
        rewind($streams[0]);
        $process = @proc_open(array_map('strval', $args), $streams, $pipes, $cwd, null, ['bypass_shell' => true]);
        ensure(is_resource($process), 'Nelze spustit ' . basename((string) $args[0]) . '.');
        $start = microtime(true);
        do {
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            if (microtime(true) - $start > $timeout) {
                proc_terminate($process);
                throw new Failure('Vypršel limit příkazu ' . basename((string) $args[0]) . '.');
            }
            usleep(10000);
        } while (true);
        $closedCode = proc_close($process);
        $process = null;
        $code = $status['exitcode'] >= 0 ? $status['exitcode'] : $closedCode;
        rewind($streams[1]);
        rewind($streams[2]);
        $result = ['code' => $code, 'stdout' => stream_get_contents($streams[1]), 'stderr' => stream_get_contents($streams[2])];
        ensure(!$check || $code === 0, 'Příkaz ' . basename((string) $args[0]) . ' skončil kódem ' . $code . '.');
        return $result;
    } finally {
        if (is_resource($process)) {
            proc_terminate($process);
            proc_close($process);
        }
        foreach ($streams as $stream) {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}

function git(string $root, string ...$args): string
{
    return trim(run(['git', '-c', 'core.quotepath=false', '-C', $root, ...$args])['stdout']);
}

function readFile(string $path): string
{
    $data = @file_get_contents($path);
    ensure($data !== false, 'Nelze načíst soubor: ' . basename($path));
    return $data;
}

function writeFile(string $path, string $data): void
{
    $parent = dirname($path);
    ensure(is_dir($parent) || @mkdir($parent, 0777, true), 'Nelze vytvořit složku souboru.');
    ensure(file_put_contents($path, $data) === strlen($data), 'Nelze zapsat soubor: ' . basename($path));
}

function readJson(string $path): array
{
    try {
        $data = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', readFile($path)), true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        throw new Failure('Nelze načíst platný JSON: ' . basename($path));
    }
    ensure(is_array($data), 'JSON musí obsahovat objekt nebo pole: ' . basename($path));
    return $data;
}

function json(array $value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
}

function writeJson(string $path, array $value): void { writeFile($path, json($value)); }
function digest(string $data): string { return hash('sha256', $data); }
function now(): string { return gmdate('Y-m-d\TH:i:s\Z'); }

function safePath(string $root, string $relative): string
{
    ensure($relative !== '' && !str_contains($relative, '\\') && !str_contains($relative, "\0") && !str_starts_with($relative, '/'),
        'Cesta musí být neprázdná relativní cesta s lomítky.');
    $parts = explode('/', $relative);
    $resolved = realpath($root);
    ensure($resolved !== false, 'Kořen projektu neexistuje.');
    $path = str_replace('\\', '/', $resolved);
    $prefix = strtolower(rtrim($path, '/') . '/');
    foreach ($parts as $part) {
        ensure(!in_array(strtolower($part), ['', '.', '..', '.git', '.svn'], true) && !str_contains($part, ':') && !preg_match('/[. ]$/', $part),
            'Nepovolená cesta mimo projekt nebo do metadat.');
        $path .= '/' . $part;
        ensure(!is_link($path), 'Symbolické odkazy nejsou v předávaných cestách povoleny.');
        $actual = realpath($path);
        ensure($actual === false || str_starts_with(strtolower(str_replace('\\', '/', $actual)), $prefix), 'Cesta opouští projekt.');
    }
    return $path;
}

function config(string $root): array
{
    return validateConfig(readJson($root . '/.prace.json'));
}

function validateConfig(array $data): array
{
    ensure(($data['version'] ?? null) === 1, 'Neznámá verze .prace.json.');
    ensure(is_string($data['repository'] ?? null) && (bool) preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~D', $data['repository']), 'Neplatný repozitář.');
    $commands = $data['tests'] ?? null;
    ensure(is_array($commands) && array_is_list($commands) && $commands !== [], 'Chybí povinné ověřovací příkazy.');
    foreach ($commands as $command) {
        ensure(is_array($command) && array_is_list($command) && $command !== [], 'Testovací příkazy musí být neprázdná pole argumentů.');
        foreach ($command as $arg) {
            ensure(is_string($arg) && $arg !== '', 'Neplatný argument testovacího příkazu.');
        }
    }
    ensure(is_array($data['svn'] ?? null) && is_bool($data['svn']['enabled'] ?? null), 'Chybí nastavení SVN.');
    return $data;
}

function tracked(string $root): array
{
    return array_values(array_filter(explode("\0", run(['git', '-C', $root, 'ls-files', '-z'])['stdout']), fn($s) => $s !== ''));
}

function clean(string $root): void
{
    ensure(git($root, 'status', '--porcelain', '--untracked-files=normal') === '', 'Pracovní strom není čistý. Ověření musí patřit přesnému commitu.');
}

function resolveCommit(string $root, string $ref): string
{
    ensure($ref !== '' && !str_starts_with($ref, '-'), 'Neplatný odkaz na commit.');
    $value = git($root, 'rev-parse', '--verify', $ref . '^{commit}');
    ensure((bool) preg_match('/^[0-9a-f]{40}$/D', $value), 'Nelze určit commit.');
    return $value;
}

function changed(string $root, string $base, string $head = 'HEAD'): array
{
    $head = resolveCommit($root, $head);
    if ($base === 'ROOT') {
        ensure(git($root, 'rev-list', '--count', $head) === '1', 'ROOT je pouze pro první commit.');
        $base = git($root, 'hash-object', '-t', 'tree', '--stdin');
    } else {
        $base = resolveCommit($root, $base);
        ensure(run(['git', '-C', $root, 'merge-base', '--is-ancestor', $base, $head], check: false)['code'] === 0,
            'Základ není předkem ověřované změny.');
    }
    $parts = explode("\0", run(['git', '-C', $root, 'diff', '--no-renames', '--name-status', '-z', $base, $head])['stdout']);
    $result = [];
    for ($i = 0; $i < count($parts) - 1; $i += 2) {
        $result[] = [$parts[$i], $parts[$i + 1]];
    }
    return $result;
}

function noLongDash(string $text, string $description): void
{
    ensure(!str_contains($text, "\u{2013}") && !str_contains($text, "\u{2014}"), $description . ': používej pouze krátké pomlčky.');
}

function meaningful(mixed $text, string $description, int $minimum = 12): void
{
    ensure(is_string($text) && mb_strlen(trim($text), 'UTF-8') >= $minimum, $description . ': chybí konkrétní popis.');
    ensure(!preg_match('/\b(TODO|TBD|doplnit|placeholder|lorem ipsum)\b/iu', $text), $description . ': zůstal zástupný text.');
}

function policyHash(string $root): string
{
    $files = [TOOL_ROOT . '/upstream.lock.json', $root . '/.prace.json', TOOL_ROOT . '/prace.php', TOOL_ROOT . '/prace.ps1'];
    if (is_file($root . '/nastroje-prace.lock.json')) {
        $files[] = $root . '/nastroje-prace.lock.json';
    }
    foreach (['src/*.php', 'scripts/*.php', 'scripts/*.ps1', 'hooky/*', 'pravidla/*.md'] as $pattern) {
        $matches = glob(TOOL_ROOT . '/' . $pattern);
        sort($matches);
        array_push($files, ...$matches);
    }
    return digest(implode("\0", array_map(fn($path) => basename($path) . "\0" . readFile($path), $files)));
}

/** Neočekávané chyby nesmí vypsat vstupní JSON, argumenty nebo přihlášení. */
function entry(callable $action): int
{
    try {
        $action();
        return 0;
    } catch (\Throwable $error) {
        fwrite(STDERR, ($error instanceof Failure ? $error->getMessage() : 'Neúspěšné ověření: ' . get_class($error) . '.') . "\n");
        return 1;
    }
}
