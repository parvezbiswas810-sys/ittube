<?php
/**
 * IT TUBE — storage/permission repair.
 * Upload next to index.php (api.adsxuit.com/), open with ?key=..., then DELETE this file.
 * Key: 722fa169d71d460a
 */
declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');

if (($_GET['key'] ?? '') !== '722fa169d71d460a') {
    http_response_code(403);
    exit("Forbidden. Open this file with the key from the download page.\n");
}

$candidates = ['ittube_backend', 'ittube-backend/ittube_backend', 'ittube_backend/ittube_backend', 'cpanel-package/ittube_backend'];
$backend = null;
$dir = __DIR__;
for ($i = 0; $i < 6; $i++) {
    foreach ($candidates as $c) {
        if (is_file($dir.'/'.$c.'/bootstrap/app.php')) { $backend = $dir.'/'.$c; break 2; }
    }
    $p = dirname($dir);
    if ($p === $dir) { break; }
    $dir = $p;
}

if ($backend === null) {
    http_response_code(500);
    exit("ittube_backend not found. Upload the package first (see the deploy guide).\n");
}

echo "IT TUBE storage repair\n";
echo "backend: $backend\n";
echo str_repeat('-', 58)."\n";

$dirs = [
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
];

foreach ($dirs as $rel) {
    $path = $backend.'/'.$rel;
    if (! is_dir($path)) {
        @mkdir($path, 0755, true);
        echo "created  $rel\n";
    }
    $ok = @chmod($path, 0755);
    printf("%-8s %-34s %s\n", $ok ? 'chmod' : 'FAILED', $rel, is_writable($path) ? 'writable' : 'NOT writable');
}

// stale caches carry build-machine paths
foreach (glob($backend.'/bootstrap/cache/*.php') ?: [] as $file) {
    @unlink($file);
    echo "removed  bootstrap/cache/".basename($file)."\n";
}

echo str_repeat('-', 58)."\n";
foreach (['.env' => 'file', 'vendor/autoload.php' => 'file', 'public/index.php' => 'file'] as $rel => $type) {
    printf("%-10s %s\n", is_file($backend.'/'.$rel) ? 'found' : 'MISSING', $rel);
}

if (is_file($backend.'/.env')) {
    $env = (string) file_get_contents($backend.'/.env');
    preg_match('/^APP_KEY=(.*)$/m', $env, $m);
    echo 'APP_KEY  '.(trim($m[1] ?? '') === '' ? 'empty (will be generated on first page load)' : 'set')."\n";
}

echo str_repeat('-', 58)."\n";
echo "Done. Now open https://".($_SERVER['HTTP_HOST'] ?? 'your-domain')."/install\n";
echo "IMPORTANT: delete this file (fix-storage.php) from the domain folder now.\n";
