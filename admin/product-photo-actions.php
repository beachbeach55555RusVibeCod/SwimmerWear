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
$mode = (string)($_POST['mode'] ?? '');
$ids = $_POST['ids'] ?? [];
if (!is_array($ids)) $ids = [$ids];
$ids = array_values(array_unique(array_filter(array_map('intval', $ids), function($v){ return $v > 0; })));

if ($pid <= 0 || !$ids) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'Не выбраны фотографии'], JSON_UNESCAPED_UNICODE);
  exit;
}

$marks = implode(',', array_fill(0, count($ids), '?'));
$params = array_merge([$pid], $ids);

if ($mode === 'color') {
  $color = trim((string)($_POST['color'] ?? ''));
  if ($color === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Выбери цвет'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $check = db()->prepare("SELECT COUNT(*) FROM variants WHERE product_id=? AND color=?");
  $check->execute([$pid, $color]);
  if (!(int)$check->fetchColumn()) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Такого цвета нет у этого товара'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $st = db()->prepare("UPDATE product_media SET color=? WHERE product_id=? AND id IN ($marks)");
  $st->execute(array_merge([$color, $pid], $ids));
  echo json_encode(['ok'=>true,'updated'=>$st->rowCount()], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($mode === 'delete') {
  $st = db()->prepare("SELECT pm.id,pm.media_id,m.file FROM product_media pm JOIN media m ON m.id=pm.media_id WHERE pm.product_id=? AND pm.id IN ($marks)");
  $st->execute($params);
  $rows = $st->fetchAll();
  $deleted = [];
  foreach ($rows as $row) {
    db()->prepare("DELETE FROM product_media WHERE id=? AND product_id=?")->execute([(int)$row['id'], $pid]);
    $used = db()->prepare("SELECT COUNT(*) FROM product_media WHERE media_id=?");
    $used->execute([(int)$row['media_id']]);
    if (!(int)$used->fetchColumn()) {
      if (!preg_match('~^https?://~i', (string)$row['file'])) @unlink(UPLOAD_DIR . '/' . $row['file']);
      db()->prepare("DELETE FROM media WHERE id=?")->execute([(int)$row['media_id']]);
    }
    $deleted[] = (int)$row['id'];
  }
  echo json_encode(['ok'=>true,'deleted'=>$deleted], JSON_UNESCAPED_UNICODE);
  exit;
}

http_response_code(400);
echo json_encode(['ok'=>false,'error'=>'Неизвестное действие'], JSON_UNESCAPED_UNICODE);
