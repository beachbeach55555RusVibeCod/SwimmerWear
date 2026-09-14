<?php
require 'inc.php';
need_auth();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
  exit;
}

$pid = (int)($_POST['pid'] ?? 0);
$color = trim((string)($_POST['color'] ?? ''));
$mode = $_POST['mode'] ?? 'one';

if ($pid <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Не указан товар'], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($mode === 'all') {
  $st = db()->prepare("UPDATE product_media SET color=? WHERE product_id=?");
  $st->execute([$color, $pid]);
  echo json_encode(['ok'=>true,'updated'=>$st->rowCount()], JSON_UNESCAPED_UNICODE);
  exit;
}

$photoId = (int)($_POST['photo_id'] ?? 0);
if ($photoId <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Не указано фото'], JSON_UNESCAPED_UNICODE);
  exit;
}

$st = db()->prepare("UPDATE product_media SET color=? WHERE id=? AND product_id=?");
$st->execute([$color, $photoId, $pid]);
echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
