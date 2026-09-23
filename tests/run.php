<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;
require_once __DIR__ . '/../src/load.php';
require_once __DIR__ . '/support.php';

// I testy samy mají být syntakticky platné; lint pokrývá všechny PHP vstupní body.
exit(\NastrojePrace\entry(function (): void {
    \NastrojePrace\Upstream::verify();
    $files = [__DIR__ . '/../prace.php', ...glob(__DIR__ . '/../src/*.php'), ...glob(__DIR__ . '/../scripts/*.php'), ...glob(__DIR__ . '/*.php')];
    foreach ($files as $file) { \NastrojePrace\run([PHP_BINARY, '-l', $file]); }
    require __DIR__ . '/policy.php';
    require __DIR__ . '/gate.php';
    require __DIR__ . '/svn.php';
    require __DIR__ . '/project.php';
    require __DIR__ . '/main.php';
    require __DIR__ . '/decisions.php';
    require __DIR__ . '/screenshots.php';
    require __DIR__ . '/documentation.php';
    $failed = $passed = $skipped = 0;
    foreach ($GLOBALS['tests'] as [$name, $action, $svn]) {
        if ($svn && !hasSvn()) {
            echo 'SKIP ' . $name . ": místní SVN CLI není dostupné.\n";
            $skipped++;
            continue;
        }
        try {
            $action();
            echo 'PASS ' . $name . "\n";
            $passed++;
        } catch (\Throwable $error) {
            echo 'FAIL ' . $name . ': ' . $error->getMessage() . "\n";
            $failed++;
        }
    }
    echo "Výsledek: $passed úspěšných, $failed neúspěšných, $skipped přeskočených.\n";
    \NastrojePrace\ensure($failed === 0, 'Testovací sada selhala.');
}));
