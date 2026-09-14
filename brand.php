<?php
require __DIR__ . '/config.php';

$brand = setting('site_name', 'SWIMMER');
$menu = db()->query("SELECT * FROM menu WHERE place='header' AND visible=1 ORDER BY sort")->fetchAll();
$label = setting('brand_story_label', 'О БРЕНДЕ');
$title = setting('brand_story_title', 'Мы просто всегда любили воду');
$text = setting('brand_story_text', "SWIMMER вырос из простого желания проводить больше времени у воды — не думая о ветре, сырости и переменчивой погоде.\n\nМы делаем вещи спокойными по характеру и практичными по сути. В основе — защита от дождя и ветра, свободная посадка, тёплая мягкая подкладка и материалы, которые рассчитаны не на витрину, а на реальное использование.\n\nДля нас продукт — это не сезонная декорация, а надёжная вещь для поездок, берега, лодки, дачи и долгих прогулок. Конструкцию и материалы подбираем так, чтобы парку было удобно носить, хранить и брать с собой.\n\nПроизводство строится вокруг понятных решений: мембранная ткань, проклеенные швы, функциональные детали и контроль качества на каждом этапе.");
$linkText = setting('brand_story_link_text', 'Выбрать надежную вещь для отдыха на природе.');
$media = setting('brand_story_media_src', 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&q=88&w=2200');
$mediaLabel = setting('brand_story_media_label', 'Вода и река');
$isVideo = preg_match('~\.(mp4|webm)(?:\?|$)~i', $media);
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>О бренде — <?= h($brand) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/style.css">
<link rel="stylesheet" href="/assets/tz-visual.css">
<style>
.brand-page{padding:110px 0 0;background:#fff;text-align:center}.brand-page__copy{max-width:980px;margin:0 auto}.brand-page__label{font-size:10px;letter-spacing:.16em;color:#777f80;margin-bottom:22px}.brand-page h1{margin:0 auto 36px;font-size:clamp(42px,5vw,68px);line-height:.98;letter-spacing:-.045em;max-width:14ch}.brand-page__text{max-width:720px;margin:0 auto}.brand-page__text p{margin:0 0 18px;font-size:15px;line-height:1.72;color:#4e5556}.brand-page__link{display:inline-block;margin:10px auto 58px;font-size:14px;font-weight:600;color:#171b1c;text-decoration:underline;text-underline-offset:4px}.brand-page__media{width:100%;height:min(78vh,820px);overflow:hidden;background:#dfe4e4}.brand-page__media img,.brand-page__media video{width:100%;height:100%;object-fit:cover;object-position:center 68%;display:block}.brand-page header .menu a[aria-current="page"]{font-weight:600}.brand-page-footer-contacts{display:grid;gap:8px;margin-top:22px;font-size:12px;color:#aeb4b4}.brand-page-footer-contacts a{color:inherit}@media(max-width:700px){.brand-page{padding-top:78px}.brand-page h1{max-width:12ch}.brand-page__media{height:55vh;min-height:380px}.brand-page__media img,.brand-page__media video{object-position:center 72%}}
</style>
</head>
<body>
<header>
  <div class="wrap bar">
    <a href="/" class="logo" aria-label="<?= h($brand) ?> — на главную"><img src="/assets/logo.svg" alt="<?= h($brand) ?>"></a>
    <nav class="menu">
      <?php foreach ($menu as $m): ?>
        <?php if (($m['url'] ?? '') === '#brand'): ?>
          <a href="/brand.php" aria-current="page"><?= h($m['title']) ?></a>
        <?php elseif (($m['url'] ?? '') === '#contacts'): ?>
          <a href="#brand-contacts"><?= h($m['title']) ?></a>
        <?php else: ?>
          <a href="<?= h(str_starts_with($m['url'], '#') ? '/' . $m['url'] : $m['url']) ?>"><?= h($m['title']) ?></a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <div class="tools"><a class="cart-open" href="/#product">Каталог</a></div>
  </div>
</header>

<main class="brand-page">
  <div class="wrap brand-page__copy">
    <div class="brand-page__label"><?= h($label) ?></div>
    <h1><?= h($title) ?></h1>
    <div class="brand-page__text">
      <?php foreach (preg_split('/\n\s*\n/', $text) as $p): if (trim($p)==='') continue; ?>
        <p><?= h(trim($p)) ?></p>
      <?php endforeach; ?>
    </div>
    <a class="brand-page__link" href="/#product"><?= h($linkText) ?></a>
  </div>
  <div class="brand-page__media">
    <?php if ($isVideo): ?>
      <video src="<?= h($media) ?>" autoplay muted loop playsinline controls></video>
    <?php else: ?>
      <img src="<?= h($media) ?>" alt="<?= h($mediaLabel) ?>">
    <?php endif; ?>
  </div>
</main>

<footer id="brand-contacts">
  <div class="wrap">
    <div class="fgrid">
      <div><img class="fbrand" src="/assets/logo-white.svg" alt="<?= h($brand) ?>"><p class="fslogan">Увидимся у воды.</p></div>
      <div class="footer-legal"><a href="#offer">Публичная оферта</a><a href="#payment">Способы оплаты</a><a href="#returns">Гарантия и возврат</a></div>
      <div class="brand-page-footer-contacts">
        <?php if (setting('phone')): ?><a href="tel:<?= h(preg_replace('/[^+\d]/','',setting('phone'))) ?>"><?= h(setting('phone')) ?></a><?php endif; ?>
        <?php if (setting('email')): ?><a href="mailto:<?= h(setting('email')) ?>"><?= h(setting('email')) ?></a><?php endif; ?>
        <?php if (setting('tg')): ?><a href="<?= h(setting('tg')) ?>">Telegram</a><?php endif; ?>
      </div>
    </div>
  </div>
</footer>
</body>
</html>
