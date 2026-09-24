<?php
declare(strict_types=1);

namespace NastrojePrace;

/** Pracovní vstup sdílené kontroly; upstream je závislost, nikoli druhá sada projektu. */
final class RuleSet
{
    public const NAME = 'nastroje-prace';
    public const TOPIC = 'pravidla-nastroje-prace';

    private static function load(): void
    {
        Upstream::verify();
        $library = Upstream::cache() . '/src/sada-pravidel.php';
        ensure(is_file($library), 'Připnuté nástroje neobsahují kontrolu sad pravidel. Ověř upstream.lock.json.');
        require_once $library;
    }

    public static function verify(string $root): string
    {
        self::load();
        try {
            \Terms4Ever\SadaPravidel::over($root, self::NAME);
            \Terms4Ever\SadaPravidel::topics(config($root)['topics'] ?? [], self::NAME);
        } catch (\RuntimeException $error) {
            throw new Failure($error->getMessage());
        }
        return self::NAME;
    }

    public static function topics(array $topics): void
    {
        self::load();
        try {
            \Terms4Ever\SadaPravidel::topics($topics, self::NAME);
        } catch (\RuntimeException $error) {
            throw new Failure($error->getMessage());
        }
    }
}
