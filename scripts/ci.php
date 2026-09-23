<?php
declare(strict_types=1);

namespace NastrojePrace;
require_once __DIR__ . '/../src/load.php';

exit(entry(function (): void {
    $root = getcwd();
    $eventPath = getenv('GITHUB_EVENT_PATH');
    ensure(is_string($eventPath) && $eventPath !== '', 'Chybí soubor události GitHubu.');
    $event = readJson($eventPath);
    $base = MainBranch::ciBase($root, $event, getenv('GITHUB_EVENT_NAME') ?: '', getenv('GITHUB_REF') ?: '', getenv('GITHUB_SHA') ?: '');
    Gate::verify($root, $base, true, new GitHub(config($root)['repository']), ci: true);
}));
