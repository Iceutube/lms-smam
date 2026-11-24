<?php
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'db_lms';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS');
if ($password === false) {
    $password = '';
}

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

if (getenv('DB_SSL') === 'true') {
    $caPath = getenv('DB_SSL_CA');
    if ($caPath && file_exists($caPath)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
    } else {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    if (getenv('VERCEL') && !getenv('DB_HOST')) {
        http_response_code(503);
        die("Database belum dikonfigurasi. Tambahkan environment variables DB_HOST, DB_PORT, DB_NAME, DB_USER, dan DB_PASS di pengaturan proyek Vercel.");
    }
    die("Koneksi gagal: " . $e->getMessage());
}
?>
