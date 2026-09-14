<?php
require __DIR__ . '/config.php';
require __DIR__ . '/inc/blocks.php';

$slug = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if ($slug === '') $slug = '/';

try {
  $st = db()->prepare("SELECT * FROM pages WHERE slug=? AND status='published' LIMIT 1");
  $st->execute([$slug]);
  $page = $st->fetch();
} catch (Throwable $e) {
  http_response_code(500);
  exit('<h1>Нет связи с базой</h1><p>Запустите <a href="/install.php">install.php</a></p>');
}

if (!$page) {
  http_response_code(404);
  $page = ['id'=>0,'title'=>'Страница не найдена','seo_title'=>'404','seo_desc'=>''];
  $blocks = [];
} else {
  $st = db()->prepare("SELECT * FROM blocks WHERE page_id=? AND visible=1 ORDER BY sort");
  $st->execute([$page['id']]);
  $blocks = $st->fetchAll();
}

/* ---------- данные для витрины (уходят в JS) ---------- */
$SW = ['hero'=>[], 'colors'=>[], 'sizes'=>[], 'products'=>[], 'scenarios'=>[], 'details'=>[],
       'modelNote'=>setting('model_note',''), 'pdesc'=>['tags'=>[]]];

foreach (($tags = preg_split('/\s*,\s*/', setting('product_tags',''))) as $t)
  if (trim($t) !== '') $SW['pdesc']['tags'][] = trim($t);

foreach ($blocks as $b) {
  $d = json_decode($b['data'] ?? '{}', true) ?: [];
  if ($b['type'] === 'hero') {
    foreach (($d['slides'] ?? []) as $i => $s)
      $SW['hero'][] = media_obj($s['src'] ?? '', $s['label'] ?? ('слайд ' . ($i + 1)));
  }
  if ($b['type'] === 'scenarios') {
    foreach (($d['items'] ?? []) as $s) {
      if (trim($s['t'] ?? '') === '') continue;
      $SW['scenarios'][] = ['media'=>media_obj($s['src'] ?? '', $s['label'] ?? ''),
                            'title'=>$s['t'], 'text'=>$s['text'] ?? ''];
    }
  }
  if ($b['type'] === 'build') {
    foreach (($d['details'] ?? []) as $s) {
      if (trim($s['t'] ?? '') === '') continue;
      $SW['details'][] = ['src'=>$s['src'] ?? '', 'label'=>$s['label'] ?? '',
                          'title'=>$s['t'], 'text'=>$s['text'] ?? ''];
    }
  }
}

/* товары, цвета и размеры — из БД */
$prods = db()->query("SELECT * FROM products WHERE status='published' ORDER BY sort, id")->fetchAll();
$colorHex = [];
foreach (preg_split('/\n+/', setting('colors', "хаки|#575E43\nчёрный|#22262A\nсиний|#41546B")) as $line) {
  $parts = array_map('trim', explode('|', $line));
  if (count($parts) >= 2 && $parts[0] !== '') $colorHex[lc($parts[0])] = $parts[1];
}

$seenColor = []; $seenSize = [];
foreach ($prods as $p) {
  $st = db()->prepare("SELECT * FROM variants WHERE product_id=? ORDER BY id");
  $st->execute([$p['id']]);
  $vars = $st->fetchAll();

  $st = db()->prepare("SELECT m.file, pm.color FROM product_media pm
                       JOIN media m ON m.id = pm.media_id
                       WHERE pm.product_id=? ORDER BY pm.sort, pm.id");
  $st->execute([$p['id']]);
  $shotsAll = $st->fetchAll();

  $byColor = [];
  foreach ($vars as $v) {
    $key = lc($v['color']);
    $byColor[$key]['name'] = $v['color'];
    $byColor[$key]['sizes'][] = $v['size'];
    if (!isset($seenSize[$v['size']])) { $seenSize[$v['size']] = 1; $SW['sizes'][] = $v['size']; }
  }
  if (!$byColor) $byColor['—'] = ['name'=>'—','sizes'=>[]];

  foreach ($byColor as $key => $c) {
    if (!isset($seenColor[$key])) {
      $seenColor[$key] = 1;
      $SW['colors'][] = ['id'=>$key, 'name'=>$c['name'], 'hex'=>$colorHex[$key] ?? '#7E8688'];
    }
    /* app.js ждёт здесь простые ссылки, не объекты */
    $shots = [];
    foreach ($shotsAll as $s)
      if ($s['color'] === null || $s['color'] === '' || lc($s['color']) === $key)
        $shots[] = media_url($s['file']);
    if (!$shots) $shots[] = placeholder_src($p['name'] . ' · ' . $c['name']);

    $SW['products'][] = [
      'id'=>$p['id'] . '-' . $key, 'name'=>$p['name'], 'colorId'=>$key,
      'price'=>(int)$p['price'], 'sku'=>$p['sku'], 'shots'=>$shots,
      'descr'=>$p['descr'], 'compose'=>$p['compose'], 'care'=>$p['care'],
      'kit'=>$p['kit'], 'delivery'=>$p['delivery'],
    ];
  }
}

$menu  = db()->query("SELECT * FROM menu WHERE place='header' AND visible=1 ORDER BY sort")->fetchAll();
$fmenu = db()->query("SELECT * FROM menu WHERE place='footer' AND visible=1 ORDER BY sort")->fetchAll();
$brand = setting('site_name', 'SWIMMER');
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($page['seo_title'] ?: $page['title']) ?></title>
<meta name="description" content="<?= h($page['seo_desc'] ?? '') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>

<header>
  <div class="wrap bar">
    <a href="#hero" class="logo" aria-label="<?= h($brand) ?> — на главную"><img src="/assets/logo.svg" alt="<?= h($brand) ?>"></a>
    <nav class="menu">
      <?php foreach ($menu as $m): ?>
        <?php if (in_array($m['url'], ['#brand','#contacts'], true)): ?>
          <button data-open="<?= ltrim(h($m['url']), '#') ?>"><?= h($m['title']) ?></button>
        <?php else: ?>
          <a href="<?= h($m['url']) ?>"><?= h($m['title']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="tools">
      <button class="burger" data-open="contacts" aria-label="Меню">&#9776;</button>
      <button class="cart-open" data-open="cart">Корзина <span class="num" id="cartCount">0</span></button>
    </div>
  </div>
</header>

<?php
foreach ($blocks as $b) render_block($b['type'], json_decode($b['data'] ?? '{}', true) ?: []);
if (!$blocks) echo '<section><div class="wrap"><h1>' . h($page['title']) . '</h1><p class="lede">Блоков пока нет.</p></div></section>';
?>

<footer>
  <div class="wrap">
    <div class="fgrid">
      <div>
        <img class="fbrand" src="/assets/logo-white.svg" alt="<?= h($brand) ?>">
        <p class="fslogan"><?= h(setting('slogan', '')) ?></p>
      </div>
      <div>
        <h4>РАЗДЕЛЫ</h4>
        <?php foreach ($fmenu as $m): ?>
          <?php if (in_array($m['url'], ['#brand','#contacts'], true)): ?>
            <button data-open="<?= ltrim(h($m['url']), '#') ?>"><?= h($m['title']) ?></button>
          <?php else: ?>
            <a href="<?= h($m['url']) ?>"><?= h($m['title']) ?></a>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <div>
        <h4>КОНТАКТЫ</h4>
        <?php if (setting('phone')): ?><a href="tel:<?= h(preg_replace('/[^+\d]/', '', setting('phone'))) ?>"><?= h(setting('phone')) ?></a><?php endif; ?>
        <?php if (setting('email')): ?><a href="mailto:<?= h(setting('email')) ?>"><?= h(setting('email')) ?></a><?php endif; ?>
        <button data-open="contacts">Все контакты</button>
      </div>
    </div>
    <div class="fbot">
      <span>&copy; <?= date('Y') ?> <?= h($brand) ?></span>
      <span><?= h(setting('tagline', '')) ?></span>
    </div>
  </div>
</footer>

<!-- модалки -->
<div class="ov" id="ov"></div>

<div class="sheet modal" id="mProduct" role="dialog" aria-modal="true" aria-label="Карточка товара">
  <div class="mhead"><h3 id="pTitleTop">Товар</h3><button class="x" data-close aria-label="Закрыть">&times;</button></div>
  <div class="mbody" id="pBody"></div>
</div>

<div class="sheet modal narrow" id="mSizes" role="dialog" aria-modal="true" aria-label="Таблица размеров">
  <div class="mhead"><h3>Таблица размеров</h3><button class="x" data-close aria-label="Закрыть">&times;</button></div>
  <div class="mbody">
    <?php
    $rows = array_values(array_filter(preg_split('/\n+/', setting('size_table', '')), 'trim'));
    if ($rows): ?>
    <table>
      <?php foreach ($rows as $i => $row): $cells = preg_split('/\s*\|\s*/', trim($row)); ?>
        <?php if ($i === 0): ?>
          <thead><tr><?php foreach ($cells as $c): ?><th><?= h($c) ?></th><?php endforeach; ?></tr></thead><tbody>
        <?php else: ?>
          <tr><?php foreach ($cells as $c): ?><td><?= h($c) ?></td><?php endforeach; ?></tr>
        <?php endif; ?>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
    <?php if (setting('size_note')): ?><p class="lede" style="font-size:14px"><?= h(setting('size_note')) ?></p><?php endif; ?>
  </div>
</div>

<div class="sheet modal" id="mBrand" role="dialog" aria-modal="true" aria-label="О бренде">
  <div class="mhead"><h3>О бренде</h3><button class="x" data-close aria-label="Закрыть">&times;</button></div>
  <div class="mbody brand-body">
    <?php if (setting('brand_title')): ?><h2 style="margin-bottom:20px"><?= h(setting('brand_title')) ?></h2><?php endif; ?>
    <?php foreach (preg_split('/\n+/', setting('brand_text', '')) as $p): if (trim($p) === '') continue; ?>
      <p><?= h(trim($p)) ?></p>
    <?php endforeach; ?>
    <p><a href="#product" class="linkish" data-close>Перейти в каталог</a></p>
    <?php if (setting('brand_image')): ?><img class="big" src="<?= h(setting('brand_image')) ?>" alt="<?= h($brand) ?>" loading="lazy"><?php endif; ?>
  </div>
</div>

<div class="sheet modal narrow" id="mContacts" role="dialog" aria-modal="true" aria-label="Контакты">
  <div class="mhead"><h3>Контакты</h3><button class="x" data-close aria-label="Закрыть">&times;</button></div>
  <div class="mbody">
    <div class="contacts-list">
      <?php if (setting('address')): ?><div><b>АДРЕС</b><?= h(setting('address')) ?></div><?php endif; ?>
      <?php if (setting('phone')): ?><div><b>ТЕЛЕФОН</b><a href="tel:<?= h(preg_replace('/[^+\d]/', '', setting('phone'))) ?>"><?= h(setting('phone')) ?></a></div><?php endif; ?>
      <?php if (setting('email')): ?><div><b>E-MAIL</b><a href="mailto:<?= h(setting('email')) ?>"><?= h(setting('email')) ?></a></div><?php endif; ?>
      <?php if (setting('tg')): ?><div><b>TELEGRAM</b><a href="<?= h(setting('tg')) ?>"><?= h(setting('tg')) ?></a></div><?php endif; ?>
      <?php if ($fmenu): ?>
      <div><b>РАЗДЕЛЫ</b>
        <?php foreach ($fmenu as $m): ?><a href="<?= h($m['url']) ?>" data-close><?= h($m['title']) ?></a><?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="sheet drawer" id="mCart" role="dialog" aria-modal="true" aria-label="Корзина">
  <div class="mhead"><h3>Корзина</h3><button class="x" data-close aria-label="Закрыть">&times;</button></div>
  <div class="citems" id="cartItems"></div>
  <div class="cfoot">
    <div class="total"><span>Итого</span><span class="num" id="cartTotal">0 &#8381;</span></div>
    <button class="add" id="checkout">ОФОРМИТЬ ЗАКАЗ</button>
    <button class="cont" data-close>Продолжить покупки</button>
  </div>
</div>

<script>window.SW = <?= json_encode($SW, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="/assets/app.js"></script>
<script src="/assets/tech-fallback.js"></script>
<script src="/assets/build-fallback.js"></script>
<script src="/assets/reviews-fallback.js?v=1"></script>
</body>
</html>
