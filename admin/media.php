<?php
require 'inc.php';
require dirname(__DIR__) . '/inc/blocks.php';
need_auth();
$msg = '';

/* ---- загрузка файлов ---- */
if (!empty($_FILES['f']['tmp_name'][0])) {
  if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
  $ok = 0; $bad = [];
  foreach ($_FILES['f']['tmp_name'] as $i => $tmp) {
    if (!is_uploaded_file($tmp)) continue;
    $orig = $_FILES['f']['name'][$i];
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif','svg','mp4','webm'])) { $bad[] = $orig; continue; }
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (move_uploaded_file($tmp, UPLOAD_DIR . '/' . $name)) {
      @chmod(UPLOAD_DIR . '/' . $name, 0644);
      db()->prepare("INSERT INTO media (file,alt,mime,filesize) VALUES (?,?,?,?)")
          ->execute([$name, pathinfo($orig, PATHINFO_FILENAME), $_FILES['f']['type'][$i], $_FILES['f']['size'][$i]]);
      $ok++;
    }
  }
  $msg = 'Загружено файлов: ' . $ok . ($bad ? '. Не приняты (неподходящий формат): ' . implode(', ', $bad) : '');
}

/* ---- добавление по ссылке ---- */
if (($_POST['a'] ?? '') === 'url') {
  $u = trim($_POST['url'] ?? '');
  if ($u !== '' && preg_match('~^https?://~i', $u)) {
    db()->prepare("INSERT INTO media (file,alt,mime) VALUES (?,?,?)")
        ->execute([$u, trim($_POST['alt'] ?? ''), 'external']);
    $msg = 'Ссылка добавлена в медиатеку';
  } else $msg = 'Нужна ссылка, начинающаяся с http:// или https://';
}

/* ---- подпись ---- */
if (($_POST['a'] ?? '') === 'alt') {
  db()->prepare("UPDATE media SET alt=? WHERE id=?")->execute([trim($_POST['alt']), (int)$_POST['id']]);
  $msg = 'Подпись сохранена';
}

/* ---- удаление ---- */
if (($_POST['a'] ?? '') === 'del') {
  $id = (int)$_POST['id'];
  $used = (int)db()->query("SELECT COUNT(*) FROM product_media WHERE media_id=$id")->fetchColumn();
  if ($used) {
    $msg = 'Файл используется в товаре — сначала отвяжите его на странице товара';
  } else {
    $st = db()->prepare("SELECT file FROM media WHERE id=?"); $st->execute([$id]);
    $f = $st->fetchColumn();
    if ($f && !preg_match('~^https?://~i', $f)) @unlink(UPLOAD_DIR . '/' . $f);
    db()->prepare("DELETE FROM media WHERE id=?")->execute([$id]);
    $msg = 'Удалено';
  }
}

$list = db()->query("SELECT * FROM media ORDER BY id DESC")->fetchAll();

head('Медиа'); ?>
<h1>Медиатека</h1>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

<div class="panel">
  <form method="post" enctype="multipart/form-data" class="row">
    <div style="flex:1"><label>Свои файлы — jpg, png, webp, svg, mp4</label><input type="file" name="f[]" multiple></div>
    <button class="btn" style="align-self:end">Загрузить</button>
  </form>
</div>

<div class="panel">
  <form method="post" class="row">
    <input type="hidden" name="a" value="url">
    <div style="flex:2"><label>Или ссылка на чужой файл</label><input name="url" placeholder="https://…"></div>
    <div style="flex:1"><label>Подпись</label><input name="alt"></div>
    <button class="btn grey" style="align-self:end">Добавить ссылку</button>
  </form>
  <p class="tag" style="margin-top:8px">Ссылки удобны, пока нет своей съёмки. Свои файлы надёжнее — их не удалит чужой сервер.</p>
</div>

<div class="panel">
  <b>Файлов в медиатеке: <?= count($list) ?></b>
  <p class="tag" style="margin:8px 0 14px">Путь под картинкой можно скопировать и вставить в поле блока</p>
  <div class="thumbs">
    <?php foreach ($list as $m): $url = media_url($m['file']);
      $isVideo = preg_match('~\.(mp4|webm)$~i', $m['file']); ?>
      <div class="thumb">
        <?php if ($isVideo): ?>
          <div class="ph" style="height:100px;display:flex;align-items:center;justify-content:center">видео</div>
        <?php else: ?>
          <img src="<?= h($url) ?>" alt="" loading="lazy">
        <?php endif; ?>
        <div class="n">
          <input value="<?= h($url) ?>" readonly onclick="this.select()"
                 style="font-size:10px;padding:3px;margin-bottom:4px">
          <form method="post" style="margin-bottom:4px">
            <input type="hidden" name="a" value="alt"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <input name="alt" value="<?= h($m['alt']) ?>" placeholder="подпись" style="font-size:11px;padding:3px">
          </form>
          <form method="post" onsubmit="return confirm('Удалить файл?')">
            <input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <button class="btn sm red">×</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$list): ?><p class="tag">Пока пусто</p><?php endif; ?>
</div>
<?php foot();
