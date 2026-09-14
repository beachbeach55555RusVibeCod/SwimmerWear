<?php
require 'inc.php';
require dirname(__DIR__) . '/inc/blocks.php';
need_auth();

function v_lines($value) {
  return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)$value)), 'strlen'));
}
function v_rows($key, $fields) {
  $src = $_POST[$key] ?? []; $out = []; $indexes = [];
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
function v_upload_error_text($code) {
  $map = [
    UPLOAD_ERR_INI_SIZE=>'Файл больше лимита сервера upload_max_filesize.',
    UPLOAD_ERR_FORM_SIZE=>'Файл больше разрешённого размера формы.',
    UPLOAD_ERR_PARTIAL=>'Файл загрузился не полностью.',
    UPLOAD_ERR_NO_TMP_DIR=>'На сервере нет временной папки для загрузки.',
    UPLOAD_ERR_CANT_WRITE=>'Сервер не смог записать файл на диск.',
    UPLOAD_ERR_EXTENSION=>'Загрузка остановлена расширением PHP.',
  ];
  return $map[$code] ?? 'Неизвестная ошибка загрузки (код '.$code.').';
}
function v_apply_uploads() {
  $messages = [];
  if (empty($_FILES['upload']['name']) || !is_array($_FILES['upload']['name'])) return $messages;
  if (!is_dir(UPLOAD_DIR) && !@mkdir(UPLOAD_DIR, 0755, true)) return ['Не удалось создать папку uploads.'];
  if (!is_writable(UPLOAD_DIR)) return ['Папка uploads недоступна для записи.'];
  $allowed = ['jpg','jpeg','png','webp','gif','svg','mp4','webm'];
  foreach ($_FILES['upload']['name'] as $key => $rows) {
    if (!is_array($rows)) continue;
    foreach ($rows as $i => $orig) {
      if (!$orig) continue;
      $error = (int)($_FILES['upload']['error'][$key][$i] ?? UPLOAD_ERR_NO_FILE);
      if ($error === UPLOAD_ERR_NO_FILE) continue;
      if ($error !== UPLOAD_ERR_OK) { $messages[] = h($orig).': '.v_upload_error_text($error); continue; }
      $tmp = $_FILES['upload']['tmp_name'][$key][$i] ?? '';
      if (!$tmp || !is_uploaded_file($tmp)) { $messages[] = h($orig).': сервер не получил временный файл.'; continue; }
      $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed, true)) { $messages[] = h($orig).': формат не поддерживается.'; continue; }
      $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
      if (!move_uploaded_file($tmp, UPLOAD_DIR . '/' . $name)) { $messages[] = h($orig).': не удалось сохранить файл.'; continue; }
      @chmod(UPLOAD_DIR . '/' . $name, 0644);
      $mime = $_FILES['upload']['type'][$key][$i] ?? '';
      $size = (int)($_FILES['upload']['size'][$key][$i] ?? 0);
      db()->prepare("INSERT INTO media (file,alt,mime,filesize) VALUES (?,?,?,?)")
        ->execute([$name, pathinfo($orig, PATHINFO_FILENAME), $mime, $size]);
      $_POST[$key]['src'][$i] = media_url($name);
      $messages[] = h($orig).': загружен.';
    }
  }
  return $messages;
}
function v_media_field($key, $i, $src, $label, $title = 'Медиа') {
  $src = (string)$src; $label = (string)$label;
  $isVideo = preg_match('~\.(mp4|webm)(?:\?|$)~i', $src);
  ?>
  <div class="visual-media" data-original-src="<?= h($src) ?>">
    <div class="visual-preview">
      <?php if ($src): ?>
        <?php if ($isVideo): ?><video src="<?= h($src) ?>" muted controls preload="metadata"></video>
        <?php else: ?><img src="<?= h($src) ?>" alt="" loading="lazy"><?php endif; ?>
      <?php else: ?><div class="visual-empty">нет файла</div><?php endif; ?>
    </div>
    <div class="visual-fields">
      <label><?= h($title) ?></label>
      <div class="visual-upload-row">
        <input class="visual-file" type="file" name="upload[<?= h($key) ?>][<?= $i ?>]" accept="image/*,video/mp4,video/webm">
        <button class="btn grey sm visual-cancel" type="button">Отмена</button>
      </div>
      <div class="visual-file-hint">Выбери фото или видео с компьютера. Затем нажми кнопку сохранения блока.</div>
      <label>Или ссылка / файл из медиатеки</label>
      <input class="visual-src" name="<?= h($key) ?>[src][<?= $i ?>]" value="<?= h($src) ?>" list="visual-media-list" placeholder="/uploads/... или https://...">
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
  $uploadMessages = v_apply_uploads();
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
        $d['title']=trim($_POST['title'] ?? ''); $rows=v_rows('shot',['src','label']); $d['shots']=[]; $d['shot_labels']=[];
        foreach ($rows as $r) {$d['shots'][]=$r['src'];$d['shot_labels'][]=$r['label'];}
        $d['items']=v_lines($_POST['items'] ?? '');
      }
      db()->prepare("UPDATE blocks SET data=? WHERE id=?")->execute([json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$id]);
      $msg = 'Изменения блока сохранены';
    }
  }
  if ($action === 'save_brand') {
    $row = v_rows('brand',['src','label']); $media = $row[0] ?? ['src'=>'','label'=>''];
    v_setting('brand_story_label', trim($_POST['label'] ?? ''));
    v_setting('brand_story_title', trim($_POST['title'] ?? ''));
    v_setting('brand_story_text', trim($_POST['text'] ?? ''));
    v_setting('brand_story_link_text', trim($_POST['link_text'] ?? ''));
    v_setting('brand_story_media_src', $media['src']);
    v_setting('brand_story_media_label', $media['label']);
    $msg = 'Блок «О бренде» сохранён';
  }
  if ($uploadMessages) $msg .= ($msg ? ' ' : '') . implode(' ', $uploadMessages);
}

$st = db()->prepare("SELECT * FROM blocks WHERE page_id=? ORDER BY sort,id"); $st->execute([$pageId]); $blocks=$st->fetchAll();
$mediaList = db()->query("SELECT file,alt FROM media ORDER BY id DESC")->fetchAll();
$byType=[]; foreach($blocks as $b) if(in_array($b['type'],['hero','scenarios','build','kit'],true)) $byType[$b['type']]=$b;
$brand = [
  'label'=>setting('brand_story_label','О БРЕНДЕ'),
  'title'=>setting('brand_story_title','Мы просто всегда любили воду'),
  'text'=>setting('brand_story_text',"SWIMMER вырос из простого желания проводить больше времени у воды — не думая о ветре, сырости и переменчивой погоде.\n\nМы делаем вещи спокойными по характеру и практичными по сути.\n\nДля нас продукт — это надёжная вещь для поездок, берега, лодки, дачи и долгих прогулок.\n\nПроизводство строится вокруг понятных решений: мембранная ткань, проклеенные швы, функциональные детали и контроль качества на каждом этапе."),
  'link_text'=>setting('brand_story_link_text','Выбрать надежную вещь для отдыха на природе.'),
  'media_src'=>setting('brand_story_media_src','https://images.unsplash.com/photo-1500375592092-40eb2168fd21?auto=format&fit=crop&q=88&w=2200'),
  'media_label'=>setting('brand_story_media_label','Вода и река'),
];

head('Фото, видео и надписи'); ?>
<style>
.visual-media{display:grid;grid-template-columns:180px minmax(0,1fr);gap:16px;padding:16px 0;border-top:1px solid var(--line)}
.visual-preview{height:130px;background:#eef0f0;overflow:hidden;display:grid;place-items:center}.visual-preview img,.visual-preview video{width:100%;height:100%;object-fit:cover}.visual-empty{font-size:12px;color:#8a9293}.visual-fields label{margin-top:0}.visual-fields input{margin-bottom:8px}.visual-upload-row{display:flex;gap:8px;align-items:center}.visual-upload-row .visual-file{flex:1}.visual-file{padding:9px!important;background:#fff;border:1px solid var(--line)}.visual-cancel{display:none;white-space:nowrap}.visual-cancel.on{display:inline-flex}.visual-file-hint{font-size:11px;color:#7e8688;margin:-2px 0 10px}.visual-section-title{display:flex;align-items:center;gap:10px;margin-bottom:12px}.visual-section-title h2{margin:0}.visual-help{font-size:12px;color:#7e8688;line-height:1.5}.visual-grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px}@media(max-width:760px){.visual-media{grid-template-columns:1fr}.visual-preview{height:200px}.visual-grid2{grid-template-columns:1fr}.visual-upload-row{align-items:stretch;flex-direction:column}}
</style>
<h1>Фото, видео и надписи</h1>
<p class="visual-help">Фото и видео можно загрузить прямо с компьютера. После выбора увидишь превью. Если передумал — нажми «Отмена». Для применения файла нажми кнопку сохранения нужного блока.</p>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>
<datalist id="visual-media-list"><?php foreach($mediaList as $m): ?><option value="<?= h(media_url($m['file'])) ?>"><?= h($m['alt']) ?></option><?php endforeach; ?></datalist>
<div class="panel"><form method="get" class="row"><div style="flex:1"><label>Страница</label><select name="page" onchange="this.form.submit()"><?php foreach($pages as $p): ?><option value="<?= (int)$p['id'] ?>" <?= $pageId==(int)$p['id']?'selected':'' ?>><?= h($p['title']) ?> (<?= h($p['slug']) ?>)</option><?php endforeach; ?></select></div></form></div>

<?php if(isset($byType['hero'])): $b=$byType['hero'];$d=json_decode($b['data']??'{}',true)?:[]; ?>
<div class="panel"><form method="post" enctype="multipart/form-data"><input type="hidden" name="a" value="save_block"><input type="hidden" name="page_id" value="<?= $pageId ?>"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><div class="visual-section-title"><h2>Обложка / главный слайдер</h2><span class="tag">Hero</span></div><div class="visual-grid2"><div><label>Надзаголовок</label><input name="t1" value="<?= h($d['t1']??'') ?>"></div><div><label>Текст кнопки</label><input name="cta" value="<?= h($d['cta']??'') ?>"></div></div><label>Главный заголовок</label><textarea name="h1" style="min-height:64px"><?= h($d['h1']??'') ?></textarea><label>Подзаголовок</label><input name="t2" value="<?= h($d['t2']??'') ?>"><?php $slides=$d['slides']??[]; for($i=0;$i<6;$i++):$r=$slides[$i]??[];v_media_field('slide',$i,$r['src']??'',$r['label']??'','Слайд '.($i+1));endfor; ?><button class="btn">Сохранить обложку</button></form></div><?php endif; ?>

<?php if(isset($byType['scenarios'])): $b=$byType['scenarios'];$d=json_decode($b['data']??'{}',true)?:[]; ?>
<div class="panel"><form method="post" enctype="multipart/form-data"><input type="hidden" name="a" value="save_block"><input type="hidden" name="page_id" value="<?= $pageId ?>"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><div class="visual-section-title"><h2>Вода. Природа. Свобода.</h2><span class="tag">Слайдер</span></div><label>Заголовок блока</label><input name="title" value="<?= h($d['title']??'') ?>"><?php $items=$d['items']??[]; for($i=0;$i<6;$i++):$r=$items[$i]??[];v_media_field('item',$i,$r['src']??'',$r['label']??'','Кадр '.($i+1)); ?><div class="visual-grid2"><div><label>Заголовок на фото</label><input name="item[t][<?= $i ?>]" value="<?= h($r['t']??'') ?>"></div><div><label>Текст на фото</label><input name="item[text][<?= $i ?>]" value="<?= h($r['text']??'') ?>"></div></div><?php endfor; ?><button class="btn">Сохранить слайдер</button></form></div><?php endif; ?>

<?php if(isset($byType['build'])): $b=$byType['build'];$d=json_decode($b['data']??'{}',true)?:[]; ?>
<div class="panel"><form method="post" enctype="multipart/form-data"><input type="hidden" name="a" value="save_block"><input type="hidden" name="page_id" value="<?= $pageId ?>"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><div class="visual-section-title"><h2>Продуманная конструкция</h2><span class="tag">Слайдер деталей</span></div><label>Заголовок блока</label><input name="title" value="<?= h($d['title']??'') ?>"><label>Текст слева</label><textarea name="text"><?= h($d['text']??'') ?></textarea><?php $items=$d['details']??[]; for($i=0;$i<6;$i++):$r=$items[$i]??[];v_media_field('item',$i,$r['src']??'',$r['label']??'','Деталь '.($i+1)); ?><div class="visual-grid2"><div><label>Заголовок на фото</label><input name="item[t][<?= $i ?>]" value="<?= h($r['t']??'') ?>"></div><div><label>Описание на фото</label><input name="item[text][<?= $i ?>]" value="<?= h($r['text']??'') ?>"></div></div><?php endfor; ?><button class="btn">Сохранить конструкцию</button></form></div><?php endif; ?>

<?php if(isset($byType['kit'])): $b=$byType['kit'];$d=json_decode($b['data']??'{}',true)?:[]; ?>
<div class="panel"><form method="post" enctype="multipart/form-data"><input type="hidden" name="a" value="save_block"><input type="hidden" name="page_id" value="<?= $pageId ?>"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><div class="visual-section-title"><h2>В комплекте</h2><span class="tag">Галерея</span></div><label>Заголовок блока</label><input name="title" value="<?= h($d['title']??'') ?>"><?php $shots=$d['shots']??[];$labels=$d['shot_labels']??[];for($i=0;$i<3;$i++):v_media_field('shot',$i,$shots[$i]??'',$labels[$i]??'','Фото комплекта '.($i+1));endfor; ?><label>Список комплекта — каждый пункт с новой строки</label><textarea name="items"><?= h(implode("\n",$d['items']??[])) ?></textarea><button class="btn">Сохранить комплект</button></form></div><?php endif; ?>

<div class="panel"><form method="post" enctype="multipart/form-data"><input type="hidden" name="a" value="save_brand"><input type="hidden" name="page_id" value="<?= $pageId ?>"><div class="visual-section-title"><h2>О бренде</h2><span class="tag">Большое фото / видео</span></div><div class="visual-grid2"><div><label>Метка</label><input name="label" value="<?= h($brand['label']) ?>"></div><div><label>Заголовок</label><input name="title" value="<?= h($brand['title']) ?>"></div></div><label>Текст — абзацы разделяй пустой строкой</label><textarea name="text" style="min-height:180px"><?= h($brand['text']) ?></textarea><label>Текст ссылки</label><input name="link_text" value="<?= h($brand['link_text']) ?>"><?php v_media_field('brand',0,$brand['media_src'],$brand['media_label'],'Большое фото / видео'); ?><button class="btn">Сохранить «О бренде»</button></form></div>
<script>
document.addEventListener('change',function(e){
  var input=e.target.closest('.visual-file'); if(!input)return;
  var box=input.closest('.visual-media'), preview=box.querySelector('.visual-preview'), cancel=box.querySelector('.visual-cancel');
  if(!input.files || !input.files[0]){cancel.classList.remove('on');return;}
  var file=input.files[0], url=URL.createObjectURL(file), node;
  preview.innerHTML='';
  if(file.type.indexOf('video/')===0){node=document.createElement('video');node.controls=true;node.muted=true;}
  else {node=document.createElement('img');}
  node.src=url;preview.appendChild(node);cancel.classList.add('on');
});
document.addEventListener('click',function(e){
  var btn=e.target.closest('.visual-cancel'); if(!btn)return;
  var box=btn.closest('.visual-media'), input=box.querySelector('.visual-file'), preview=box.querySelector('.visual-preview'), original=box.getAttribute('data-original-src')||'';
  input.value=''; btn.classList.remove('on'); preview.innerHTML='';
  if(original){var isVideo=/\.(mp4|webm)(?:\?|$)/i.test(original), node=document.createElement(isVideo?'video':'img');node.src=original;if(isVideo){node.controls=true;node.muted=true;}preview.appendChild(node);}
  else {preview.innerHTML='<div class="visual-empty">нет файла</div>';}
});
</script>
<?php foot();