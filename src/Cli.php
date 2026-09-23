<?php
declare(strict_types=1);

namespace NastrojePrace;

final class Cli
{
    public static function parse(array $args): array
    {
        $root = getcwd();
        if (($args[0] ?? '') === '--root') {
            ensure(isset($args[1]), 'Parametr --root vyžaduje cestu.');
            $root = $args[1];
            $args = array_slice($args, 2);
        }
        $root = realpath($root);
        ensure($root !== false, 'Kořen projektu neexistuje.');
        $command = array_shift($args) ?? 'help';
        $specs = [
            'help' => [0, []], 'bootstrap' => [0, ['php-windows' => false]], 'doctor' => [0, []], 'install-hooks' => [0, []],
            'check' => [0, ['base' => true, 'online' => false]], 'commit-check' => [1, []], 'readme-check' => [0, []],
            'metadata-check' => [0, []], 'issue-check' => [1, []], 'issue-create' => [1, []], 'issue-update' => [2, []],
            'issue-close' => [1, ['summary' => true]], 'issues-check' => [0, ['number' => true]],
            'project-init' => [1, ['dest' => true]], 'project-check' => [0, ['online' => false]], 'state-update' => [0, []],
            'svn-zaklad' => [1, []], 'predani-priprav' => [1, []], 'predani-over' => [1, []], 'predani-zapis' => [1, ['revision' => true]],
        ];
        ensure(isset($specs[$command]), 'Neznámý příkaz. Použij php prace.php help.');
        [$count, $known] = $specs[$command];
        $positional = $options = [];
        while ($args !== []) {
            $arg = array_shift($args);
            if (str_starts_with($arg, '--')) {
                $key = substr($arg, 2);
                ensure(array_key_exists($key, $known) && !array_key_exists($key, $options), 'Neznámý nebo opakovaný parametr.');
                if ($known[$key]) {
                    ensure(isset($args[0]) && $args[0] !== '' && !str_starts_with($args[0], '--'), 'Chybí hodnota parametru ' . $key . '.');
                    $options[$key] = array_shift($args);
                } else {
                    $options[$key] = true;
                }
            } else {
                $positional[] = $arg;
            }
        }
        ensure(count($positional) === $count, 'Nesprávný počet argumentů příkazu ' . $command . '.');
        foreach (['check' => 'base', 'issue-close' => 'summary', 'predani-zapis' => 'revision', 'project-init' => 'dest'] as $name => $required) {
            ensure($command !== $name || isset($options[$required]), 'Chybí povinný parametr --' . $required . '.');
        }
        return [$root, $command, $positional, $options];
    }

    private static function number(string $value): int
    {
        ensure((bool) preg_match('/^[1-9][0-9]*$/D', $value) && strlen($value) < 15, 'Je vyžadováno kladné celé číslo.');
        return (int) $value;
    }

    public static function main(array $args): void
    {
        [$root, $command, $pos, $options] = self::parse($args);
        if ($command === 'help') {
            echo "Použití: php prace.php [--root CESTA] PŘÍKAZ\n\n";
            echo "bootstrap | doctor | install-hooks | readme-check | metadata-check\n";
            echo "check --base COMMIT [--online]\n";
            echo "project-init JSON --dest PRAZDNA_SLOZKA | project-check [--online] | state-update\n";
            echo "commit-check SOUBOR | issue-check JSON | issue-create JSON\n";
            echo "issue-update ČÍSLO JSON | issue-close ČÍSLO --summary TEXT\n";
            echo "issues-check [--number ČÍSLO]\n";
            echo "svn-zaklad ČÍSLO | predani-priprav ČÍSLO | predani-over MANIFEST\n";
            echo "predani-zapis MANIFEST --revision REVIZE\n";
            return;
        }
        Upstream::runtime();
        Project::verifyIfBound($root);
        if ($command === 'bootstrap') {
            $result = ['upstream' => Upstream::bootstrap()];
        } elseif ($command === 'doctor') {
            $hook = run(['git', '-C', $root, 'config', '--get', 'core.hooksPath'], check: false);
            $result = ['version' => VERSION, 'php' => PHP_VERSION, 'git' => git($root, '--version'), 'upstream' => Upstream::verify(),
                'repository' => config($root)['repository'], 'hooks' => trim($hook['stdout']) ?: 'nenainstalované'];
        } elseif ($command === 'install-hooks') {
            $result = Project::installHooks($root);
        } elseif ($command === 'project-init') {
            $result = Project::initialize(readJson($pos[0]), $options['dest']);
        } elseif ($command === 'project-check') {
            $result = Project::check($root, isset($options['online']) ? new GitHub(config($root)['repository']) : null);
        } elseif ($command === 'state-update') {
            $result = ['output' => Upstream::check('stav-projektu.php', [$root, '--zapsat'])];
        } elseif ($command === 'commit-check') {
            $result = ['issue' => Policy::commit(readFile($pos[0]))];
        } elseif ($command === 'readme-check') {
            Policy::readme($root);
            $result = ['success' => true];
        } elseif ($command === 'issue-check') {
            Policy::issue($root, readJson($pos[0]));
            $result = ['success' => true];
        } elseif ($command === 'check') {
            $online = isset($options['online']);
            $result = Gate::verify($root, $options['base'], $online, $online ? new GitHub(config($root)['repository']) : null);
        } elseif ($command === 'metadata-check') {
            Policy::repositoryMetadata($root, new GitHub(config($root)['repository']));
            $result = ['private' => true, 'topics' => config($root)['topics']];
        } elseif (str_starts_with($command, 'issue')) {
            $client = new GitHub(config($root)['repository']);
            if ($command === 'issue-create') {
                $item = Issues::create($root, readJson($pos[0]), $client);
                $result = ['number' => $item['number'], 'url' => $item['html_url']];
            } elseif ($command === 'issue-update') {
                $item = Issues::update($root, self::number($pos[0]), readJson($pos[1]), $client);
                $result = ['number' => $item['number'], 'url' => $item['html_url']];
            } elseif ($command === 'issue-close') {
                $item = Issues::close($root, self::number($pos[0]), $options['summary'], $client);
                $result = ['number' => $item['number'], 'state' => $item['state']];
            } else {
                $result = ['checked' => Issues::audit($root, $client, isset($options['number']) ? self::number($options['number']) : null)];
            }
        } elseif ($command === 'svn-zaklad') {
            $result = Svn::baseline($root, self::number($pos[0]));
        } elseif ($command === 'predani-priprav') {
            $result = ['manifest' => Svn::prepare($root, self::number($pos[0]))];
        } elseif ($command === 'predani-over') {
            $result = Svn::verifyPackage($root, $pos[0]);
        } else {
            $result = Svn::recordDelivery($root, $pos[0], self::number($options['revision']));
        }
        echo json($result);
    }
}
