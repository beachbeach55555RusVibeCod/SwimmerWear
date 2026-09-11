<?php
require 'inc.php';
need_auth();
$msg = '';

if (!empty($_FILES['f'])) {
  if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
  $ok = 0;
  foreach ($_FILES['f']['tmp_name'] as $i => $tmp) {
    if (!is_uploaded_file($tmp)) continue;
    $ext = strtolower(pathinfo($_FILES['f']['name'][$i], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif','svg','mp4'])) continue;
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (move_uploaded_file($tmp, UPLOAD_DIR . '/' . $name)) {
      db()->prepare("INSERT INTO media (file,alt,mime,filesize) VALUES (?,?,?,?)")
          ->execute([$name, $_FILES['f']['name'][$i], $_FILES['f']['type'][$i], $_FILES['f']['size'][$i]]);
      $ok++;
    }
  }
  $msg = "Загружено файлов: $ok";
}

if (($_POST['a'] ?? '') === 'del') {
  $st = db()->prepare("SELECT file FROM media WHERE id=?"); $st->execute([(int)$_POST['id']]);
  if ($f = $st->fetchColumn()) @unlink(UPLOAD_DIR . '/' . $f);
  db()->prepare("DELETE FROM media WHERE id=?")->execute([(int)$_POST['id']]);
  $msg = 'Удалено';
}

$list = db()->query("SELECT * FROM media ORDER BY id DESC")->fetchAll();
head('Медиа'); ?>
<h1>Медиатека</h1>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

<div class="panel">
  <form method="post" enctype="multipart/form-data" class="row">
    <div style="flex:1"><label>Файлы (jpg, png, webp, svg, mp4)</label><input type="file" name="f[]" multiple></div>
    <button class="btn" style="align-self:end">Загрузить</button>
  </form>
</div>

<div class="panel">
  <div class="thumbs">
    <?php foreach ($list as $m): ?>
    <div class="thumb">
      <img src="<?= UPLOAD_URL ?>/<?= h($m['file']) ?>" alt="">
      <div class="n"><?= h($m['file']) ?>
        <form method="post" style="margin-top:6px"><input type="hidden" name="a" value="del">
          <input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn sm red">×</button></form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$list): ?><p class="tag">Файлов пока нет</p><?php endif; ?>
</div>
<?php foot();
