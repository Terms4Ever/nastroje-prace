<?php
declare(strict_types=1);

namespace NastrojePrace;

final class Issues
{
    public static function create(string $root, array $draft, ApiClient $client): array
    {
        Policy::issue($root, $draft, false);
        foreach ($client->pages('/issues?state=open') as $existing) {
            ensure(mb_strtolower(trim($existing['title']), 'UTF-8') !== mb_strtolower(trim($draft['title']), 'UTF-8'), 'Stejně nazvané otevřené issue již existuje.');
        }
        return $client->request('POST', '/issues', self::payload($draft));
    }

    private static function payload(array $draft): array
    {
        return ['title' => $draft['title'], 'body' => $draft['body'], 'labels' => $draft['labels'], 'assignees' => $draft['assignees']];
    }

    public static function update(string $root, int $number, array $draft, ApiClient $client): array
    {
        $old = $client->issue($number);
        $payload = self::payload($draft);
        Policy::issue($root, [...$old, ...$payload, 'number' => $number]);
        return $client->request('PATCH', '/issues/' . $number, $payload);
    }

    public static function close(string $root, int $number, string $summary, ApiClient $client): array
    {
        $proof = Gate::published($root, $client);
        ensure($proof['online'] === true && in_array($number, $proof['issues'], true), 'Chybí online ověření příslušného úkolu.');
        $record = Policy::taskRecord($root, $number);
        $item = $client->issue($number);
        ensure($item['state'] === 'open', 'Issue již není otevřené.');
        Policy::issue($root, $item, true);
        ensure(in_array('rozhrani', Policy::names($item['labels']), true) === $record['visual'], 'Nesouhlasí klasifikace změny rozhraní.');
        meaningful($summary, 'Výsledek úkolu');
        $lines = [$summary, 'Ověření: https://github.com/' . config($root)['repository'] . '/commit/' . $proof['commit']];
        if ($record['delivery'] === 'svn') {
            $delivery = readJson($root . '/.local/svn/' . $number . '-predano.json');
            ensure($delivery['commit'] === $proof['commit'], 'Chybí doložené SVN předání této verze.');
            $lines[] = 'Předáno do SVN v revizi ' . $delivery['svn_revision'] . '.';
        }
        $comment = implode("\n", $lines);
        Policy::comment($comment);
        $client->request('POST', '/issues/' . $number . '/comments', ['body' => $comment]);
        return $client->request('PATCH', '/issues/' . $number, ['state' => 'closed', 'state_reason' => 'completed']);
    }

    public static function audit(string $root, ApiClient $client, ?int $number = null): int
    {
        $items = $number === null ? $client->pages('/issues?state=all') : [$client->issue($number)];
        $count = 0;
        foreach ($items as $item) {
            if (isset($item['pull_request'])) {
                continue;
            }
            Policy::issue($root, $item);
            foreach ($client->pages('/issues/' . $item['number'] . '/comments') as $comment) {
                Policy::comment($comment['body']);
            }
            $count++;
        }
        return $count;
    }
}
