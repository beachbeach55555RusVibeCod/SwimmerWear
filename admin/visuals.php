<?php
require 'inc.php';
require dirname(__DIR__) . '/inc/blocks.php';
need_auth();

function v_lines($value) {
  return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)$value)), 'strlen'));
}
function v_rows($key, $fields) {
  $src = $_POST[$key] ?? []; $out = [];
  $indexes = [];
  foreach ($fields as $f) foreach (array_keys($src[$f] ?? []) as $i) $indexes[$i] = true;
  ksort($indexes);
  foreach (array_keys($indexes) as $i) {
    $r = [];
    foreach ($fields as $f) $r[$f] = trim($src[$f][$i] ?? '');
    if (implode('', $r) !== '') $out[] = $r;
  }
  return $out;
}
function v_setting($k, $v) {
  db()->prepare("INSERT INTO settings (`k`,`v`) VALUES (?,?) ON DUPLICATE KEY UPDATE `v`=VALUES(`v`)")->execute([$k,$v]);
}
function v_media_field($key, $i, $src, $label, $mediaList, $title = 'Медиа') {
  $src = (string)$src; $label = (string)$label;
  $isVideo = preg_match('~\.(mp4|webm)(?:\?|$)~i', $src);
  ?>
  <div class="visual-media">
    <div class="visual-preview">
      <?php if ($src): ?>
        <?php if ($isVideo): ?><video src="<?= h($src) ?>" muted controls preload="metadata"></video>
        <?php else: ?><img src="<?= h($src) ?>" alt="" loading="lazy"><?php endif; ?>
      <?php else: ?><div class="visual-empty">нет файла</div><?php endif; ?>
    </div>
    <div class="visual-fields">
      <label><?= h($title) ?> — фото или видео</label>
      <input name="<?= h($key) ?>[src][<?= $i ?>]" value="<?= h($src) ?>" list="visual-media-list" placeholder="/uploads/... или https://...">
      <label>Надпись / подпись</label>
      <input name="<?= h($key) ?>[label][<?= $i ?>]" value="<?= h($label) ?>" placeholder="Текст для этого кадра">
    </div>
  </div>
<?php }

$msg = '';
$pageId = (int)($_GET['page'] ?? $_POST['page_id'] ?? 0);
$pages = db()->query("SELECT * FROM pages ORDER BY sort,id")->fetchAll();
if (!$pageId && $pages) {
  foreach ($pages as $p) if (($p['slug'] ?? '') === '/') {$pageId=(int)$p['id'];break;}
  if (!$pageId) $pageId=(int)$pages[0]['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['a'] ?? '';
  if ($action === 'save_block') {
    $id = (int)($_POST['id'] ?? 0);
    $st = db()->prepare("SELECT * FROM blocks WHERE id=? AND page_id=? LIMIT 1"); $st->execute([$id,$pageId]);
    if ($b = $st->fetch()) {
      $d = json_decode($b['data'] ?? '{}', true) ?: [];
      if ($b['type'] === 'hero') {
        $d['t1']=trim($_POST['t1'] ?? ''); $d['h1']=trim($_POST['h1'] ?? ''); $d['t2']=trim($_POST['t2'] ?? ''); $d['cta']=trim($_POST['cta'] ?? '');
        $d['slides']=v_rows('slide',['src','label']);
      } elseif ($b['type'] === 'scenarios') {
        $d['title']=trim($_POST['title'] ?? ''); $d['items']=v_rows('item',['src','label','t','text']);
      } elseif ($b['type'] === 'build') {
        $d['title']=trim($_POST['title'] ?? ''); $d['text']=trim($_POST['text'] ?? ''); $d['details']=v_rows('item',['src','label','t','text']);
      } elseif ($b['type'] === 'kit') {
        $d['title']=trim($_POST['title'] ?? '');
        $rows=v_rows('shot',['src','label']); $d['shots']=[]; $d['shot_labels']=[];
        foreach ($rows as $r) {$d['shots'][]=$r['src'];$d['shot_labels'][]=$r['label'];}
        $d['items']=v_lines($_POST['items'] ?? '');
      }
      db()->prepare("UPDATE blocks SET data=? WHERE id=?")->execute([json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$id]);
      $msg = 'Изменения блока сохранены';
    }
  }
  if ($action === 'save_brand') {
    v_setting('brand_story_label', trim($_POST['label'] ?? ''));
    v_setting('brand_story_title', trim($_POST['title'] ?? ''));
    v_setting('brand_story_text', trim($_POST['text'] ?? ''));
    v_setting('brand_story_link_text', trim($_POST['link_text'] ?? ''));
    v_setting('brand_story_media_src', trim($_POST['media_src'] ?? ''));
    v_setting('brand_story_media_label', trim($_POST['media_label'] ?? ''));
    $msg = 'Блок «О бренде» сохранён';
  }
}

$st = db()->prepare("SELECT * FROM blocks WHERE page_id=? ORDER BY sort,id"); $st->execute([$pageId]); $blocks=$st->fetchAll();
$mediaList = db()->query("SELECT file,alt FROM media ORDER BY id DESC")->fetchAll();
$byType=[]; foreach($blocks as $b) if(in_array($b['type'],['hero','scenarios','build','kit'],true)) $byType[$b['type']]=$b;

$brand = [
  'label'=>setting('brand_story_label','О БРЕНДЕ'),
  'title'=>setting('brand_story_title','Мы просто всегда любили воду'),
  'text'=>setting('brand_story_text',"SWIMMER вырос из простого желания проводить больше времени у воды — не думая о ветре, сырости и переменчивой погоде.\n\nМы делаем вещи спокойными по характеру и практичными по сути. В основе — защита от дождя и ветра, свободная посадка, тёплая мягкая подкладка и материалы, которые рассчитаны не на витрину, а на реальное использование.\n\nДля нас продукт — это не сезонная декорация, а надёжная вещь для поездок, берега, лодки, дачи и долгих прогулок. Конструкцию и материалы подбираем так, чтобы парку было удобно носить, хранить и брать с собой.\n\nПроизводство строится вокруг понятных решений: мембранная ткань, проклеенные швы, функциональные детали и контроль качества на каждом этапе."),
  'link_text'=>setting('brand_story_link_text','Выбрать надежную вещь для отдыха на природе.'),
  'media_src'=>setting('brand_story_media_src','https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&q=88&w=2200'),
  'media_label'=>setting('brand_story_media_label','Вода и река'),
];

head('Фото, видео и надписи'); ?>
<style>
.visual-media{display:grid;grid-template-columns:180px minmax(0,1fr);gap:16px;padding:14px 0;border-top:1px solid var(--line)}
.visual-preview{height:130px;background:#eef0f0;overflow:hidden;display:grid;place-items:center}.visual-preview img,.visual-preview video{width:100%;height:100%;object-fit:cover}.visual-empty{font-size:12px;color:#8a9293}.visual-fields label{margin-top:0}.visual-fields input{margin-bottom:8px}.visual-section-title{display:flex;align-items:center;gap:10px;margin-bottom:12px}.visual-section-title h2{margin:0}.visual-help{font-size:12px;color:#7e8688;line-height:1.5}.visual-grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px}@media(max-width:760px){.visual-media{grid-template-columns:1fr}.visual-preview{height:200px}.visual-grid2{grid-template-columns:1fr}}
</style>
<h1>Фото, видео и надписи</h1>
<p class="visual-help">Здесь собраны все визуальные блоки главной страницы. Можно вставить файл из медиатеки, внешнюю ссылку, фото или mp4/webm-видео. После сохранения изменения появляются на витрине после обновления страницы.</p>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>
<datalist id="visual-media-list"><?php foreach($mediaList as $m): ?><option value="<?= h(media_url($m['file'])) ?>"><?= h($m['alt']) ?></option><?php endforeach; ?></datalist>

<div class="panel">
<form method="get" class="row"><div style="flex:1"><label>Страница</label><select name="page" onchange="this.form.submit()"><?php foreach($pages as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $pageId==(int)$p['id']?'selected':'' ?>><?= h($p['title']) ?> (<?= h($p['slug']) ?>)</option><?php endforeach; ?></select></div></form>
</div>

<?php if(isset($byType['hero'])): $b=$byType['hero'];$d=json_decode($b['data']??'{}',true)?:[]; ?>
<div class="panel"><form method="post"><input type="hidden" name="a" value="save_block"><input type="hidden" name="page_id" value="<?= $pageId ?>"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
<div class="visual-section-title"><h2>Обложка / главный слайдер</h2><span class="tag">Hero</span></div>
<div class="visual-grid2"><div><label>Надзаголовок</label><input name="t1" value="<?= h($d['t1']??'') ?>"></div><div><label>Текст кнопки</label><input name="cta" value="<?= h($d['cta']??'') ?>"></div></div>
<label>Главный заголовок</label><textarea name="h1" style="min-height:64px"><?= h($d['h1']??'') ?></textarea><label>Подзаголовок</label><input name="t2" value="<?= h($d['t2']??'') ?>">
<?php $slides=$d['slides']??[]; for($i=0;$i<6;$i++):$r=$slides[$i]??[];v_media_field('slide',$i,$r['src']??'',$r['label']??'',$mediaList,'Слайд '.($i+1));endfor; ?>
<button class="btn">Сохранить обложку</button></form></div>
<?php endif; ?>

<?php if(isset($byType['scenarios'])): $b=$byType['scenarios'];$d=json_decode($b['data']??'{}',true)?:[]; ?>
<div class="panel"><form method="post"><input type="hidden" name="a" value="save_block"><input type="hidden" name="page_id" value="<?= $pageId ?>"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
<div class="visual-section-title"><h2>Вода. Природа. Свобода.</h2><span class="tag">Слайдер</span></div><label>Заголовок блока</label><input name="title" value="<?= h($d['title']??'') ?>">
<?php $items=$d['items']??[]; for($i=0;$i<6;$i++):$r=$items[$i]??[];v_media_field('item',$i,$r['src']??'',$r['label']??'',$mediaList,'Кадр '.($i+1)); ?>
<div class="visual-grid2"><div><label>Заголовок на фото</label><input name="item[t][<?= $i ?>]" value="<?= h($r['t']??'') ?>"></div><div><label>Текст на фото</label><input name="item[text][<?= $i ?>]" value="<?= h($r['text']??'') ?>"></div></div><?php endfor; ?>
<button class="btn">Сохранить слайдер</button></form></div>
<?php endif; ?>

<?php if(isset($byType['build'])): $b=$byType['build'];$d=json_decode($b['data']??'{}',true)?:[]; ?>
<div class="panel"><form method="post"><input type="hidden" name="a" value="save_block"><input type="hidden" name="page_id" value="<?= $pageId ?>"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
<div class="visual-section-title"><h2>Продуманная конструкция</h2><span class="tag">Слайдер деталей</span></div><label>Заголовок блока</label><input name="title" value="<?= h($d['title']??'') ?>"><label>Текст слева</label><textarea name="text"><?= h($d['text']??'') ?></textarea>
<?php $items=$d['details']??[]; for($i=0;$i<6;$i++):$r=$items[$i]??[];v_media_field('item',$i,$r['src']??'',$r['label']??'',$mediaList,'Деталь '.($i+1)); ?>
<div class="visual-grid2"><div><label>Заголовок на фото</label><input name="item[t][<?= $i ?>]" value="<?= h($r['t']??'') ?>"></div><div><label>Описание на фото</label><input name="item[text][<?= $i ?>]" value="<?= h($r['text']??'') ?>"></div></div><?php endfor; ?>
<button class="btn">Сохранить конструкцию</button></form></div>
<?php endif; ?>

<?php if(isset($byType['kit'])): $b=$byType['kit'];$d=json_decode($b['data']??'{}',true)?:[]; ?>
<div class="panel"><form method="post"><input type="hidden" name="a" value="save_block"><input type="hidden" name="page_id" value="<?= $pageId ?>"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
<div class="visual-section-title"><h2>В комплекте</h2><span class="tag">Галерея</span></div><label>Заголовок блока</label><input name="title" value="<?= h($d['title']??'') ?>">
<?php $shots=$d['shots']??[];$labels=$d['shot_labels']??[];for($i=0;$i<3;$i++):v_media_field('shot',$i,$shots[$i]??'',$labels[$i]??'',$mediaList,'Фото комплекта '.($i+1));endfor; ?>
<label>Список комплекта — каждый пункт с новой строки</label><textarea name="items"><?= h(implode("\n",$d['items']??[])) ?></textarea><button class="btn">Сохранить комплект</button></form></div>
<?php endif; ?>

<div class="panel"><form method="post"><input type="hidden" name="a" value="save_brand"><input type="hidden" name="page_id" value="<?= $pageId ?>">
<div class="visual-section-title"><h2>О бренде</h2><span class="tag">Большое фото / видео</span></div>
<div class="visual-grid2"><div><label>Метка</label><input name="label" value="<?= h($brand['label']) ?>"></div><div><label>Заголовок</label><input name="title" value="<?= h($brand['title']) ?>"></div></div>
<label>Текст — абзацы разделяй пустой строкой</label><textarea name="text" style="min-height:180px"><?= h($brand['text']) ?></textarea><label>Текст ссылки</label><input name="link_text" value="<?= h($brand['link_text']) ?>">
<?php v_media_field('brand',0,$brand['media_src'],$brand['media_label'],$mediaList,'Большое фото / видео'); ?>
<input type="hidden" name="media_src" id="brandMediaSrc"><input type="hidden" name="media_label" id="brandMediaLabel">
<button class="btn" onclick="document.getElementById('brandMediaSrc').value=this.form.querySelector('[name=\'brand[src][0]\']').value;document.getElementById('brandMediaLabel').value=this.form.querySelector('[name=\'brand[label][0]\']').value">Сохранить «О бренде»</button></form></div>

<div class="panel"><div class="visual-section-title"><h2>Карточки товара</h2><span class="tag">Фото по цветам</span></div><p class="visual-help">Фотографии товара, цвета и порядок кадров редактируются в разделе «Товары».</p><a class="btn grey" href="products.php">Открыть товары</a></div>
<?php foot();
