<?php
declare(strict_types=1);

namespace NastrojePrace;

final class MainBranch
{
    public static function local(string $root): void
    {
        $ref = run(['git', '-C', $root, 'symbolic-ref', '--quiet', 'HEAD'], check: false);
        ensure($ref['code'] === 0 && trim($ref['stdout']) === 'refs/heads/main', 'Pracuj pouze ve větvi main; pracovní větve ani odpojený HEAD nejsou povolené.');
    }

    public static function pushBase(string $root, string $input): ?string
    {
        self::local($root);
        if (trim($input) === '') { return null; }
        $lines = preg_split('/\r?\n/', trim($input));
        ensure(count($lines) === 1, 'Push smí měnit pouze main, bez dalších větví nebo tagů.');
        $fields = preg_split('/\s+/', $lines[0]);
        ensure(count($fields) === 4, 'Hook obdržel neplatný seznam referencí.');
        [$localRef, $localSha, $remoteRef, $remoteSha] = $fields;
        ensure(in_array($localRef, ['refs/heads/main', 'HEAD'], true) && $remoteRef === 'refs/heads/main', 'Push smí měnit pouze main; jiné větve, tagy a odstranění jsou zakázané.');
        ensure($localSha === resolveCommit($root, 'HEAD'), 'Push musí obsahovat právě ověřovaný HEAD.');
        ensure((bool) preg_match('/^[a-f0-9]{40}$/D', $remoteSha), 'Hook obdržel neplatné vzdálené SHA.');
        if ($remoteSha === str_repeat('0', 40)) {
            ensure(git($root, 'rev-list', '--count', 'HEAD') === '1', 'Nový main musí začít jediným ověřeným prvním commitem.');
            return 'ROOT';
        }
        return $remoteSha;
    }

    /** CI smí používat odpojený HEAD pouze pro přesné SHA události na main. */
    public static function ciBase(string $root, array $event, string $name, string $ref, string $sha): string
    {
        ensure(in_array($name, ['push', 'workflow_dispatch'], true) && $ref === 'refs/heads/main', 'CI přijímá pouze push nebo ruční ověření větve main.');
        ensure($sha === resolveCommit($root, 'HEAD'), 'CI neověřuje přesné SHA události.');
        if ($name === 'push') {
            ensure(($event['ref'] ?? '') === $ref && ($event['after'] ?? '') === $sha && ($event['deleted'] ?? false) === false, 'Událost neodpovídá změně main.');
            $base = $event['before'] ?? '';
            ensure(is_string($base) && (bool) preg_match('/^[a-f0-9]{40}$/D', $base), 'Chybí přesný základ push události.');
            if ($base === str_repeat('0', 40)) { $base = 'ROOT'; }
        } else {
            $base = $event['inputs']['base'] ?? '';
            ensure(is_string($base) && $base !== '', 'Ruční CI vyžaduje explicitní základ rozsahu.');
        }
        changed($root, $base, $sha);
        return $base;
    }
}
