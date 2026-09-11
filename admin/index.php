<?php
require 'inc.php';
need_auth();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $a = $_POST['a'] ?? '';
  if ($a === 'new') {
    $slug = '/' . ltrim(trim($_POST['slug']), '/');
    if ($slug !== '/') $slug = rtrim($slug, '/');
    $st = db()->prepare("INSERT INTO pages (slug,title,status,sort) VALUES (?,?,'draft',0)");
    try { $st->execute([$slug, trim($_POST['title']) ?: 'Без названия']); $msg = 'Страница создана'; }
    catch (Throwable $e) { $msg = 'Такой slug уже есть'; }
  }
  if ($a === 'save') {
    $st = db()->prepare("UPDATE pages SET title=?,slug=?,seo_title=?,seo_desc=?,status=?,sort=? WHERE id=?");
    $st->execute([$_POST['title'], $_POST['slug'], $_POST['seo_title'], $_POST['seo_desc'],
                  $_POST['status'], (int)$_POST['sort'], (int)$_POST['id']]);
    $msg = 'Сохранено';
  }
  if ($a === 'del') {
    db()->prepare("DELETE FROM blocks WHERE page_id=?")->execute([(int)$_POST['id']]);
    db()->prepare("DELETE FROM pages WHERE id=?")->execute([(int)$_POST['id']]);
    $msg = 'Удалено';
  }
}

$edit = null;
if (!empty($_GET['edit'])) {
  $st = db()->prepare("SELECT * FROM pages WHERE id=?");
  $st->execute([(int)$_GET['edit']]);
  $edit = $st->fetch();
}
$pages = db()->query("SELECT * FROM pages ORDER BY sort, id")->fetchAll();

head('Страницы'); ?>
<h1>Страницы</h1>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

<?php if ($edit): ?>
<div class="panel">
  <form method="post">
    <input type="hidden" name="a" value="save"><input type="hidden" name="id" value="<?= (int)$edit['id'] ?>">
    <label>Заголовок</label><input name="title" value="<?= h($edit['title']) ?>" required>
    <label>Slug</label><input name="slug" value="<?= h($edit['slug']) ?>" required>
    <label>SEO title</label><input name="seo_title" value="<?= h($edit['seo_title']) ?>">
    <label>SEO description</label><textarea name="seo_desc" style="min-height:60px"><?= h($edit['seo_desc']) ?></textarea>
    <div class="row">
      <div style="flex:1"><label>Статус</label>
        <select name="status">
          <option value="draft" <?= $edit['status']==='draft'?'selected':'' ?>>Черновик</option>
          <option value="published" <?= $edit['status']==='published'?'selected':'' ?>>Опубликована</option>
        </select></div>
      <div style="width:100px"><label>Сортировка</label><input name="sort" type="number" value="<?= (int)$edit['sort'] ?>"></div>
    </div>
    <div class="row" style="margin-top:16px">
      <button class="btn">Сохранить</button>
      <a class="btn grey" href="blocks.php?page=<?= (int)$edit['id'] ?>">Блоки страницы</a>
      <a class="btn grey" href="index.php">Отмена</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="panel">
  <table>
    <tr><th>Заголовок</th><th>Slug</th><th>Статус</th><th>Блоки</th><th></th></tr>
    <?php foreach ($pages as $p):
      $bc = db()->query("SELECT COUNT(*) FROM blocks WHERE page_id=" . (int)$p['id'])->fetchColumn(); ?>
    <tr>
      <td><?= h($p['title']) ?></td>
      <td><code><?= h($p['slug']) ?></code></td>
      <td><span class="tag <?= $p['status']==='published'?'pub':'' ?>"><?= $p['status']==='published'?'онлайн':'черновик' ?></span></td>
      <td><?= $bc ?></td>
      <td class="row">
        <a class="btn sm" href="?edit=<?= (int)$p['id'] ?>">Правка</a>
        <a class="btn sm grey" href="blocks.php?page=<?= (int)$p['id'] ?>">Блоки</a>
        <form method="post" onsubmit="return confirm('Удалить страницу и все её блоки?')" style="display:inline">
          <input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button class="btn sm red">×</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<div class="panel">
  <form method="post" class="row">
    <input type="hidden" name="a" value="new">
    <div style="flex:2"><label>Заголовок новой страницы</label><input name="title" required></div>
    <div style="flex:1"><label>Slug</label><input name="slug" placeholder="/about" required></div>
    <button class="btn" style="align-self:end">Создать</button>
  </form>
</div>
<?php foot();
