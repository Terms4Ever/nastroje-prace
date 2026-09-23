<?php
declare(strict_types=1);

namespace NastrojePrace;

interface ApiClient
{
    public function request(string $method, string $suffix, ?array $data = null): mixed;
    public function pages(string $suffix): array;
    public function issue(int $number): array;
}

class GitHub implements ApiClient
{
    private ?string $credential = null;

    public function __construct(private readonly string $repository)
    {
        ensure((bool) preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~D', $repository), 'Neplatný repozitář API.');
    }

    private function token(): string
    {
        if ($this->credential === null) {
            $value = getenv('GH_TOKEN') ?: getenv('GITHUB_TOKEN');
            if (!$value) {
                $result = run(['git', '-c', 'credential.interactive=false', 'credential', 'fill'], input: "protocol=https\nhost=github.com\n\n");
                $fields = [];
                foreach (explode("\n", $result['stdout']) as $line) {
                    if (str_contains($line, '=')) {
                        [$key, $field] = explode('=', rtrim($line, "\r"), 2);
                        $fields[$key] = $field;
                    }
                }
                $value = $fields['password'] ?? null;
            }
            ensure(is_string($value) && $value !== '', 'Chybí přihlášení na GitHub.');
            $this->credential = $value;
        }
        return $this->credential;
    }

    public function request(string $method, string $suffix, ?array $data = null): mixed
    {
        ensure(($suffix === '' || str_starts_with($suffix, '/')) && !str_starts_with($suffix, '//') && !str_contains(rawurldecode($suffix), '..') && !preg_match('/[\r\n#]/', $suffix), 'Neplatná cesta GitHub API.');
        ensure(in_array($method, ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'], true), 'Neznámá metoda API.');
        $curl = curl_init('https://api.github.com/repos/' . $this->repository . $suffix);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 60, CURLOPT_USERAGENT => 'nastroje-prace/' . VERSION,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->token(), 'Accept: application/vnd.github+json',
                'X-GitHub-Api-Version: 2022-11-28', 'Content-Type: application/json'],
        ]);
        // Windows používá důvěryhodné certifikáty systému, ověřování TLS se nevypíná.
        if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
            curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
        }
        if ($data !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json($data));
        }
        try {
            $response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            ensure($response !== false, 'GitHub API není dostupné.');
            ensure($status >= 200 && $status < 300, 'GitHub API ' . $method . ' ' . explode('?', $suffix)[0] . ': HTTP ' . $status . '.');
            if ($status === 204) {
                return null;
            }
            try {
                return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new Failure('GitHub API vrátilo neplatnou odpověď.');
            }
        } finally {
            curl_close($curl);
        }
    }

    public function pages(string $suffix): array
    {
        $rows = [];
        for ($page = 1; $page <= 100; $page++) {
            $batch = $this->request('GET', $suffix . (str_contains($suffix, '?') ? '&' : '?') . 'per_page=100&page=' . $page);
            ensure(is_array($batch) && array_is_list($batch), 'GitHub nevrátil seznam.');
            array_push($rows, ...$batch);
            if (count($batch) < 100) {
                return $rows;
            }
        }
        throw new Failure('Překročen limit stránek; kontrola nesmí tiše vynechat další záznamy.');
    }

    public function issue(int $number): array
    {
        ensure($number > 0, 'Neplatné číslo issue.');
        $item = $this->request('GET', '/issues/' . $number);
        ensure(is_array($item) && !isset($item['pull_request']), 'Očekáváno issue, nikoli pull request.');
        return $item;
    }
}
