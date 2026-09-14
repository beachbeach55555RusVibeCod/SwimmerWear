<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$out = ['scenarios'=>null,'build'=>null,'kit'=>null];
try {
  $pageId = (int)db()->query("SELECT id FROM pages WHERE slug='/' LIMIT 1")->fetchColumn();
  if ($pageId) {
    $st = db()->prepare("SELECT type,data FROM blocks WHERE page_id=? AND type IN ('scenarios','build','kit') ORDER BY id");
    $st->execute([$pageId]);
    foreach ($st->fetchAll() as $b) {
      $data = json_decode($b['data'] ?? '{}', true);
      if (is_array($data)) $out[$b['type']] = $data;
    }
  }
} catch (Throwable $e) {
  http_response_code(500);
}

echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
