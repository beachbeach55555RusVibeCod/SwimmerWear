<?php
require 'inc.php';
require dirname(__DIR__) . '/inc/blocks.php';
need_auth();

$msg = '';
$newName = trim($_POST['name'] ?? '');
$newSku = trim($_POST['sku'] ?? '');
$newPrice = (int)($_POST['price'] ?? 0);

function product_colors($pid) {
  $st = db()->prepare("SELECT DISTINCT color FROM variants WHERE product_id=? AND color<>'' ORDER BY color");
  $st->execute([$pid]);
  return array_values(array_filter($st->fetchAll(PDO::FETCH_COLUMN)));
}

function color_hex($name) {
  static $map = null;
  if ($map === null) {
    $map = [];
    $raw = setting('colors', "хаки|#575E43\nчёрный|#22262A\nсиний|#41546B");
    foreach (preg_split('/\n+/', $raw) as $line) {
      $parts = array_map('trim', explode('|', $line));
      if (count($parts) >= 2 && $parts[0] !== '') $map[mb_strtolower($parts[0])] = $parts[1];
    }
  }
  $key = mb_strtolower(trim((string)$name));
  return $map[$key] ?? '#9AA0A2';
}

function sku_exists($sku, $excludeId = 0) {
  if ($sku === '') return false;
  if ($excludeId > 0) {
    $st = db()->prepare("SELECT id FROM products WHERE sku=? AND id<>? LIMIT 1");
    $st->execute([$sku, $excludeId]);
  } else {
    $st = db()->prepare("SELECT id FROM products WHERE sku=? LIMIT 1");
    $st->execute([$sku]);
  }
  return (bool)$st->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $a = $_POST['a'] ?? '';
  $pid = (int)($_POST['pid'] ?? $_POST['id'] ?? 0);

  if ($a === 'new') {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $price = (int)($_POST['price'] ?? 0);

    if ($name === '' || $sku === '') {
      $msg = 'Заполните название и артикул.';
    } elseif (sku_exists($sku)) {
      $msg = 'Товар с артикулом «' . $sku . '» уже существует. Укажите другой артикул.';
    } else {
      try {
        db()->prepare("INSERT INTO products (sku,name,price,status,sort) VALUES (?,?,?,'draft',0)")
          ->execute([$sku, $name, $price]);
        $pid = (int)db()->lastInsertId();
        header('Location: product-cards.php?edit=' . $pid); exit;
      } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) == 1062) {
          $msg = 'Товар с таким артикулом уже существует. Укажите другой артикул.';
        } else {
          $msg = 'Не удалось создать товар. Попробуйте ещё раз.';
        }
      }
    }
  }

  if ($a === 'save') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    if ($name === '' || $sku === '') {
      $msg = 'Название и артикул не могут быть пустыми.';
    } elseif (sku_exists($sku, $pid)) {
      $msg = 'Этот артикул уже используется другим товаром.';
    } else {
      try {
        db()->prepare("UPDATE products SET sku=?,name=?,price=?,status=? WHERE id=?")
          ->execute([$sku, $name, (int)$_POST['price'], $_POST['status']==='published'?'published':'draft', $pid]);
        $msg = 'Карточка сохранена';
      } catch (PDOException $e) {
        $msg = (($e->errorInfo[1] ?? 0) == 1062)
          ? 'Этот артикул уже используется другим товаром.'
          : 'Не удалось сохранить карточку.';
      }
    }
  }

  if ($a === 'color_add') {
    $color = trim($_POST['color'] ?? '');
    if ($color !== '') {
      foreach (['XS','S','M','L','XL'] as $size) {
        db()->prepare("INSERT INTO variants (product_id,color,size,stock) VALUES (?,?,?,0) ON DUPLICATE KEY UPDATE color=VALUES(color)")
          ->execute([$pid,$color,$size]);
      }
      $msg = 'Цвет добавлен';
    }
  }

  if ($a === 'color_del') {
    $color = trim($_POST['color'] ?? '');
    db()->prepare("DELETE FROM variants WHERE product_id=? AND color=?")->execute([$pid,$color]);
    $msg = 'Цвет удалён';
  }

  if ($a === 'photo_color') {
    db()->prepare("UPDATE product_media SET color=? WHERE id=? AND product_id=?")
      ->execute([trim($_POST['color'] ?? ''),(int)$_POST['photo_id'],$pid]);
    $msg = 'Цвет фотографии сохранён';
  }

  if ($a === 'photo_del') {
    $id = (int)$_POST['photo_id'];
    $st = db()->prepare("SELECT pm.media_id,m.file FROM product_media pm JOIN media m ON m.id=pm.media_id WHERE pm.id=? AND pm.product_id=?");
    $st->execute([$id,$pid]);
    $row = $st->fetch();
    if ($row) {
      db()->prepare("DELETE FROM product_media WHERE id=?")->execute([$id]);
      $used = db()->prepare("SELECT COUNT(*) FROM product_media WHERE media_id=?");
      $used->execute([$row['media_id']]);
      if (!(int)$used->fetchColumn()) {
        if (!preg_match('~^https?://~i',$row['file'])) @unlink(UPLOAD_DIR.'/'.$row['file']);
        db()->prepare("DELETE FROM media WHERE id=?")->execute([$row['media_id']]);
      }
    }
    $msg = 'Фото удалено';
  }

  if ($a === 'photo_move') {
    $id = (int)$_POST['photo_id'];
    $dir = $_POST['dir'] ?? 'up';
    $st = db()->prepare("SELECT id,sort FROM product_media WHERE id=? AND product_id=?");
    $st->execute([$id,$pid]);
    $cur = $st->fetch();
    if ($cur) {
      $op = $dir === 'up' ? '<' : '>';
      $ord = $dir === 'up' ? 'DESC' : 'ASC';
      $st = db()->prepare("SELECT id,sort FROM product_media WHERE product_id=? AND sort $op ? ORDER BY sort $ord LIMIT 1");
      $st->execute([$pid,$cur['sort']]);
      if ($nb = $st->fetch()) {
        db()->prepare("UPDATE product_media SET sort=? WHERE id=?")->execute([$nb['sort'],$cur['id']]);
        db()->prepare("UPDATE product_media SET sort=? WHERE id=?")->execute([$cur['sort'],$nb['id']]);
      }
    }
  }

  if ($a === 'photo_upload' && !empty($_FILES['photos']['tmp_name'][0])) {
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR,0755,true);
    $color = trim($_POST['color'] ?? '');
    $st = db()->prepare("SELECT COALESCE(MAX(sort),-1) FROM product_media WHERE product_id=?");
    $st->execute([$pid]);
    $sort = (int)$st->fetchColumn();
    $ok = 0;
    foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
      if (!is_uploaded_file($tmp)) continue;
      $orig = $_FILES['photos']['name'][$i];
      $ext = strtolower(pathinfo($orig,PATHINFO_EXTENSION));
      if (!in_array($ext,['jpg','jpeg','png','webp'],true)) continue;
      $name = date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.'.$ext;
      if (move_uploaded_file($tmp,UPLOAD_DIR.'/'.$name)) {
        @chmod(UPLOAD_DIR.'/'.$name,0644);
        db()->prepare("INSERT INTO media (file,alt,mime,filesize) VALUES (?,?,?,?)")
          ->execute([$name,pathinfo($orig,PATHINFO_FILENAME),$_FILES['photos']['type'][$i],$_FILES['photos']['size'][$i]]);
        $mid = (int)db()->lastInsertId();
        db()->prepare("INSERT INTO product_media (product_id,media_id,color,sort) VALUES (?,?,?,?)")
          ->execute([$pid,$mid,$color,++$sort]);
        $ok++;
      }
    }
    $msg = 'Добавлено фото: ' . $ok;
  }
}

$edit = null;
if (!empty($_GET['edit'])) {
  $st = db()->prepare("SELECT * FROM products WHERE id=?");
  $st->execute([(int)$_GET['edit']]);
  $edit = $st->fetch();
}
$list = db()->query("SELECT * FROM products ORDER BY sort,id")->fetchAll();
head('Карточки товара');
?>
<style>
.color-chips{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0 18px}.color-chip-form{margin:0}.color-chip{display:inline-flex;align-items:center;gap:7px;min-height:34px;padding:7px 11px;border:1px solid #cfd4d5;background:#fff;color:#191d1e;border-radius:2px;font:inherit;font-size:12px;line-height:1;cursor:pointer}.color-chip:hover{border-color:#92999a;background:#f8f9f9}.color-chip__dot{width:10px;height:10px;border-radius:50%;border:1px solid rgba(0,0,0,.15);flex:0 0 10px}.color-chip__x{margin-left:2px;color:#8b9192}.color-radio-group{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}.color-radio{position:relative;margin:0}.color-radio input{position:absolute;opacity:0;pointer-events:none}.color-radio__body{display:inline-flex;align-items:center;gap:7px;min-height:34px;padding:7px 11px;border:1px solid #cfd4d5;background:#fff;color:#191d1e;border-radius:2px;font-size:12px;line-height:1;cursor:pointer;box-sizing:border-box}.color-radio__body:hover{border-color:#92999a}.color-radio input:checked+.color-radio__body{border-color:#191d1e;box-shadow:inset 0 0 0 1px #191d1e}.color-radio__dot{width:10px;height:10px;border-radius:50%;border:1px solid rgba(0,0,0,.15);flex:0 0 10px}.photo-color-form{margin:0 0 10px}.photo-color-form>label{margin-bottom:6px}.photo-color-form .color-radio-group{gap:6px}.photo-color-form .color-radio__body{min-height:30px;padding:6px 8px;font-size:11px}
</style>
<h1>Карточки товара</h1>
<p class="tag" style="margin:-8px 0 22px">Здесь только товар, его цвета и фотографии. Фото, которому назначен цвет, показывается на сайте при выборе этого цвета.</p>
<?php if($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

<?php if($edit): $colors = product_colors($edit['id']); ?>
<div class="panel">
  <h2 style="margin-top:0">Основное</h2>
  <form method="post">
    <input type="hidden" name="a" value="save"><input type="hidden" name="pid" value="<?= (int)$edit['id'] ?>">
    <div class="row"><div style="flex:2"><label>Название</label><input name="name" value="<?= h($edit['name']) ?>" required></div><div style="flex:1"><label>Артикул</label><input name="sku" value="<?= h($edit['sku']) ?>" required></div></div>
    <div class="row"><div style="width:180px"><label>Цена, ₽</label><input type="number" name="price" value="<?= (int)$edit['price'] ?>"></div><div style="width:220px"><label>Статус</label><select name="status"><option value="draft" <?= $edit['status']==='draft'?'selected':'' ?>>Черновик</option><option value="published" <?= $edit['status']==='published'?'selected':'' ?>>Опубликован</option></select></div></div>
    <button class="btn">Сохранить карточку</button>
  </form>
</div>

<div class="panel">
  <h2 style="margin-top:0">Цвета товара</h2>
  <p class="tag">Добавленный здесь цвет появится в выборе цвета на сайте.</p>
  <div class="color-chips">
    <?php foreach($colors as $c): ?>
      <form method="post" class="color-chip-form" onsubmit="return confirm('Удалить цвет <?= h($c) ?>?')">
        <input type="hidden" name="a" value="color_del"><input type="hidden" name="pid" value="<?= (int)$edit['id'] ?>"><input type="hidden" name="color" value="<?= h($c) ?>">
        <button class="color-chip" type="submit"><span class="color-chip__dot" style="background:<?= h(color_hex($c)) ?>"></span><span><?= h($c) ?></span><span class="color-chip__x">×</span></button>
      </form>
    <?php endforeach; ?>
    <?php if(!$colors): ?><span class="tag">Цветов пока нет</span><?php endif; ?>
  </div>
  <form method="post" class="row"><input type="hidden" name="a" value="color_add"><input type="hidden" name="pid" value="<?= (int)$edit['id'] ?>"><div style="flex:1"><label>Новый цвет</label><input name="color" placeholder="например: хаки" required></div><button class="btn" style="align-self:end">Добавить цвет</button></form>
</div>

<?php
$st = db()->prepare("SELECT pm.*,m.file,m.alt FROM product_media pm JOIN media m ON m.id=pm.media_id WHERE pm.product_id=? ORDER BY pm.sort,pm.id");
$st->execute([$edit['id']]);
$photos = $st->fetchAll();
?>
<div class="panel">
  <h2 style="margin-top:0">Фотографии</h2>
  <form method="post" enctype="multipart/form-data" style="padding:16px;background:#f3f4f4;margin-bottom:22px">
    <input type="hidden" name="a" value="photo_upload"><input type="hidden" name="pid" value="<?= (int)$edit['id'] ?>">
    <div class="row">
      <div style="flex:2"><label>Добавить фото с компьютера</label><input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required></div>
      <div style="flex:1"><label>Цвет этих фото</label><div class="color-radio-group"><label class="color-radio"><input type="radio" name="color" value="" checked><span class="color-radio__body">Для всех цветов</span></label><?php foreach($colors as $c): ?><label class="color-radio"><input type="radio" name="color" value="<?= h($c) ?>"><span class="color-radio__body"><span class="color-radio__dot" style="background:<?= h(color_hex($c)) ?>"></span><?= h($c) ?></span></label><?php endforeach; ?></div></div>
      <button class="btn" style="align-self:end">Загрузить</button>
    </div>
  </form>

  <div class="thumbs">
    <?php foreach($photos as $ph): ?>
    <div class="thumb" style="min-width:180px">
      <img src="<?= h(media_url($ph['file'])) ?>" alt="" loading="lazy">
      <div class="n">
        <form method="post" class="photo-color-form">
          <input type="hidden" name="a" value="photo_color"><input type="hidden" name="pid" value="<?= (int)$edit['id'] ?>"><input type="hidden" name="photo_id" value="<?= (int)$ph['id'] ?>">
          <label>Показывать для цвета</label>
          <div class="color-radio-group">
            <label class="color-radio"><input type="radio" name="color" value="" <?= $ph['color']===''?'checked':'' ?> onchange="this.form.submit()"><span class="color-radio__body">Все цвета</span></label>
            <?php foreach($colors as $c): ?><label class="color-radio"><input type="radio" name="color" value="<?= h($c) ?>" <?= $ph['color']===$c?'checked':'' ?> onchange="this.form.submit()"><span class="color-radio__body"><span class="color-radio__dot" style="background:<?= h(color_hex($c)) ?>"></span><?= h($c) ?></span></label><?php endforeach; ?>
          </div>
        </form>
        <div class="row" style="gap:5px"><form method="post"><input type="hidden" name="a" value="photo_move"><input type="hidden" name="pid" value="<?= (int)$edit['id'] ?>"><input type="hidden" name="photo_id" value="<?= (int)$ph['id'] ?>"><input type="hidden" name="dir" value="up"><button class="btn sm grey">←</button></form><form method="post"><input type="hidden" name="a" value="photo_move"><input type="hidden" name="pid" value="<?= (int)$edit['id'] ?>"><input type="hidden" name="photo_id" value="<?= (int)$ph['id'] ?>"><input type="hidden" name="dir" value="down"><button class="btn sm grey">→</button></form><form method="post" onsubmit="return confirm('Удалить фото из карточки?')"><input type="hidden" name="a" value="photo_del"><input type="hidden" name="pid" value="<?= (int)$edit['id'] ?>"><input type="hidden" name="photo_id" value="<?= (int)$ph['id'] ?>"><button class="btn sm red">Удалить</button></form></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if(!$photos): ?><p class="tag">Фото пока нет</p><?php endif; ?>
</div>
<a class="btn grey" href="product-cards.php">← К списку товаров</a>
<?php else: ?>
<div class="panel"><table><tr><th>Товар</th><th>Артикул</th><th>Цена</th><th>Статус</th><th></th></tr><?php foreach($list as $p): ?><tr><td><?= h($p['name']) ?></td><td><?= h($p['sku']) ?></td><td><?= number_format($p['price'],0,'',' ') ?> ₽</td><td><?= $p['status']==='published'?'Опубликован':'Черновик' ?></td><td><a class="btn sm" href="?edit=<?= (int)$p['id'] ?>">Редактировать</a></td></tr><?php endforeach; ?></table></div>
<div class="panel"><h2 style="margin-top:0">Добавить товар</h2><form method="post" class="row"><input type="hidden" name="a" value="new"><div style="flex:2"><label>Название</label><input name="name" value="<?= h($newName) ?>" required></div><div style="flex:1"><label>Артикул</label><input name="sku" value="<?= h($newSku) ?>" required><div class="tag" style="margin-top:5px">Артикул должен быть уникальным для каждого товара.</div></div><div style="width:150px"><label>Цена, ₽</label><input type="number" name="price" value="<?= (int)$newPrice ?>"></div><button class="btn" style="align-self:end">Создать</button></form></div>
<?php endif; foot();
