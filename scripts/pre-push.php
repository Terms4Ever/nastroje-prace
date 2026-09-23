<?php
declare(strict_types=1);

namespace NastrojePrace;
require_once __DIR__ . '/../src/load.php';

exit(entry(function (): void {
    $root = getcwd();
    $base = MainBranch::pushBase($root, stream_get_contents(STDIN));
    if ($base !== null) {
        Gate::verify($root, $base, true, new GitHub(config($root)['repository']));
    }
}));
