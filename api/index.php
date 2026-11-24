<?php
/**
 * Vercel Serverless PHP Front Controller
 * Routes requests to the appropriate PHP page and provides
 * database-backed PHP sessions when running on Vercel.
 */

if (getenv('VERCEL') && getenv('DB_HOST')) {
    class VercelPdoSessionHandler implements SessionHandlerInterface
    {
        private ?PDO $pdo = null;

        private function connect(): ?PDO
        {
            if ($this->pdo !== null) {
                return $this->pdo;
            }
            $host = getenv('DB_HOST');
            $port = getenv('DB_PORT') ?: '3306';
            $dbname = getenv('DB_NAME') ?: 'db_lms';
            $username = getenv('DB_USER') ?: 'root';
            $password = getenv('DB_PASS');
            if ($password === false) {
                $password = '';
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
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
                $this->pdo = new PDO($dsn, $username, $password, $options);
                $this->pdo->exec(
                    "CREATE TABLE IF NOT EXISTS php_sessions (
                        id VARCHAR(128) PRIMARY KEY,
                        data MEDIUMTEXT NOT NULL,
                        last_access INT UNSIGNED NOT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                );
            } catch (Throwable $e) {
                return null;
            }
            return $this->pdo;
        }

        public function open(string $path, string $name): bool
        {
            return $this->connect() !== null;
        }

        public function close(): bool
        {
            return true;
        }

        public function read(string $id): string|false
        {
            $pdo = $this->connect();
            if (!$pdo) {
                return '';
            }
            $stmt = $pdo->prepare('SELECT data FROM php_sessions WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (string) $row['data'] : '';
        }

        public function write(string $id, string $data): bool
        {
            $pdo = $this->connect();
            if (!$pdo) {
                return false;
            }
            $stmt = $pdo->prepare(
                'INSERT INTO php_sessions (id, data, last_access) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE data = VALUES(data), last_access = VALUES(last_access)'
            );
            return $stmt->execute([$id, $data, time()]);
        }

        public function destroy(string $id): bool
        {
            $pdo = $this->connect();
            if (!$pdo) {
                return true;
            }
            $stmt = $pdo->prepare('DELETE FROM php_sessions WHERE id = ?');
            return $stmt->execute([$id]);
        }

        public function gc(int $max_lifetime): int|false
        {
            $pdo = $this->connect();
            if (!$pdo) {
                return 0;
            }
            $stmt = $pdo->prepare('DELETE FROM php_sessions WHERE last_access < ?');
            $stmt->execute([time() - $max_lifetime]);
            return $stmt->rowCount();
        }
    }

    session_set_save_handler(new VercelPdoSessionHandler(), true);
}

$rootDir = realpath(__DIR__ . '/..');
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$relativePath = ltrim($requestPath, '/');

if ($relativePath === '' || $relativePath === '/') {
    $relativePath = 'index.php';
} elseif (is_dir($rootDir . DIRECTORY_SEPARATOR . $relativePath)) {
    $relativePath = rtrim($relativePath, '/') . '/index.php';
}

// Block direct access to internal folders or this router itself
$blockedPrefixes = ['includes/', 'layout/', 'api/', 'database/', '.git/'];
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
