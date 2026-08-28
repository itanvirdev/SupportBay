<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  fwrite(STDERR, "SupportBay flow tests may only be started from the command line.\n");
  exit(1);
}

$test = isset($argv[1]) ? preg_replace('/[^a-z0-9-]/', '', strtolower((string) $argv[1])) : 'all';

define('SBAY_ENABLE_FLOW_TESTS', true);
$_GET['sbay_test'] = $test !== '' ? $test : 'all';

ob_start();
require dirname(__DIR__, 4) . '/wp-load.php';
