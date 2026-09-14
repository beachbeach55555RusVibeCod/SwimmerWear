<?php
require dirname(__DIR__) . '/config.php';

function head($title) { ?>
<!doctype html><html lang="ru"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> — SWIMMER CMS</title>
<link rel="stylesheet" href="admin.css"></head><body>
<div class="layout">
<aside>
  <div class="brand">SWIMMER<span>CMS</span></div>
  <nav>
    <a href="index.php">Страницы</a>
    <a href="visuals.php">Фото, видео и надписи</a>
    <a href="blocks.php">Блоки</a>
    <a href="products.php">Товары</a>
    <a href="media.php">Медиа</a>
    <a href="menu.php">Меню</a>
    <a href="settings.php">Настройки</a>
  </nav>
  <a class="out" href="logout.php">Выйти</a>
</aside>
<main>
<?php }

function foot() { ?>
</main></div></body></html>
<?php }
