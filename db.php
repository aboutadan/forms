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

function env_value(string $key, string $default): string {
  $value = getenv($key);
  if ($value === false || $value === '') {
    return $default;
  }
  return $value;
}

function db(): PDO {
  $dbName = env_value("DB_NAME", env_value("DB_DATABASE", "forms"));
  $dbUser = env_value("DB_USER", env_value("DB_USERNAME", "root"));
  $dbPass = env_value("DB_PASSWORD", "");

  $dsn = sprintf(
    "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
    env_value("DB_HOST", "localhost"),
    env_value("DB_PORT", "3306"),
    $dbName
  );

  return new PDO($dsn, $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
}
