<?php
require 'inc.php';
need_auth();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

function size_json($data, $code = 200) {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function size_key($pid) {
  return 'product_sizes_' . (int)$pid;
}

function size_clean($value) {
  $value = trim(preg_replace('/\s+/u', ' ', (string)$value));
  if ($value === '') return '';
  $upper = mb_strtoupper($value, 'UTF-8');
  if (preg_match('/^X{0,4}[SML]$/', $upper) || preg_match('/^X{2,5}L$/', $upper)) return $upper;
  if (mb_strtolower($value, 'UTF-8') === 'универсальный') return 'Универсальный';
  return $value;
}

function size_unique($sizes) {
  $out = []; $seen = [];
  foreach ((array)$sizes as $size) {
    $size = size_clean($size);
    if ($size === '' || mb_strlen($size, 'UTF-8') > 16) continue;
    $key = mb_strtolower($size, 'UTF-8');
    if (isset($seen[$key])) continue;
    $seen[$key] = true;
    $out[] = $size;
  }
  return $out;
}

function configured_sizes($pid, $persist = true) {
  $raw = trim((string)setting(size_key($pid), ''));
  if ($raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) return size_unique($decoded);
  }
  $st = db()->prepare("SELECT DISTINCT size FROM variants WHERE product_id=? AND size<>'' ORDER BY id");
  $st->execute([$pid]);
  $sizes = size_unique($st->fetchAll(PDO::FETCH_COLUMN));
  if (!$sizes) $sizes = ['XS','S','M','L','XL'];
  if ($persist) {
    db()->prepare("INSERT INTO settings (`k`,`v`) VALUES (?,?) ON DUPLICATE KEY UPDATE `v`=VALUES(`v`)")
      ->execute([size_key($pid), json_encode($sizes, JSON_UNESCAPED_UNICODE)]);
  }
  return $sizes;
}

function save_sizes($pid, $sizes) {
  $sizes = size_unique($sizes);
  db()->prepare("INSERT INTO settings (`k`,`v`) VALUES (?,?) ON DUPLICATE KEY UPDATE `v`=VALUES(`v`)")
    ->execute([size_key($pid), json_encode($sizes, JSON_UNESCAPED_UNICODE)]);
  return $sizes;
}

function product_colors_for_sizes($pid) {
  $st = db()->prepare("SELECT DISTINCT color FROM variants WHERE product_id=? AND color<>'' ORDER BY id");
  $st->execute([$pid]);
  return array_values(array_filter(array_map('trim', $st->fetchAll(PDO::FETCH_COLUMN))));
}

function sync_product_sizes($pid, $sizes) {
  $colors = product_colors_for_sizes($pid);
  if (!$colors) return;
  $pdo = db();
  $ins = $pdo->prepare("INSERT INTO variants (product_id,color,size,stock) VALUES (?,?,?,0) ON DUPLICATE KEY UPDATE size=VALUES(size)");
  foreach ($colors as $color) foreach ($sizes as $size) $ins->execute([$pid,$color,$size]);

  $marks = implode(',', array_fill(0, count($sizes), '?'));
  $args = array_merge([$pid], $sizes);
  $pdo->prepare("DELETE FROM variants WHERE product_id=? AND size NOT IN ($marks)")->execute($args);
}

$pid = (int)($_GET['pid'] ?? $_POST['pid'] ?? 0);
if ($pid < 1) size_json(['ok'=>false,'error'=>'Товар не выбран.'], 400);
$st = db()->prepare('SELECT id FROM products WHERE id=? LIMIT 1');
$st->execute([$pid]);
if (!$st->fetchColumn()) size_json(['ok'=>false,'error'=>'Товар не найден.'], 404);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $sizes = configured_sizes($pid);
  size_json(['ok'=>true,'sizes'=>$sizes,'colors'=>product_colors_for_sizes($pid)]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') size_json(['ok'=>false,'error'=>'Метод не поддерживается.'], 405);
$action = $_POST['a'] ?? '';
$sizes = configured_sizes($pid);

if ($action === 'add') {
  $size = size_clean($_POST['size'] ?? '');
  if ($size === '') size_json(['ok'=>false,'error'=>'Введите размер.'], 400);
  if (mb_strlen($size, 'UTF-8') > 16) size_json(['ok'=>false,'error'=>'Название размера — максимум 16 символов.'], 400);
  if (!product_colors_for_sizes($pid)) size_json(['ok'=>false,'error'=>'Сначала добавьте товару хотя бы один цвет.'], 400);
  $sizes[] = $size;
  $sizes = save_sizes($pid, $sizes);
  sync_product_sizes($pid, $sizes);
  size_json(['ok'=>true,'sizes'=>$sizes]);
}

if ($action === 'delete') {
  $size = size_clean($_POST['size'] ?? '');
  $left = array_values(array_filter($sizes, fn($s) => mb_strtolower($s,'UTF-8') !== mb_strtolower($size,'UTF-8')));
  if (count($left) === count($sizes)) size_json(['ok'=>true,'sizes'=>$sizes]);
  if (!$left) size_json(['ok'=>false,'error'=>'У товара должен остаться хотя бы один размер.'], 400);
  $left = save_sizes($pid, $left);
  db()->prepare('DELETE FROM variants WHERE product_id=? AND size=?')->execute([$pid,$size]);
  size_json(['ok'=>true,'sizes'=>$left]);
}

if ($action === 'sync') {
  sync_product_sizes($pid, $sizes);
  size_json(['ok'=>true,'sizes'=>$sizes]);
}

size_json(['ok'=>false,'error'=>'Неизвестное действие.'], 400);
