<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
  $st = db()->prepare("SELECT id FROM pages WHERE slug='/' AND status='published' LIMIT 1");
  $st->execute();
  $pageId = (int)$st->fetchColumn();
  $out = ['blocks'=>[], 'brand'=>[]];

  if ($pageId) {
    $st = db()->prepare("SELECT type,data FROM blocks WHERE page_id=? AND visible=1 ORDER BY sort,id");
    $st->execute([$pageId]);
    foreach ($st->fetchAll() as $row) {
      if (!in_array($row['type'], ['hero','scenarios','build','kit'], true)) continue;
      $out['blocks'][$row['type']] = json_decode($row['data'] ?? '{}', true) ?: [];
    }
  }

  $out['brand'] = [
    'label' => setting('brand_story_label', 'О БРЕНДЕ'),
    'title' => setting('brand_story_title', 'Мы просто всегда любили воду'),
    'text' => setting('brand_story_text', "SWIMMER вырос из простого желания проводить больше времени у воды — не думая о ветре, сырости и переменчивой погоде.\n\nМы делаем вещи спокойными по характеру и практичными по сути. В основе — защита от дождя и ветра, свободная посадка, тёплая мягкая подкладка и материалы, которые рассчитаны не на витрину, а на реальное использование.\n\nДля нас продукт — это не сезонная декорация, а надёжная вещь для поездок, берега, лодки, дачи и долгих прогулок. Конструкцию и материалы подбираем так, чтобы парку было удобно носить, хранить и брать с собой.\n\nПроизводство строится вокруг понятных решений: мембранная ткань, проклеенные швы, функциональные детали и контроль качества на каждом этапе."),
    'link_text' => setting('brand_story_link_text', 'Выбрать надежную вещь для отдыха на природе.'),
    'media_src' => setting('brand_story_media_src', 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&q=88&w=2200'),
    'media_label' => setting('brand_story_media_label', 'Вода и река'),
  ];

  echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error'=>'content_unavailable'], JSON_UNESCAPED_UNICODE);
}
