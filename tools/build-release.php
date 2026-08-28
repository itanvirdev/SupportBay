<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
  fwrite(STDERR, "SupportBay releases can only be built from the command line.\n");
  exit(1);
}

$root = dirname(__DIR__);
$buildDirectory = $root . '/build';
$stageDirectory = $buildDirectory . '/SupportBay';
$ignoreFile = $root . '/.distignore';

if (! is_file($ignoreFile)) {
  fwrite(STDERR, "Missing .distignore.\n");
  exit(1);
}

$pluginHeader = (string) file_get_contents($root . '/supportbay.php');
if (! preg_match('/^ \* Version:\s+([^\s]+)/m', $pluginHeader, $matches)) {
  fwrite(STDERR, "Could not determine the plugin version.\n");
  exit(1);
}

$version = $matches[1];
$archivePath = $buildDirectory . '/supportbay-' . $version . '.zip';
$composerHome = $buildDirectory . '/.composer-home';
$composerCache = $buildDirectory . '/.composer-cache';
$temporaryDirectory = $buildDirectory . '/.tmp';
$ignored = array_values(array_filter(array_map(
  static fn(string $line): string => trim($line),
  file($ignoreFile, FILE_IGNORE_NEW_LINES) ?: [],
), static fn(string $line): bool => $line !== '' && ! str_starts_with($line, '#')));

$remove = static function (string $path) use (&$remove): void {
  if (! file_exists($path) && ! is_link($path)) {
    return;
  }
  if (is_dir($path) && ! is_link($path)) {
    foreach (new FilesystemIterator($path) as $item) {
      $remove($item->getPathname());
    }
    rmdir($path);
    return;
  }
  unlink($path);
};

$isIgnored = static function (string $relative) use ($ignored): bool {
  $relative = str_replace('\\', '/', $relative);
  foreach ($ignored as $pattern) {
    $directory = str_ends_with($pattern, '/');
    $normalized = rtrim($pattern, '/');
    if ($relative === $normalized || ($directory && str_starts_with($relative, $normalized . '/'))) {
      return true;
    }
    if ($pattern === '.DS_Store' && basename($relative) === '.DS_Store') {
      return true;
    }
  }
  return false;
};

$remove($stageDirectory);
if (! is_dir($buildDirectory) && ! mkdir($buildDirectory, 0775, true) && ! is_dir($buildDirectory)) {
  throw new RuntimeException('Could not create the build directory.');
}
mkdir($stageDirectory, 0775, true);
foreach ([$composerHome, $composerCache, $temporaryDirectory] as $runtimeDirectory) {
  if (! is_dir($runtimeDirectory)) {
    mkdir($runtimeDirectory, 0775, true);
  }
}

$iterator = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
  RecursiveIteratorIterator::SELF_FIRST,
);

foreach ($iterator as $item) {
  $relative = substr($item->getPathname(), strlen($root) + 1);
  if ($isIgnored($relative)) {
    continue;
  }
  $destination = $stageDirectory . '/' . $relative;
  if ($item->isDir()) {
    if (! is_dir($destination)) {
      mkdir($destination, 0775, true);
    }
    continue;
  }
  if (! is_dir(dirname($destination))) {
    mkdir(dirname($destination), 0775, true);
  }
  copy($item->getPathname(), $destination);
}

copy($root . '/composer.json', $stageDirectory . '/composer.json');
if (is_file($root . '/composer.lock')) {
  copy($root . '/composer.lock', $stageDirectory . '/composer.lock');
}

$composerPath = null;
foreach (explode(PATH_SEPARATOR, (string) getenv('PATH')) as $pathDirectory) {
  $candidate = rtrim($pathDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'composer';
  if (is_file($candidate) && is_executable($candidate)) {
    $composerPath = $candidate;
    break;
  }
}
if ($composerPath === null) {
  $remove($stageDirectory);
  fwrite(STDERR, "Composer could not be found in PATH.\n");
  exit(1);
}

$command = [
  PHP_BINARY, '-d', 'sys_temp_dir=' . $temporaryDirectory, $composerPath,
  'install', '--working-dir=' . $stageDirectory, '--no-dev',
  '--no-interaction', '--no-progress', '--no-security-blocking', '--classmap-authoritative', '--no-scripts',
];
$environment = array_merge($_ENV, [
  'COMPOSER_HOME' => $composerHome,
  'COMPOSER_CACHE_DIR' => $composerCache,
  'PATH' => (string) getenv('PATH'),
]);
$process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, $root, $environment);
$exitCode = is_resource($process) ? proc_close($process) : 1;
$remove($composerHome);
$remove($composerCache);
$remove($temporaryDirectory);
if ($exitCode !== 0 || ! is_file($stageDirectory . '/vendor/autoload.php')) {
  $remove($stageDirectory);
  fwrite(STDERR, "Production Composer autoloading could not be built.\n");
  exit(1);
}

unlink($stageDirectory . '/composer.json');
if (is_file($stageDirectory . '/composer.lock')) {
  unlink($stageDirectory . '/composer.lock');
}

$remove($archivePath);
$zip = new ZipArchive();
if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
  $remove($stageDirectory);
  throw new RuntimeException('Could not create the release archive.');
}

$files = new RecursiveIteratorIterator(
  new RecursiveDirectoryIterator($stageDirectory, FilesystemIterator::SKIP_DOTS),
);
foreach ($files as $file) {
  if ($file->isFile()) {
    $relative = substr($file->getPathname(), strlen($stageDirectory) + 1);
    $zip->addFile($file->getPathname(), 'SupportBay/' . str_replace('\\', '/', $relative));
  }
}
$zip->close();
$remove($stageDirectory);

echo $archivePath . PHP_EOL;
