<?php
declare(strict_types=1);

namespace NastrojePrace;

final class Gate
{
    public static function receiptPath(string $root, string $sha): string { return $root . '/.local/overeni/' . $sha . '.json'; }

    public static function receipt(string $root): array
    {
        MainBranch::local($root);
        Project::verifyIfBound($root);
        clean($root);
        $sha = resolveCommit($root, 'HEAD');
        $value = readJson(self::receiptPath($root, $sha));
        ensure(($value['commit'] ?? '') === $sha && ($value['tree'] ?? '') === git($root, 'rev-parse', 'HEAD^{tree}'), 'Ověření patří jiné verzi.');
        ensure(($value['policy'] ?? '') === policyHash($root), 'Pravidla se po ověření změnila.');
        ensure(($value['success'] ?? false) === true && !empty($value['tests']), 'Chybí úspěšné ověření.');
        ensure(($value['upstream'] ?? '') === Upstream::verify(), 'Ověření má jinou verzi společných pravidel.');
        return $value;
    }

    /** Společná podmínka předání, uzavření issue a dokončení zavedení projektu. */
    public static function published(string $root, ?ApiClient $client = null): array
    {
        $proof = self::receipt($root);
        ensure(($proof['online'] ?? false) === true, 'Chybí místní online ověření přesného commitu.');
        $client ??= new GitHub(config($root)['repository']);
        Policy::repositoryMetadata($root, $client);
        $sha = $proof['commit'];
        ensure(($client->request('GET', '/commits/main')['sha'] ?? '') === $sha, 'Ověřená změna ještě není aktuálním main.');
        // Nefiltrujeme úspěchy: nový čekající nebo neúspěšný běh nesmí zakrýt starý úspěch.
        $runs = $client->request('GET', '/actions/workflows/kontroly.yml/runs?branch=main&head_sha=' . $sha . '&per_page=1');
        $run = $runs['workflow_runs'][0] ?? [];
        ensure(($run['head_sha'] ?? '') === $sha && ($run['head_branch'] ?? '') === 'main'
            && ($run['path'] ?? '') === '.github/workflows/kontroly.yml'
            && in_array($run['event'] ?? '', ['push', 'workflow_dispatch'], true)
            && ($run['status'] ?? '') === 'completed' && ($run['conclusion'] ?? '') === 'success'
            && is_int($run['id'] ?? null) && $run['id'] > 0, 'Chybí úspěšné GitHub CI ověřeného commitu na main.');
        $jobs = [];
        for ($page = 1; $page <= 100; $page++) {
            $batch = $client->request('GET', '/actions/runs/' . $run['id'] . '/jobs?filter=latest&per_page=100&page=' . $page);
            ensure(is_array($batch['jobs'] ?? null) && array_is_list($batch['jobs']), 'GitHub nevrátil seznam CI úloh.');
            array_push($jobs, ...$batch['jobs']);
            if (count($batch['jobs']) < 100) { break; }
            ensure($page < 100, 'Překročen limit stránek CI úloh.');
        }
        $matching = array_values(array_filter($jobs, fn($job) => ($job['name'] ?? '') === 'Povinne kontroly'));
        ensure(count($matching) === 1 && ($matching[0]['head_sha'] ?? '') === $sha
            && ($matching[0]['status'] ?? '') === 'completed' && ($matching[0]['conclusion'] ?? '') === 'success',
            'Chybí úspěšná souhrnná úloha Povinne kontroly posledního CI běhu.');
        // Čtení API není zámek, ale změnu main během kontroly nesmíme přehlédnout.
        ensure(($client->request('GET', '/commits/main')['sha'] ?? '') === $sha, 'Main se během ověření změnil.');
        self::receipt($root);
        return $proof;
    }

    public static function verify(string $root, string $base, bool $online = false, ?ApiClient $client = null, bool $ci = false): array
    {
        if (!$ci) { MainBranch::local($root); }
        $settings = config($root);
        clean($root);
        $sha = resolveCommit($root, 'HEAD');
        $destination = self::receiptPath($root, $sha);
        if (file_exists($destination)) {
            ensure(unlink($destination), 'Nelze zneplatnit předchozí ověření.');
        }
        Project::verifyIfBound($root);
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
