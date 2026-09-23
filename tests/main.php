<?php
declare(strict_types=1);

namespace NastrojePrace\Tests;

use NastrojePrace\{MainBranch, Gate};
use function NastrojePrace\{git, run, writeFile};

test('První main i běžný push mají přesný základ', function (): void {
    $temp = new TempTree();
    try {
        git($temp->root, 'init', '-b', 'main');
        git($temp->root, 'config', 'user.name', 'Zkouska');
        git($temp->root, 'config', 'user.email', 'test@example.invalid');
        MainBranch::local($temp->root);
        writeFile($temp->root . '/source.txt', 'První obsah');
        $sha = commit($temp->root);
        expect(MainBranch::pushBase($temp->root, "refs/heads/main $sha refs/heads/main " . str_repeat('0', 40)) === 'ROOT');
        writeFile($temp->root . '/source.txt', 'Druhý obsah'); $next = commit($temp->root);
        expect(MainBranch::pushBase($temp->root, "HEAD $next refs/heads/main $sha\n") === $sha);
        expect(MainBranch::pushBase($temp->root, '') === null);
        fails(fn() => MainBranch::pushBase($temp->root, "HEAD $next refs/heads/main " . str_repeat('0', 40)), 'jediným');
    } finally { $temp->remove(); }
});

test('Push odmítne jinou větev, tag, odstranění a další referenci před API', fn() => fixture(function ($root): void {
    $sha = git($root, 'rev-parse', 'HEAD'); $base = git($root, 'rev-parse', 'HEAD^');
    $valid = "refs/heads/main $sha refs/heads/main $base";
    foreach (["refs/heads/main $sha refs/heads/ukol/1 $base", "refs/heads/main $sha refs/tags/v1 $base",
        "refs/heads/ukol/1 $sha refs/heads/main $base", '(delete) ' . str_repeat('0', 40) . " refs/heads/main $sha",
        "$valid\nrefs/tags/v1 $sha refs/tags/v1 $base", "HEAD $base refs/heads/main $base"] as $input) {
        fails(fn() => MainBranch::pushBase($root, $input));
        $result = run([PHP_BINARY, \NastrojePrace\TOOL_ROOT . '/scripts/pre-push.php'], cwd: $root, input: $input, check: false);
        expect($result['code'] !== 0 && !str_contains($result['stderr'], 'API'));
    }
}));

test('Skutečný commit hook odmítne platnou zprávu mimo main', fn() => projectFixture(function ($target, $source): void {
    application($target, $source);
    $sha = git($target, 'rev-parse', 'HEAD');
    git($target, 'switch', '-c', 'zakazany-test');
    $result = run(['git', '-C', $target, 'commit', '--allow-empty', '-m', str_replace('(#1)', '(#7)', MESSAGE)], check: false);
    expect($result['code'] !== 0 && str_contains($result['stderr'], 'pouze ve větvi main'));
    expect(git($target, 'rev-parse', 'HEAD') === $sha);
    fails(fn() => MainBranch::pushBase($target, ''), 'pouze ve větvi main');
}));

test('CI přijme přesné main SHA v odpojené kopii a odmítne cizí událost či základ', fn() => fixture(function ($root): void {
    $base = git($root, 'rev-parse', 'HEAD'); $sha = change($root);
    $event = ['ref' => 'refs/heads/main', 'after' => $sha, 'before' => $base, 'deleted' => false];
    git($root, 'checkout', '--detach', $sha);
    fails(fn() => Gate::verify($root, $base), 'pouze ve větvi main');
    expect(MainBranch::ciBase($root, $event, 'push', 'refs/heads/main', $sha) === $base);
    quiet(fn() => Gate::verify($root, $base, ci: true));
    foreach ([['push', 'refs/heads/jina', $sha, $event], ['pull_request', 'refs/heads/main', $sha, $event],
        ['push', 'refs/heads/main', $base, $event], ['push', 'refs/heads/main', $sha, [...$event, 'before' => '']],
        ['push', 'refs/heads/main', $sha, [...$event, 'deleted' => true]],
        ['push', 'refs/heads/main', $sha, [...$event, 'before' => str_repeat('0', 40)]]] as [$name, $ref, $head, $data]) {
        fails(fn() => MainBranch::ciBase($root, $data, $name, $ref, $head));
    }
    expect(MainBranch::ciBase($root, ['inputs' => ['base' => $base]], 'workflow_dispatch', 'refs/heads/main', $sha) === $base);
    fails(fn() => MainBranch::ciBase($root, [], 'workflow_dispatch', 'refs/heads/main', $sha), 'explicitní');
}));
