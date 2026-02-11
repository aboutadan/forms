<?php
declare(strict_types=1);

function load_dotenv(string $path): void {
  if (!is_file($path)) {
    return;
  }
  $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if ($lines === false) {
    return;
  }
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
      continue;
    }
    $parts = explode('=', $line, 2);
    if (count($parts) !== 2) {
      continue;
    }
    $key = trim($parts[0]);
    $value = trim($parts[1]);
    if (strlen($value) >= 2 && $value[0] === '"' && $value[strlen($value) - 1] === '"') {
      $value = substr($value, 1, -1);
    }
    if (getenv($key) === false) {
      putenv($key . '=' . $value);
    }
  }
}

load_dotenv(__DIR__ . DIRECTORY_SEPARATOR . '.env');

$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_DATABASE') ?: 'forms';
$dbUser = getenv('DB_USERNAME') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: 'root';

$schemaPath = __DIR__ . DIRECTORY_SEPARATOR . 'schema.sql';
if (!is_file($schemaPath)) {
  fwrite(STDERR, "schema.sql not found at $schemaPath\n");
  exit(1);
}

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);

try {
  $pdo = new PDO($dsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  ]);

  $sql = file_get_contents($schemaPath);
  if ($sql === false) {
    throw new RuntimeException('Unable to read schema.sql');
  }

  // Split on semicolons that end a statement.
  $statements = array_filter(array_map('trim', preg_split('/;\\s*\\n/', $sql)));
  foreach ($statements as $statement) {
    if ($statement === '') {
      continue;
    }
    $pdo->exec($statement);
  }

  fwrite(STDOUT, "Migration completed.\n");
} catch (Throwable $e) {
  fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
  exit(1);
}
