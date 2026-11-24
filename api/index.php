<?php
$rootDir = realpath(__DIR__ . '/..');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$relativePath = ltrim($requestPath, '/');

if ($relativePath === '' || $relativePath === '/') {
    $relativePath = 'index.php';
} elseif (is_dir($rootDir . DIRECTORY_SEPARATOR . $relativePath)) {
    $relativePath = rtrim($relativePath, '/') . '/index.php';
}

// Block direct access to internal folders or this router itself
$blockedPrefixes = ['includes/', 'layout/', 'api/', '.git/'];
foreach ($blockedPrefixes as $prefix) {
    if (str_starts_with($relativePath, $prefix)) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
}

$targetFile = realpath($rootDir . DIRECTORY_SEPARATOR . $relativePath);

if (
    $targetFile === false ||
    !str_starts_with($targetFile, $rootDir . DIRECTORY_SEPARATOR) ||
    !is_file($targetFile) ||
    pathinfo($targetFile, PATHINFO_EXTENSION) !== 'php'
) {
    http_response_code(404);
    echo '404 Not Found';
    exit;
}

$_SERVER['SCRIPT_FILENAME'] = $targetFile;
$_SERVER['SCRIPT_NAME'] = '/' . $relativePath;
$_SERVER['PHP_SELF'] = '/' . $relativePath;

chdir(dirname($targetFile));
require $targetFile;
