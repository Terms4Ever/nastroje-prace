<?php
declare(strict_types=1);

namespace NastrojePrace;

final class ScreenshotException
{
    public static function path(string $root, int $number, string $path): array
    {
        safePath($root, $path);
        ensure((bool) preg_match('~^(docs/snimky/' . $number . '-[^/]+)/(pred|po)-([^/]+\.(?:png|jpe?g|webp))$~D', $path, $match),
            'Výjimka musí určit konkrétní snímek před nebo po stejného issue.');
        return [$match[2], $match[1] . '/' . $match[3]];
    }

    /** Připraví podklady; tento příkaz sám nic neschvaluje ani nezapisuje na GitHub. */
    public static function request(string $root, int $number): array
    {
        $meta = readJson($root . '/.tasks/' . $number . '.json');
        ensure(($meta['issue'] ?? null) === $number && ($meta['visual'] ?? null) === true, 'Výjimka snímků patří jen vizuálnímu úkolu stejného čísla.');
        $exception = $meta['screenshot_exception'] ?? null;
        ensure(is_array($exception), 'Chybí žádost o výjimku snímků.');
        foreach (['reason' => 'Překážka pořízení snímku', 'attempt' => 'Pokus o pořízení snímku'] as $key => $label) {
            meaningful($exception[$key] ?? null, $label);
            noLongDash($exception[$key], $label);
        }
        $missing = $exception['missing'] ?? null;
        ensure(is_array($missing) && array_is_list($missing) && $missing !== [], 'Výjimka nemá konkrétní chybějící snímky.');
        foreach ($missing as $path) {
            ensure(is_string($path), 'Cesta chybějícího snímku musí být text.');
            self::path($root, $number, $path);
            ensure(!file_exists(safePath($root, $path)), 'Snímek existuje; vlož jej do issue místo výjimky.');
        }
        ensure(count(array_unique($missing)) === count($missing), 'Výjimka opakuje stejný snímek.');
        sort($missing);
        $evidence = $exception['evidence'] ?? null;
        ensure(is_array($evidence) && array_is_list($evidence) && $evidence !== [], 'Chybí doložený pokus a dostupný náhradní důkaz.');
        $hashes = [];
        $files = tracked($root);
        foreach ($evidence as $path) {
            ensure(is_string($path) && str_starts_with($path, 'docs/'), 'Důkaz výjimky musí být soubor v dokumentaci.');
            $file = safePath($root, $path);
            ensure(in_array($path, $files, true) && is_file($file) && !isset($hashes[$path]), 'Důkaz musí být existující verzovaný soubor bez opakování.');
            $data = readFile($file);
            ensure(trim($data) !== '', 'Prázdný soubor nedokládá nemožnost snímku.');
            ensure(run(['git', '-C', $root, 'diff', '--quiet', '--', $path], check: false)['code'] === 0,
                'Důkaz se po přidání do Gitu změnil; před žádostí přidej jeho aktuální obsah.');
            // Git blob zachová stejný otisk i při jiných koncích řádků místní kopie.
            $hashes[$path] = digest(run(['git', '-C', $root, 'show', ':' . $path])['stdout']);
        }
        ksort($hashes);
        $request = ['repository' => config($root)['repository'], 'issue' => $number, 'missing' => $missing,
            'reason' => $exception['reason'], 'attempt' => $exception['attempt'], 'evidence' => $hashes];
        $hash = digest(json($request));
        return [...$request, 'hash' => $hash, 'approval_text' => 'Výjimku snímků pro #' . $number . ' schvaluji: ' . $hash . '.'];
    }

    /** Živý souhlas platí jen pro přesné podklady. Totožnost člověka za sdíleným účtem API neprokazuje. */
    public static function approvedMissing(string $root, int $number, string $body, bool $visual, ?ApiClient $client): array
    {
        $path = $root . '/.tasks/' . $number . '.json';
        $meta = is_file($path) ? readJson($path) : [];
        if (!array_key_exists('screenshot_exception', $meta)) { return []; }
        ensure($visual, 'Nevizuální issue nemůže použít výjimku snímků.');
        $request = self::request($root, $number);
        $id = $meta['screenshot_exception']['approval_comment'] ?? null;
        ensure(is_int($id) && $id > 0, 'Chybí výslovné schválení výjimky vlastníkem v komentáři issue.');
        ensure($client !== null, 'Schválení výjimky snímků vyžaduje živé ověření GitHubu.');
        $comment = $client->request('GET', '/issues/comments/' . $id);
        $url = 'https://github.com/' . $request['repository'] . '/issues/' . $number . '#issuecomment-' . $id;
        $owner = explode('/', $request['repository'])[0];
        ensure(($comment['id'] ?? null) === $id && ($comment['html_url'] ?? '') === $url
            && strcasecmp($comment['user']['login'] ?? '', $owner) === 0
            && ($comment['user']['type'] ?? '') === 'User' && empty($comment['performed_via_github_app']),
            'Souhlas musí pocházet od vlastníka a patřit stejnému issue.');
        $lines = preg_split('/\R/u', trim($comment['body'] ?? ''));
        ensure(($lines[0] ?? '') === $request['approval_text'], 'Souhlas vlastníka neodpovídá aktuálním podkladům výjimky.');
        Policy::comment($comment['body']);
        ensure(str_contains(Policy::sections($body)['Snímky'] ?? '', $url), 'Sekce Snímky musí odkazovat na konkrétní souhlas vlastníka.');
        return $request['missing'];
    }
}
