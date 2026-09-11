<?php
require __DIR__ . '/config.php';

$slug = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$slug = rtrim($slug, '/');
if ($slug === '') $slug = '/';

try {
  $st = db()->prepare("SELECT * FROM pages WHERE slug=? AND status='published' LIMIT 1");
  $st->execute([$slug]);
  $page = $st->fetch();
} catch (Throwable $e) {
  http_response_code(500);
  exit('<h1>Нет связи с БД</h1><p>Запусти <a href="/install.php">install.php</a></p>');
}

if (!$page) { http_response_code(404); $page = ['title'=>'Страница не найдена','seo_title'=>'404','seo_desc'=>'','id'=>0]; $blocks = []; }
else {
  $st = db()->prepare("SELECT * FROM blocks WHERE page_id=? AND visible=1 ORDER BY sort");
  $st->execute([$page['id']]);
  $blocks = $st->fetchAll();
}

$menu = db()->query("SELECT * FROM menu WHERE place='header' AND visible=1 ORDER BY sort")->fetchAll();
$fmenu = db()->query("SELECT * FROM menu WHERE place='footer' AND visible=1 ORDER BY sort")->fetchAll();
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($page['seo_title'] ?: $page['title']) ?></title>
<meta name="description" content="<?= h($page['seo_desc'] ?? '') ?>">
<link rel="stylesheet" href="/assets/style.css">
</head><body>

<header class="site-head">
  <a class="logo" href="/"><?= h(setting('site_name','SWIMMER')) ?></a>
  <nav><?php foreach ($menu as $m): ?><a href="<?= h($m['url']) ?>"><?= h($m['title']) ?></a><?php endforeach; ?></nav>
</header>

<main>
<?php foreach ($blocks as $b):
  $d = json_decode($b['data'] ?? '{}', true) ?: [];
  switch ($b['type']):

  case 'hero': ?>
    <section class="hero">
      <h1><?= h($d['title'] ?? '') ?></h1>
      <p class="sub"><?= h($d['subtitle'] ?? '') ?></p>
      <?php if (!empty($d['cta'])): ?><a class="btn" href="#products"><?= h($d['cta']) ?></a><?php endif; ?>
    </section>
  <?php break;

  case 'text': ?>
    <section class="wrap">
      <?php if (!empty($d['title'])): ?><h2><?= h($d['title']) ?></h2><?php endif; ?>
      <p><?= nl2br(h($d['content'] ?? '')) ?></p>
    </section>
  <?php break;

  case 'features': ?>
    <section class="wrap">
      <?php if (!empty($d['title'])): ?><h2><?= h($d['title']) ?></h2><?php endif; ?>
      <div class="grid3">
        <?php foreach (($d['items'] ?? []) as $it): ?>
          <div class="card"><h3><?= h($it['t'] ?? '') ?></h3><p><?= h($it['d'] ?? '') ?></p></div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php break;

  case 'products':
    $prods = db()->query("SELECT * FROM products WHERE status='published' ORDER BY sort")->fetchAll(); ?>
    <section class="wrap" id="products">
      <?php if (!empty($d['title'])): ?><h2><?= h($d['title']) ?></h2><?php endif; ?>
      <?php if (!$prods): ?>
        <p class="muted">Товары пока не добавлены.</p>
      <?php else: ?>
        <div class="grid3">
        <?php foreach ($prods as $p): ?>
          <div class="card">
            <h3><?= h($p['name']) ?></h3>
            <p class="price"><?= number_format($p['price'], 0, '', ' ') ?> ₽</p>
            <p><?= h($p['descr']) ?></p>
          </div>
        <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php break;

  case 'faq': ?>
    <section class="wrap">
      <?php if (!empty($d['title'])): ?><h2><?= h($d['title']) ?></h2><?php endif; ?>
      <?php foreach (($d['items'] ?? []) as $it): ?>
        <details><summary><?= h($it['q'] ?? '') ?></summary><p><?= h($it['a'] ?? '') ?></p></details>
      <?php endforeach; ?>
    </section>
  <?php break;

  default: ?>
    <!-- block <?= h($b['type']) ?> -->
  <?php endswitch;
endforeach;

if (!$blocks): ?>
  <section class="wrap"><h1><?= h($page['title']) ?></h1><p class="muted">Блоков пока нет.</p></section>
<?php endif; ?>
</main>

<footer class="site-foot">
  <nav><?php foreach ($fmenu as $m): ?><a href="<?= h($m['url']) ?>"><?= h($m['title']) ?></a><?php endforeach; ?></nav>
  <p>&copy; <?= date('Y') ?> <?= h(setting('site_name','SWIMMER')) ?></p>
</footer>
</body></html>
