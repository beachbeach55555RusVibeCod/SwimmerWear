<?php
require 'inc.php';
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
    <div style="flex:1"><label>Цвет</label><input name="color" placeholder="хаки" required></div>
    <div style="flex:1"><label>Размер</label><input name="size" placeholder="M" required></div>
    <div style="width:110px"><label>Остаток</label><input name="stock" type="number" value="0"></div>
    <button class="btn" style="align-self:end">Добавить</button>
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
