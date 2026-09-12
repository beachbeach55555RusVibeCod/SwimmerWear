<?php
// ---- Настройки БД (reg.ru) ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'ИМЯ_БД');
define('DB_USER', 'ПОЛЬЗОВАТЕЛЬ_БД');
define('DB_PASS', 'ПАРОЛЬ_БД');

// ---- Общее ----
define('BASE', __DIR__);
define('UPLOAD_DIR', BASE . '/uploads');
define('UPLOAD_URL', '/uploads');

// В production ошибки не должны попадать в HTML ответа.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax'
  ]);
  session_start();
}

function db(): PDO {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO(
      'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
      DB_USER, DB_PASS,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
       PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
       PDO::ATTR_EMULATE_PREPARES => false]
    );
  }
  return $pdo;
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function is_auth(): bool { return !empty($_SESSION['uid']); }
function need_auth() {
  if (!is_auth()) { header('Location: login.php'); exit; }
  // CSRF защита админских POST без необходимости доверять скрытым полям форм.
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $originOk = $origin === '' || parse_url($origin, PHP_URL_HOST) === preg_replace('/:\d+$/', '', $host);
    $refererOk = $referer === '' || parse_url($referer, PHP_URL_HOST) === preg_replace('/:\d+$/', '', $host);
    if (!$originOk || !$refererOk) {
      http_response_code(403);
      exit('Forbidden');
    }
  }
}
function go($url) { header('Location: ' . $url); exit; }
function setting($key, $default = '') {
  static $all = null;
  if ($all === null) {
    $all = [];
    foreach (db()->query("SELECT `k`,`v` FROM settings") as $r) $all[$r['k']] = $r['v'];
  }
  return $all[$key] ?? $default;
}

// Повторный запуск публичного installer после создания первого администратора запрещён.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'install.php') {
  try {
    $pdo = db();
    $hasUsers = (bool)$pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
    if ($hasUsers && (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
      http_response_code(403);
      exit('Установка уже выполнена. Удалите install.php с сервера.');
    }
  } catch (Throwable $e) {
    // БД ещё не установлена — installer должен иметь возможность создать таблицы.
  }
}

// Автоматически подключаем production-fixes.js только к витрине.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === 'index.php') {
  register_shutdown_function(function () {
    echo '<script src="/assets/production-fixes.js" defer></script>';
  });
}
