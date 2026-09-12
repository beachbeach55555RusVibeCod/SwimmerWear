<?php
require 'inc.php';
require dirname(__DIR__) . '/inc/blocks.php';
need_auth();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $a = $_POST['a'] ?? '';
  if ($a === 'new') {
    try {
      db()->prepare("INSERT INTO products (sku,name,price,status,sort) VALUES (?,?,?,'draft',0)")
          ->execute([trim($_POST['sku']), trim($_POST['name']), (int)$_POST['price']]);
      $msg = 'Товар создан';
    } catch (Throwable $e) { $msg = 'Такой артикул уже есть'; }
  }
  if ($a === 'save') {
    db()->prepare("UPDATE products SET sku=?,name=?,price=?,old_price=?,descr=?,compose=?,care=?,kit=?,delivery=?,status=?,sort=? WHERE id=?")
        ->execute([$_POST['sku'],$_POST['name'],(int)$_POST['price'],
                   $_POST['old_price']!==''?(int)$_POST['old_price']:null,
                   $_POST['descr'],$_POST['compose'],$_POST['care'],$_POST['kit'],$_POST['delivery'],
                   $_POST['status'],(int)$_POST['sort'],(int)$_POST['id']]);
    $msg = 'Сохранено';
  }
  if ($a === 'del') {
    db()->prepare("DELETE FROM variants WHERE product_id=?")->execute([(int)$_POST['id']]);
    db()->prepare("DELETE FROM products WHERE id=?")->execute([(int)$_POST['id']]);
    $msg = 'Удалено';
  }
  if ($a === 'var_add') {
    db()->prepare("INSERT INTO variants (product_id,color,size,stock) VALUES (?,?,?,?)
                   ON DUPLICATE KEY UPDATE stock=VALUES(stock)")
        ->execute([(int)$_POST['pid'], trim($_POST['color']), trim($_POST['size']), (int)$_POST['stock']]);
    $msg = 'Вариант добавлен';
  }
  if ($a === 'ph_add') {
    $pid = (int)$_POST['pid'];
    $max = (int)db()->query("SELECT COALESCE(MAX(sort),-1) FROM product_media WHERE product_id=$pid")->fetchColumn();
    foreach ((array)($_POST['media'] ?? []) as $mid) {
      if (!$mid) continue;
      db()->prepare("INSERT INTO product_media (product_id,media_id,color,sort) VALUES (?,?,?,?)")
          ->execute([$pid, (int)$mid, trim($_POST['color'] ?? ''), ++$max]);
    }
    $msg = 'Фотографии привязаны';
  }
  if ($a === 'ph_del') { db()->prepare("DELETE FROM product_media WHERE id=?")->execute([(int)$_POST['id']]); $msg = 'Фото отвязано'; }
  if ($a === 'ph_color') {
    db()->prepare("UPDATE product_media SET color=? WHERE id=?")->execute([trim($_POST['color']), (int)$_POST['id']]);
    $msg = 'Цвет фото изменён';
  }
  if ($a === 'ph_move') {
    $id = (int)$_POST['id'];
    $st = db()->prepare("SELECT * FROM product_media WHERE id=?"); $st->execute([$id]); $cur = $st->fetch();
    if ($cur) {
      $up = $_POST['dir'] === 'up';
      $st = db()->prepare("SELECT * FROM product_media WHERE product_id=? AND sort " . ($up ? '<' : '>') . " ? ORDER BY sort " . ($up ? 'DESC' : 'ASC') . " LIMIT 1");
      $st->execute([$cur['product_id'], $cur['sort']]);
      if ($nb = $st->fetch()) {
        db()->prepare("UPDATE product_media SET sort=? WHERE id=?")->execute([$nb['sort'], $cur['id']]);
        db()->prepare("UPDATE product_media SET sort=? WHERE id=?")->execute([$cur['sort'], $nb['id']]);
      }
    }
  }
  if ($a === 'var_del') { db()->prepare("DELETE FROM variants WHERE id=?")->execute([(int)$_POST['id']]); }
}

$edit = null;
if (!empty($_GET['edit'])) {
  $st = db()->prepare("SELECT * FROM products WHERE id=?"); $st->execute([(int)$_GET['edit']]); $edit = $st->fetch();
}
$list = db()->query("SELECT * FROM products ORDER BY sort, id")->fetchAll();

head('Товары'); ?>
<h1>Товары</h1>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

<?php if ($edit):
  $st = db()->prepare("SELECT * FROM variants WHERE product_id=? ORDER BY color,size");
  $st->execute([$edit['id']]); $vars = $st->fetchAll(); ?>
<div class="panel">
  <form method="post">
    <input type="hidden" name="a" value="save"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <div class="row">
      <div style="flex:1"><label>Артикул</label><input name="sku" value="<?= h($edit['sku']) ?>" required></div>
      <div style="flex:2"><label>Название</label><input name="name" value="<?= h($edit['name']) ?>" required></div>
    </div>
    <div class="row">
      <div style="flex:1"><label>Цена, ₽</label><input name="price" type="number" value="<?= (int)$edit['price'] ?>"></div>
      <div style="flex:1"><label>Старая цена, ₽</label><input name="old_price" type="number" value="<?= $edit['old_price'] ?>"></div>
      <div style="flex:1"><label>Статус</label><select name="status">
        <option value="draft" <?= $edit['status']==='draft'?'selected':'' ?>>Черновик</option>
        <option value="published" <?= $edit['status']==='published'?'selected':'' ?>>Опубликован</option>
      </select></div>
      <div style="width:100px"><label>Сортировка</label><input name="sort" type="number" value="<?= (int)$edit['sort'] ?>"></div>
    </div>
    <label>Описание</label><textarea name="descr"><?= h($edit['descr']) ?></textarea>
    <label>Состав</label><textarea name="compose" style="min-height:70px"><?= h($edit['compose']) ?></textarea>
    <label>Уход</label><textarea name="care" style="min-height:70px"><?= h($edit['care']) ?></textarea>
    <label>Комплектация</label><textarea name="kit" style="min-height:70px"><?= h($edit['kit']) ?></textarea>
    <label>Доставка</label><textarea name="delivery" style="min-height:70px"><?= h($edit['delivery']) ?></textarea>
    <div class="row" style="margin-top:16px">
      <button class="btn">Сохранить</button><a class="btn grey" href="products.php">Отмена</a>
    </div>
  </form>
</div>

<div class="panel">
  <b>Варианты (цвет × размер)</b>
  <table style="margin-top:12px">
    <tr><th>Цвет</th><th>Размер</th><th>Остаток</th><th></th></tr>
    <?php foreach ($vars as $v): ?>
    <tr><td><?= h($v['color']) ?></td><td><?= h($v['size']) ?></td><td><?= (int)$v['stock'] ?></td>
      <td><form method="post" style="display:inline"><input type="hidden" name="a" value="var_del">
        <input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><button class="btn sm red">×</button></form></td></tr>
    <?php endforeach; ?>
  </table>
  <form method="post" class="row" style="margin-top:14px">
    <input type="hidden" name="a" value="var_add"><input type="hidden" name="pid" value="<?= (int)$edit['id'] ?>">
    <div style="flex:1"><label>Цвет</label><input name="color" placeholder="хаки" required list="colorlist"></div>
    <div style="flex:1"><label>Размер</label><input name="size" placeholder="M" required></div>
    <div style="width:110px"><label>Остаток</label><input name="stock" type="number" value="0"></div>
    <button class="btn" style="align-self:end">Добавить</button>
  </form>
</div>

<?php
$st = db()->prepare("SELECT pm.*, m.file, m.alt FROM product_media pm JOIN media m ON m.id=pm.media_id
                     WHERE pm.product_id=? ORDER BY pm.sort, pm.id");
$st->execute([$edit['id']]);
$photos = $st->fetchAll();
$free = db()->query("SELECT * FROM media ORDER BY id DESC")->fetchAll();
$colorNames = [];
foreach ($vars as $v) $colorNames[$v['color']] = 1;
?>
<datalist id="colorlist"><?php foreach (array_keys($colorNames) as $c): ?><option value="<?= h($c) ?>"><?php endforeach; ?></datalist>

<div class="panel">
  <b>Фотографии товара</b>
  <p class="tag" style="margin:8px 0 14px">Фото с указанным цветом показываются только при выборе этого цвета. Без цвета — показываются всегда. Порядок задаёт, какое фото будет первым на карточке.</p>

  <div class="thumbs">
    <?php foreach ($photos as $ph): ?>
      <div class="thumb">
        <img src="<?= h(media_url($ph['file'])) ?>" alt="" loading="lazy">
        <div class="n">
          <form method="post" style="margin-bottom:4px">
            <input type="hidden" name="a" value="ph_color"><input type="hidden" name="id" value="<?= (int)$ph['id'] ?>">
            <select name="color" onchange="this.form.submit()" style="font-size:11px;padding:3px">
              <option value="">— все цвета —</option>
              <?php foreach (array_keys($colorNames) as $c): ?>
                <option value="<?= h($c) ?>" <?= $ph['color'] === $c ? 'selected' : '' ?>><?= h($c) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <div class="row" style="gap:4px">
            <form method="post"><input type="hidden" name="a" value="ph_move"><input type="hidden" name="dir" value="up">
              <input type="hidden" name="id" value="<?= (int)$ph['id'] ?>"><button class="btn sm grey">&larr;</button></form>
            <form method="post"><input type="hidden" name="a" value="ph_move"><input type="hidden" name="dir" value="down">
              <input type="hidden" name="id" value="<?= (int)$ph['id'] ?>"><button class="btn sm grey">&rarr;</button></form>
            <form method="post"><input type="hidden" name="a" value="ph_del">
              <input type="hidden" name="id" value="<?= (int)$ph['id'] ?>"><button class="btn sm red">×</button></form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$photos): ?><p class="tag">К товару пока не привязано ни одного фото</p><?php endif; ?>

  <form method="post" style="margin-top:18px;border-top:1px solid var(--line);padding-top:14px">
    <input type="hidden" name="a" value="ph_add"><input type="hidden" name="pid" value="<?= (int)$edit['id'] ?>">
    <div class="row">
      <div style="flex:1"><label>Цвет для добавляемых фото</label>
        <select name="color">
          <option value="">— все цвета —</option>
          <?php foreach (array_keys($colorNames) as $c): ?><option value="<?= h($c) ?>"><?= h($c) ?></option><?php endforeach; ?>
        </select></div>
      <button class="btn" style="align-self:end">Привязать отмеченные</button>
    </div>
    <label style="margin-top:14px">Выберите фото из медиатеки</label>
    <div class="thumbs">
      <?php foreach ($free as $m): if (preg_match('~\.(mp4|webm)$~i', $m['file'])) continue; ?>
        <label class="thumb" style="cursor:pointer;display:block">
          <img src="<?= h(media_url($m['file'])) ?>" alt="" loading="lazy">
          <div class="n" style="display:flex;gap:6px;align-items:center">
            <input type="checkbox" name="media[]" value="<?= (int)$m['id'] ?>" style="width:auto">
            <span><?= h($m['alt'] ?: $m['file']) ?></span>
          </div>
        </label>
      <?php endforeach; ?>
    </div>
    <?php if (!$free): ?><p class="tag">Медиатека пуста — загрузите файлы в разделе «Медиа»</p><?php endif; ?>
  </form>
</div>
<?php endif; ?>

<div class="panel">
  <table>
    <tr><th>Артикул</th><th>Название</th><th>Цена</th><th>Статус</th><th></th></tr>
    <?php foreach ($list as $p): ?>
    <tr>
      <td><code><?= h($p['sku']) ?></code></td>
      <td><?= h($p['name']) ?></td>
      <td><?= number_format($p['price'],0,'',' ') ?> ₽</td>
      <td><span class="tag <?= $p['status']==='published'?'pub':'' ?>"><?= $p['status']==='published'?'онлайн':'черновик' ?></span></td>
      <td class="row">
        <a class="btn sm" href="?edit=<?= (int)$p['id'] ?>">Правка</a>
        <form method="post" style="display:inline" onsubmit="return confirm('Удалить товар?')">
          <input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button class="btn sm red">×</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php if (!$list): ?><p class="tag" style="margin-top:10px">Товаров пока нет</p><?php endif; ?>
</div>

<div class="panel">
  <form method="post" class="row">
    <input type="hidden" name="a" value="new">
    <div style="flex:1"><label>Артикул</label><input name="sku" placeholder="SWIM-PARKA-001" required></div>
    <div style="flex:2"><label>Название</label><input name="name" placeholder="Речная парка" required></div>
    <div style="width:120px"><label>Цена, ₽</label><input name="price" type="number" value="0"></div>
    <button class="btn" style="align-self:end">Создать</button>
  </form>
</div>
<?php foot();
