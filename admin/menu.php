<?php
require 'inc.php';
need_auth();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $a = $_POST['a'] ?? '';
  if ($a === 'new') {
    db()->prepare("INSERT INTO menu (title,url,place,sort,visible) VALUES (?,?,?,?,1)")
        ->execute([$_POST['title'], $_POST['url'], $_POST['place'], (int)$_POST['sort']]);
    $msg = 'Пункт добавлен';
  }
  if ($a === 'del') { db()->prepare("DELETE FROM menu WHERE id=?")->execute([(int)$_POST['id']]); $msg = 'Удалено'; }
  if ($a === 'save') {
    db()->prepare("UPDATE menu SET title=?,url=?,place=?,sort=?,visible=? WHERE id=?")
        ->execute([$_POST['title'],$_POST['url'],$_POST['place'],(int)$_POST['sort'],
                   isset($_POST['visible'])?1:0,(int)$_POST['id']]);
    $msg = 'Сохранено';
  }
}
$list = db()->query("SELECT * FROM menu ORDER BY place, sort")->fetchAll();
head('Меню'); ?>
<h1>Меню</h1>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>
<div class="panel">
<?php foreach ($list as $m): ?>
  <form method="post" class="row" style="border-bottom:1px solid var(--line);padding:8px 0">
    <input type="hidden" name="a" value="save"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
    <div style="flex:1"><input name="title" value="<?= h($m['title']) ?>"></div>
    <div style="flex:1"><input name="url" value="<?= h($m['url']) ?>"></div>
    <div style="width:110px"><select name="place">
      <option value="header" <?= $m['place']==='header'?'selected':'' ?>>шапка</option>
      <option value="footer" <?= $m['place']==='footer'?'selected':'' ?>>подвал</option></select></div>
    <div style="width:70px"><input name="sort" type="number" value="<?= (int)$m['sort'] ?>"></div>
    <label style="margin:0"><input type="checkbox" name="visible" style="width:auto" <?= $m['visible']?'checked':'' ?>></label>
    <button class="btn sm">OK</button>
  </form>
  <form method="post" style="display:inline-block;margin:-38px 0 0 0"></form>
<?php endforeach; ?>
</div>
<div class="panel">
  <form method="post" class="row">
    <input type="hidden" name="a" value="new">
    <div style="flex:1"><label>Название</label><input name="title" required></div>
    <div style="flex:1"><label>Ссылка</label><input name="url" placeholder="/about" required></div>
    <div style="width:120px"><label>Где</label><select name="place">
      <option value="header">шапка</option><option value="footer">подвал</option></select></div>
    <div style="width:80px"><label>Сорт.</label><input name="sort" type="number" value="0"></div>
    <button class="btn" style="align-self:end">Добавить</button>
  </form>
</div>
<?php foot();
