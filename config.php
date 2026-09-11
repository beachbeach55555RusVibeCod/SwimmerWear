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

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) session_start();

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
function need_auth() { if (!is_auth()) { header('Location: login.php'); exit; } }
function go($url) { header('Location: ' . $url); exit; }
function setting($key, $default = '') {
  static $all = null;
  if ($all === null) {
    $all = [];
    foreach (db()->query("SELECT `k`,`v` FROM settings") as $r) $all[$r['k']] = $r['v'];
  }
  return $all[$key] ?? $default;
}

/** Нижний регистр с поддержкой кириллицы, если есть mbstring. */
function lc($s) {
  return function_exists('mb_strtolower') ? mb_strtolower((string)$s, 'UTF-8') : strtolower((string)$s);
}
