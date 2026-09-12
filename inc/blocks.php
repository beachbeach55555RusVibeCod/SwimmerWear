<?php
function nl2br_h($s) { return nl2br(h($s)); }
function pic($src, $alt='') {
  $src = media_url($src);
  echo '<img src="' . h($src) . '" alt="' . h($alt) . '" loading="lazy">';
}

function block_hero($d) { ?>
<section class="hero" id="hero">
  <div class="hero-slides" id="heroSlides"></div>
  <div class="hero-veil"></div>
  <div class="hero-in wrap">
    <?php if (!empty($d['t1'])): ?><div class="hero-t1"><?= h($d['t1']) ?></div><?php endif; ?>
    <h1><?= nl2br_h($d['h1'] ?? '') ?></h1>
    <?php if (!empty($d['t2'])): ?><p class="hero-t2"><?= h($d['t2']) ?></p><?php endif; ?>
    <?php if (!empty($d['cta'])): ?><a class="hero-cta" href="#product"><?= h($d['cta']) ?></a><?php endif; ?>
    <?php if (!empty($d['specs'])): ?><div class="specs">
      <?php foreach ($d['specs'] as $s): ?><div class="spec"><span aria-hidden="true">＋</span><div><b><?= h($s['k'] ?? '') ?></b><span><?= h($s['v'] ?? '') ?></span></div></div><?php endforeach; ?>
    </div><?php endif; ?>
  </div>
  <div class="hero-arrows"><button id="hPrev" aria-label="Предыдущий слайд">&lsaquo;</button><button id="hNext" aria-label="Следующий слайд">&rsaquo;</button></div>
  <div class="hero-dots" id="heroDots"></div>
</section>
<?php }

function block_products($d) { ?>
<section class="products" id="product">
  <div class="wrap">
    <div class="prod-top"><div class="sec-head"><h2><?= nl2br_h($d['title'] ?? '') ?></h2></div><div class="rail-btns"><button id="pPrev" aria-label="Назад">&lsaquo;</button><button id="pNext" aria-label="Вперёд">&rsaquo;</button></div></div>
    <div class="rail" id="rail"></div>
  </div>
</section>
<?php }

function block_scenarios($d) { ?>
<section class="scen"><div class="scen-slides" id="scenSlides"></div><div class="sc-in wrap"><h2><?= nl2br_h($d['title'] ?? '') ?></h2><div class="sc-box" id="scBox"></div></div><div class="scen-dots" id="scenDots"></div></section>
<?php }

function block_tech($d) { ?>
<section><div class="wrap tech-grid"><div><div class="sec-head"><h2><?= nl2br_h($d['title'] ?? '') ?></h2></div><div class="tech-list"><?php foreach (($d['items'] ?? []) as $it): ?><div><b><?= h($it['k'] ?? '') ?></b><p><?= h($it['v'] ?? '') ?></p></div><?php endforeach; ?></div></div><div class="membrane"><?php if (file_exists(__DIR__.'/membrane.svg.php')) include __DIR__.'/membrane.svg.php'; ?></div></div></section>
<?php }

function block_build($d) { ?>
<section class="build"><div class="wrap build-grid"><div class="build-left"><div class="sec-head"><h2><?= nl2br_h($d['title'] ?? '') ?></h2></div><p><?= h($d['text'] ?? '') ?></p><div class="build-nav"><button id="dPrev" aria-label="Назад">&lsaquo;</button><button id="dNext" aria-label="Вперёд">&rsaquo;</button></div></div><div class="det-rail" id="detRail"></div></div></section>
<?php }

function block_kit($d) { ?>
<section><div class="wrap kit-grid"><div class="kit-shots"><?php foreach (array_slice($d['shots'] ?? [], 0, 3) as $i => $s): pic($s, 'фото ' . ($i + 1)); endforeach; ?></div><div><h2><?= nl2br_h($d['title'] ?? '') ?></h2><ul class="kit-list"><?php $n=0; foreach (($d['items'] ?? []) as $it): if (trim($it)==='') continue; $n++; ?><li><span class="num"><?= sprintf('%02d',$n) ?></span><?= h(trim($it)) ?></li><?php endforeach; ?></ul></div></div></section>
<?php }

function block_faq($d) { ?>
<section><div class="wrap"><div class="sec-head"><h2><?= nl2br_h($d['title'] ?? '') ?></h2></div><div class="faq"><?php foreach (($d['items'] ?? []) as $it): if (trim($it['q'] ?? '')==='') continue; ?><details class="q"><summary><?= h($it['q']) ?></summary><div class="a"><?= h($it['a'] ?? '') ?></div></details><?php endforeach; ?></div></div></section>
<?php }

function block_reviews($d) { ?>
<section><div class="wrap"><div class="sec-head"><h2><?= nl2br_h($d['title'] ?? '') ?></h2></div><div class="revs"><?php foreach (($d['items'] ?? []) as $t): if (trim($t)==='') continue; ?><div class="rev"><p><?= h(trim($t)) ?></p></div><?php endforeach; ?></div></div></section>
<?php }

function block_text($d) { ?>
<section><div class="wrap"><?php if (!empty($d['title'])): ?><div class="sec-head"><h2><?= nl2br_h($d['title']) ?></h2></div><?php endif; ?><?php foreach (preg_split('/\n+/', (string)($d['content'] ?? '')) as $p): if (trim($p)==='') continue; ?><p class="lede" style="margin-top:0;margin-bottom:14px"><?= h(trim($p)) ?></p><?php endforeach; ?></div></section>
<?php }

function render_block($type, $data) { $fn='block_'.$type; if (function_exists($fn)) $fn($data); }
function media_url($file) { $file=(string)$file; return preg_match('~^https?://~i',$file) ? $file : UPLOAD_URL.'/'.ltrim($file,'/'); }
if (!function_exists('lc')) { function lc($s) { return function_exists('mb_strtolower') ? mb_strtolower((string)$s,'UTF-8') : strtolower((string)$s); } }
function placeholder_src($label) { $label=(string)$label; $label=h(function_exists('mb_substr')?mb_substr($label,0,40,'UTF-8'):substr($label,0,80)); $svg='<svg xmlns="http://www.w3.org/2000/svg" width="900" height="1200" viewBox="0 0 900 1200"><rect width="900" height="1200" fill="#EDEFEF"/><path d="M0 0L900 1200M900 0L0 1200" stroke="#D6DADA" stroke-width="2"/><rect x="120" y="540" width="660" height="120" fill="#EDEFEF"/><text x="450" y="615" font-family="Inter,sans-serif" font-size="34" fill="#7E8688" text-anchor="middle">'.$label.'</text></svg>'; return 'data:image/svg+xml;charset=utf-8,'.rawurlencode($svg); }
