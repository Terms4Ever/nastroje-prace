<?php
declare(strict_types=1);

namespace NastrojePrace;

final class Upstream
{
    public static function cache(): string { return TOOL_ROOT . '/.cache/nastroje'; }

    public static function runtime(): void
    {
        ensure(PHP_VERSION_ID >= 80300, 'Je vyžadováno PHP 8.3 nebo novější.');
        foreach (['mbstring', 'curl', 'zip', 'SimpleXML'] as $name) {
            ensure(extension_loaded($name), 'Chybí PHP rozšíření ' . $name . '.');
        }
    }

    public static function verify(?string $cache = null): string
    {
        self::runtime();
        $lock = readJson(TOOL_ROOT . '/upstream.lock.json');
        $cache ??= self::cache();
        ensure(is_dir($cache), 'Chybí připnuté nástroje. Spusť bootstrap.');
        ensure(git($cache, 'rev-parse', 'HEAD') === $lock['commit'], 'Nesedí verze společných nástrojů.');
        ensure(git($cache, 'status', '--porcelain') === '', 'Společné nástroje byly lokálně změněny.');
        return $lock['commit'];
    }

    public static function bootstrap(): string
    {
        self::runtime();
        $lock = readJson(TOOL_ROOT . '/upstream.lock.json');
        if (!file_exists(self::cache())) {
            $parent = dirname(self::cache());
            ensure(is_dir($parent) || mkdir($parent, 0777, true), 'Nelze vytvořit cache.');
            run(['git', 'clone', '--no-checkout', $lock['repository'], self::cache()]);
            git(self::cache(), 'checkout', '--detach', $lock['commit']);
        }
        return self::verify();
    }

    public static function check(string $script, array $args = [], string $input = ''): string
    {
        self::verify();
        ensure(in_array($script, ['kontrola-readme.php', 'kontrola-dokumentace.php', 'kontrola-tvaru-issue.php', 'stav-projektu.php'], true), 'Neznámý společný validátor.');
        $result = run([PHP_BINARY, self::cache() . '/' . $script, ...$args], input: $input, check: false);
        ensure($result['code'] === 0, trim($result['stdout'] . $result['stderr']));
        return trim($result['stdout']);
    }
}
