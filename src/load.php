<?php
declare(strict_types=1);

namespace NastrojePrace;

const TOOL_ROOT = __DIR__ . '/..';
const VERSION = '0.5.0';

require_once __DIR__ . '/Common.php';
require_once __DIR__ . '/Upstream.php';
require_once __DIR__ . '/GitHub.php';
require_once __DIR__ . '/Policy.php';
require_once __DIR__ . '/MainBranch.php';
require_once __DIR__ . '/Gate.php';
require_once __DIR__ . '/Svn.php';
require_once __DIR__ . '/Issues.php';
require_once __DIR__ . '/Project.php';
require_once __DIR__ . '/Cli.php';
