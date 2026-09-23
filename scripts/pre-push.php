<?php
declare(strict_types=1);

namespace NastrojePrace;
require_once __DIR__ . '/../src/load.php';

exit(entry(function (): void {
    $root = getcwd();
    while (($line = fgets(STDIN)) !== false) {
        $fields = preg_split('/\s+/', trim($line));
        ensure(count($fields) === 4, 'Hook obdržel neplatný seznam referencí.');
        [$localRef, $localSha, $remoteRef, $remoteSha] = $fields;
        if ($localSha === str_repeat('0', 40)) {
            continue;
        }
        ensure($localSha === resolveCommit($root, 'HEAD'), 'Push musí obsahovat právě ověřovaný HEAD.');
        $base = $remoteSha;
        if ($base === str_repeat('0', 40)) {
            $base = git($root, 'rev-list', '--count', 'HEAD') === '1' ? 'ROOT' : git($root, 'merge-base', 'HEAD', 'origin/main');
        }
        Gate::verify($root, $base, true, new GitHub(config($root)['repository']));
    }
}));
