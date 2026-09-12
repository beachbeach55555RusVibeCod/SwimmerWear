<?php
/**
 * Рендер блоков витрины SWIMMER.
 * Каждая функция печатает секцию по данным блока из БД.
 */

/** Иконки для строки характеристик в hero. */
function spec_icon($key) {
  $icons = [
    'drop'   => '<path d="M12 3c3 4.2 5.5 7 5.5 10a5.5 5.5 0 1 1-11 0C6.5 10 9 7.2 12 3z"/><path d="M9.5 20.5h9"/>',
    'fabric' => '<rect x="3.5" y="3.5" width="17" height="17"/><path d="M3.5 9h17M3.5 15h17M9 3.5v17M15 3.5v17"/>',
    'dwr'    => '<path d="M4 15.5c2.2-2.6 5-3.9 8-3.9s5.8 1.3 8 3.9"/><circle cx="8.5" cy="7" r="1.6"/><circle cx="15" cy="5.6" r="1.6"/><path d="M4 20h16"/>',
    'fleece' => '<path d="M4 8.5c1.6-2 3.2-2 4.8 0s3.2 2 4.8 0 3.2-2 4.8 0"/><path d="M4 13c1.6-2 3.2-2 4.8 0s3.2 2 4.8 0 3.2-2 4.8 0"/><path d="M4 17.5c1.6-2 3.2-2 4.8 0s3.2 2 4.8 0 3.2-2 4.8 0"/>',
  ];
  return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($icons[$key] ?? $icons['drop']) . '</svg>';
}
function spec_icon_list() {
  return ['drop'=>'капля (водостойкость)','fabric'=>'плетение (ткань)','dwr'=>'пропитка DWR','fleece'=>'ворс (подкладка)'];
}

/** Переводит строку-источник в объект медиа для JS. */
function media_obj($src, $label) {
  $src = trim((string)$src);
  if ($src === '') return ['type'=>'placeholder','label'=>$label ?: 'медиа'];
  $ext = strtolower(pathinfo(parse_url($src, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
  if ($ext === 'mp4' || $ext === 'webm') return ['type'=>'video','src'=>$src];
  return ['type'=>'image','src'=>$src,'alt'=>$label];
}

/** <br> вместо переводов строки в заголовке. */
function nl2br_h($s) { return nl2br(h($s), false); }

/** Картинка или заштрихованный плейсхолдер. */
function pic($src, $label, $cls = '') {
  $src = trim((string)$src);
  if ($src === '') {
    echo '<div class="ph' . ($cls ? ' ' . h($cls) : '') . '">' . h($label ?: 'фото') . '</div>';
  } else {
    echo '<img' . ($cls ? ' class="' . h($cls) . '"' : '') . ' src="' . h($src) . '" alt="' . h($label) . '" loading="lazy">';
  }
}

function block_hero($d) { ?>
<section class="hero" id="hero" aria-label="<?= h($d['h1'] ?? 'Обложка') ?>">
  <div class="hero-slides" id="heroSlides"></div>
  <div class="hero-veil"></div>
  <div class="hero-dots" id="heroDots"></div>
  <div class="hero-arrows">
    <button id="hPrev" aria-label="Предыдущий слайд">&lsaquo;</button>
    <button id="hNext" aria-label="Следующий слайд">&rsaquo;</button>
  </div>
  <div class="wrap hero-in">
    <?php if (!empty($d['t1'])): ?><div class="hero-t1"><?= h($d['t1']) ?></div><?php endif; ?>
    <h1><?= nl2br_h($d['h1'] ?? '') ?></h1>
    <?php if (!empty($d['t2'])): ?><p class="hero-t2"><?= h($d['t2']) ?></p><?php endif; ?>
    <?php if (!empty($d['cta'])): ?><a href="#product" class="hero-cta"><?= h($d['cta']) ?></a><?php endif; ?>
    <?php $specs = array_filter($d['specs'] ?? [], function($s){ return trim($s['b'] ?? '') !== ''; }); ?>
    <?php if ($specs): ?>
    <div class="specs">
      <?php foreach ($specs as $s): ?>
        <div class="spec">
          <?= spec_icon($s['icon'] ?? 'drop') ?>
          <div><b><?= h($s['b']) ?></b><span><?= h($s['span'] ?? '') ?></span></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php }

function block_products($d) { ?>
<section class="products" id="product">
  <div class="wrap">
    <div class="prod-top">
      <div>
        <h2><?= nl2br_h($d['title'] ?? '') ?></h2>
        <?php if (!empty($d['lede'])): ?><p class="lede"><?= h($d['lede']) ?></p><?php endif; ?>
      </div>
      <div class="rail-btns">
        <button id="pPrev" aria-label="Прокрутить назад">&lsaquo;</button>
        <button id="pNext" aria-label="Прокрутить вперёд">&rsaquo;</button>
      </div>
    </div>
    <div class="rail" id="rail"></div>
  </div>
</section>
<?php }

function block_scenarios($d) { ?>
<section class="scen" id="scenarios" aria-label="Сценарии использования">
  <div id="scenSlides"></div>
  <div class="wrap sc-in">
    <h2><?= nl2br_h($d['title'] ?? '') ?></h2>
    <div class="sc-box" id="scBox"></div>
  </div>
  <div class="scen-dots" id="scenDots"></div>
</section>
<?php }

function block_tech($d) { ?>
<section id="tech">
  <div class="wrap">
    <div class="sec-head">
      <h2><?= nl2br_h($d['title'] ?? '') ?></h2>
      <?php if (!empty($d['lede'])): ?><p class="lede"><?= h($d['lede']) ?></p><?php endif; ?>
    </div>
    <div class="tech-grid">
      <?php require __DIR__ . '/membrane.svg.php'; ?>
      <div class="tech-list">
        <?php foreach (($d['items'] ?? []) as $it): if (trim($it['b'] ?? '') === '') continue; ?>
          <div><b><?= h($it['b']) ?></b><p><?= h($it['p'] ?? '') ?></p></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php }

function block_build($d) { ?>
<section class="build">
  <div class="wrap build-grid">
    <div class="build-left">
      <h2><?= nl2br_h($d['title'] ?? '') ?></h2>
      <?php foreach (preg_split('/\n+/', (string)($d['text'] ?? '')) as $p): if (trim($p) === '') continue; ?>
        <p><?= h(trim($p)) ?></p>
      <?php endforeach; ?>
      <div class="build-nav">
        <button id="dPrev" aria-label="Прокрутить назад">&lsaquo;</button>
        <button id="dNext" aria-label="Прокрутить вперёд">&rsaquo;</button>
      </div>
    </div>
    <div class="det-rail" id="detRail"></div>
  </div>
</section>
<?php }

function block_kit($d) { ?>
<section>
  <div class="wrap kit-grid">
    <div class="kit-shots">
      <?php foreach (array_slice($d['shots'] ?? [], 0, 3) as $i => $s): pic($s, 'фото ' . ($i + 1)); endforeach; ?>
    </div>
    <div>
      <h2><?= nl2br_h($d['title'] ?? '') ?></h2>
      <ul class="kit-list">
        <?php $n = 0; foreach (($d['items'] ?? []) as $it): if (trim($it) === '') continue; $n++; ?>
          <li><span class="num"><?= sprintf('%02d', $n) ?></span><?= h(trim($it)) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>
<?php }

function block_faq($d) { ?>
<section>
  <div class="wrap">
    <div class="sec-head"><h2><?= nl2br_h($d['title'] ?? '') ?></h2></div>
    <div class="faq">
      <?php foreach (($d['items'] ?? []) as $it): if (trim($it['q'] ?? '') === '') continue; ?>
        <details class="q">
          <summary><?= h($it['q']) ?></summary>
          <div class="a"><?= h($it['a'] ?? '') ?></div>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php }

function block_reviews($d) { ?>
<section>
  <div class="wrap">
    <div class="sec-head"><h2><?= nl2br_h($d['title'] ?? '') ?></h2></div>
    <div class="revs">
      <?php foreach (($d['items'] ?? []) as $t): if (trim($t) === '') continue; ?>
        <div class="rev"><div class="mark">&ldquo;</div><p><?= h(trim($t)) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php }

function block_text($d) { ?>
<section>
  <div class="wrap">
    <?php if (!empty($d['title'])): ?><div class="sec-head"><h2><?= nl2br_h($d['title']) ?></h2></div><?php endif; ?>
    <?php foreach (preg_split('/\n+/', (string)($d['content'] ?? '')) as $p): if (trim($p) === '') continue; ?>
      <p class="lede" style="margin-top:0;margin-bottom:14px"><?= h(trim($p)) ?></p>
    <?php endforeach; ?>
  </div>
</section>
<?php }

/** Диспетчер. */
function render_block($type, $data) {
  $fn = 'block_' . $type;
  if (function_exists($fn)) $fn($data);
}

/** Путь к файлу медиатеки: свой файл или внешняя ссылка. */
function media_url($file) {
  $file = (string)$file;
  return preg_match('~^https?://~i', $file) ? $file : UPLOAD_URL . '/' . ltrim($file, '/');
}

/** Нижний регистр с поддержкой кириллицы, если есть mbstring. */
if (!function_exists('lc')) {
  function lc($s) {
    return function_exists('mb_strtolower') ? mb_strtolower((string)$s, 'UTF-8') : strtolower((string)$s);
  }
}

/** Заглушка вместо фото — рисуется прямо в ссылке, без обращения к серверу. */
function placeholder_src($label) {
  $label = (string)$label;
  $label = h(function_exists('mb_substr') ? mb_substr($label, 0, 40, 'UTF-8') : substr($label, 0, 80));
  $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="1200" viewBox="0 0 900 1200">'
       . '<rect width="900" height="1200" fill="#EDEFEF"/>'
       . '<path d="M0 0L900 1200M900 0L0 1200" stroke="#D6DADA" stroke-width="2"/>'
       . '<rect x="120" y="540" width="660" height="120" fill="#EDEFEF"/>'
       . '<text x="450" y="615" font-family="Inter,sans-serif" font-size="34" fill="#7E8688" text-anchor="middle">'
       . $label . '</text></svg>';
  return 'data:image/svg+xml;charset=utf-8,' . rawurlencode($svg);
}
