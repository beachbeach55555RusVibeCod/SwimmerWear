<?php
require dirname(__DIR__) . '/config.php';

if (basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH)) === 'visuals.php') {
  need_auth();
  require __DIR__ . '/ensure-media-blocks.php';
}

function head($title) { ?>
<!doctype html><html lang="ru"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> — SWIMMER CMS</title>
<link rel="stylesheet" href="admin.css">
<script src="product-color-presets.js?v=2" defer></script>
<script src="product-photo-colors.js?v=2" defer></script>
<script src="media-size-hints.js?v=2" defer></script>
</head><body>
<div class="layout">
<aside>
  <div class="brand">SWIMMER<span>CMS</span></div>
  <nav>
    <a href="product-cards.php">Карточки товара</a>
    <a href="visuals.php">Редактор медиа</a>
  </nav>
  <a class="out" href="logout.php">Выйти</a>
</aside>
<main>
<?php }

function foot() { ?>
</main></div></body></html>
<?php }
