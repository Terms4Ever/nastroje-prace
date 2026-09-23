<?php
declare(strict_types=1);

require_once __DIR__ . '/src/load.php';
exit(\NastrojePrace\entry(fn() => \NastrojePrace\Cli::main(array_slice($argv, 1))));
