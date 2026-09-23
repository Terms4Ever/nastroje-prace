<?php
declare(strict_types=1);

namespace NastrojePrace;
require_once __DIR__ . '/../src/load.php';

exit(entry(function (): void {
    $root = getcwd();
    $eventPath = getenv('GITHUB_EVENT_PATH');
    ensure(is_string($eventPath) && $eventPath !== '', 'Chybí soubor události GitHubu.');
    $event = readJson($eventPath);
    $base = $event['pull_request']['base']['sha'] ?? $event['before'] ?? null;
    if (!$base || $base === str_repeat('0', 40)) {
        if (git($root, 'rev-list', '--count', 'HEAD') === '1') {
            $base = 'ROOT';
        } elseif (getenv('GITHUB_REF') === 'refs/heads/main') {
            $base = git($root, 'rev-parse', 'HEAD^');
        } else {
            $base = git($root, 'merge-base', 'HEAD', 'origin/main');
        }
    }
    Gate::verify($root, $base, true, new GitHub(config($root)['repository']));
}));
