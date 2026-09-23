<?php
declare(strict_types=1);

namespace NastrojePrace;

final class Gate
{
    public static function receiptPath(string $root, string $sha): string { return $root . '/.local/overeni/' . $sha . '.json'; }

    public static function receipt(string $root): array
    {
        clean($root);
        $sha = resolveCommit($root, 'HEAD');
        $value = readJson(self::receiptPath($root, $sha));
        ensure(($value['commit'] ?? '') === $sha && ($value['tree'] ?? '') === git($root, 'rev-parse', 'HEAD^{tree}'), 'Ověření patří jiné verzi.');
        ensure(($value['policy'] ?? '') === policyHash($root), 'Pravidla se po ověření změnila.');
        ensure(($value['success'] ?? false) === true && !empty($value['tests']), 'Chybí úspěšné ověření.');
        ensure(($value['upstream'] ?? '') === Upstream::verify(), 'Ověření má jinou verzi společných pravidel.');
        return $value;
    }

    public static function verify(string $root, string $base, bool $online = false, ?ApiClient $client = null): array
    {
        $settings = config($root);
        clean($root);
        $sha = resolveCommit($root, 'HEAD');
        $destination = self::receiptPath($root, $sha);
        if (file_exists($destination)) {
            ensure(unlink($destination), 'Nelze zneplatnit předchozí ověření.');
        }
        $policy = policyHash($root);
        Upstream::verify();
        Policy::repositoryContent($root);
        $files = changed($root, $base, $sha);
        ensure($files !== [], 'Rozsah neobsahuje žádnou změnu.');
        $commits = explode("\n", git($root, 'rev-list', '--reverse', $base === 'ROOT' ? $sha : resolveCommit($root, $base) . '..' . $sha));
        $records = [];
        foreach ($commits as $commit) {
            $number = Policy::commit(git($root, 'show', '-s', '--format=%B', $commit));
            $records[$number] = Policy::taskRecord($root, $number);
        }
        ksort($records);
        $textChanges = [];
        foreach ($files as [$status, $path]) {
            if ($status !== 'D' && preg_match('~^docs/ukoly/[0-9]+\.md$~D', $path)) {
                $textChanges[] = $path;
            }
        }
        foreach ($records as $number => $record) {
            ensure(in_array('docs/ukoly/' . $number . '.md', $textChanges, true), 'Každý úkol ve změně musí mít aktualizovaný vlastní textový záznam.');
        }
        Policy::readme($root);
        Upstream::check('kontrola-dokumentace.php', [$root]);
        if ($online) {
            ensure($client !== null, 'Chybí klient GitHubu.');
            Policy::repositoryMetadata($root, $client);
            foreach ($records as $number => $record) {
                $issue = $client->issue($number);
                Policy::issue($root, $issue);
                ensure(in_array('rozhrani', Policy::names($issue['labels']), true) === $record['visual'], 'Issue a záznam nesouhlasí o změně rozhraní.');
            }
        }
        $tests = [];
        foreach ($settings['tests'] as $index => $command) {
            $args = array_map(fn($arg) => $arg === '{php}' ? PHP_BINARY : $arg, $command);
            $result = run($args, cwd: $root, timeout: 600, check: false);
            $output = $result['stdout'] . $result['stderr'];
            writeFile($root . '/.local/overeni/' . $sha . '-test-' . $index . '.log', $output);
            echo $output;
            ensure($result['code'] === 0, 'Povinné ověření ' . ($index + 1) . ' selhalo; viz místní protokol.');
            $tests[] = ['command' => $command, 'exit' => $result['code'], 'log_sha256' => digest($output)];
        }
        clean($root);
        ensure(resolveCommit($root, 'HEAD') === $sha && policyHash($root) === $policy, 'Obsah nebo pravidla se změnily během testů.');
        $value = ['version' => 1, 'success' => true, 'commit' => $sha, 'tree' => git($root, 'rev-parse', 'HEAD^{tree}'),
            'policy' => $policy, 'upstream' => Upstream::verify(), 'base' => $base, 'issues' => array_keys($records),
            'online' => $online, 'tests' => $tests, 'time' => now()];
        writeJson($destination, $value);
        return $value;
    }
}
