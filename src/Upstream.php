<?php
declare(strict_types=1);

namespace NastrojePrace;

final class Upstream
{
    public static function cache(bool $documentation = false): string
    {
        return TOOL_ROOT . '/.cache/' . ($documentation ? 'nastroje-dokumentace' : 'nastroje');
    }

    public static function runtime(): void
    {
        ensure(PHP_VERSION_ID >= 80300, 'Je vyžadováno PHP 8.3 nebo novější.');
        foreach (['mbstring', 'curl', 'zip', 'SimpleXML'] as $name) {
            ensure(extension_loaded($name), 'Chybí PHP rozšíření ' . $name . '.');
        }
    }

    public static function verify(?string $cache = null, bool $documentation = false): string
    {
        self::runtime();
        $verifyBoth = $cache === null && !$documentation;
        $lock = readJson(TOOL_ROOT . '/upstream.lock.json');
        $commit = $lock[$documentation ? 'documentation_commit' : 'commit'] ?? null;
        ensure(is_string($commit) && preg_match('/^[0-9a-f]{40}$/D', $commit), 'Chybí platný připnutý commit společných nástrojů.');
        $cache ??= self::cache($documentation);
        ensure(is_dir($cache), 'Chybí připnuté nástroje. Spusť bootstrap.');
        ensure(git($cache, 'rev-parse', 'HEAD') === $commit, 'Nesedí verze společných nástrojů.');
        ensure(git($cache, 'status', '--porcelain') === '', 'Společné nástroje byly lokálně změněny.');
        if ($verifyBoth) { self::verify(null, true); }
        return $commit;
    }

    public static function bootstrap(): string
    {
        self::runtime();
        $lock = readJson(TOOL_ROOT . '/upstream.lock.json');
        foreach ([false, true] as $documentation) {
            $cache = self::cache($documentation);
            if (!file_exists($cache)) {
                $parent = dirname($cache);
                ensure(is_dir($parent) || mkdir($parent, 0777, true), 'Nelze vytvořit cache.');
                run(['git', 'clone', '--no-checkout', $lock['repository'], $cache]);
                git($cache, 'checkout', '--detach', $lock[$documentation ? 'documentation_commit' : 'commit']);
            }
            self::verify($cache, $documentation);
        }
        return self::verify();
    }

    public static function check(string $script, array $args = [], string $input = ''): string
    {
        ensure(in_array($script, ['kontrola-readme.php', 'kontrola-dokumentace.php', 'kontrola-tvaru-issue.php', 'stav-projektu.php'], true), 'Neznámý společný validátor.');
        // Oprava názvu dokumentu nesmí převzít osobní pravidla snímků v issues.
        $documentation = $script === 'kontrola-dokumentace.php';
        self::verify(null, $documentation);
        $result = run([PHP_BINARY, self::cache($documentation) . '/' . $script, ...$args], input: $input, check: false);
        ensure($result['code'] === 0, trim($result['stdout'] . $result['stderr']));
        return trim($result['stdout']);
    }
}
