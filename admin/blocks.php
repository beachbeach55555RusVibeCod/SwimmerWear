<?php
require 'inc.php';
need_auth();

$types = [
  'hero'     => 'Hero (обложка)',
  'text'     => 'Текст',
  'features' => 'Преимущества (3 карточки)',
  'products' => 'Товары',
  'faq'      => 'FAQ',
];

$pageId = (int)($_GET['page'] ?? 0);
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $a = $_POST['a'] ?? '';
  $pageId = (int)($_POST['page_id'] ?? $pageId);

  if ($a === 'add') {
    $max = (int)db()->query("SELECT COALESCE(MAX(sort),-1) FROM blocks WHERE page_id=$pageId")->fetchColumn();
    db()->prepare("INSERT INTO blocks (page_id,type,sort,data,visible) VALUES (?,?,?,'{}',1)")
        ->execute([$pageId, $_POST['type'], $max + 1]);
    $msg = 'Блок добавлен';
  }
  if ($a === 'save') {
    $id = (int)$_POST['id'];
    $type = $_POST['type'];
    $d = [];
    if ($type === 'hero')     $d = ['title'=>$_POST['title'],'subtitle'=>$_POST['subtitle'],'cta'=>$_POST['cta']];
    if ($type === 'text')     $d = ['title'=>$_POST['title'],'content'=>$_POST['content']];
    if ($type === 'products') $d = ['title'=>$_POST['title']];
    if ($type === 'features') {
      $d = ['title'=>$_POST['title'],'items'=>[]];
      foreach (($_POST['ft'] ?? []) as $i => $t)
        if (trim($t) !== '') $d['items'][] = ['t'=>$t,'d'=>$_POST['fd'][$i] ?? ''];
    }
    if ($type === 'faq') {
      $d = ['title'=>$_POST['title'],'items'=>[]];
      foreach (($_POST['q'] ?? []) as $i => $q)
        if (trim($q) !== '') $d['items'][] = ['q'=>$q,'a'=>$_POST['ans'][$i] ?? ''];
    }
    db()->prepare("UPDATE blocks SET data=?, visible=? WHERE id=?")
        ->execute([json_encode($d, JSON_UNESCAPED_UNICODE), isset($_POST['visible'])?1:0, $id]);
    $msg = 'Блок сохранён';
  }
  if ($a === 'del') { db()->prepare("DELETE FROM blocks WHERE id=?")->execute([(int)$_POST['id']]); $msg = 'Блок удалён'; }
  if ($a === 'move') {
    $id = (int)$_POST['id']; $dir = $_POST['dir'] === 'up' ? -1 : 1;
    $st = db()->prepare("SELECT * FROM blocks WHERE id=?"); $st->execute([$id]); $cur = $st->fetch();
    if ($cur) {
      $op = $dir < 0 ? '<' : '>'; $ord = $dir < 0 ? 'DESC' : 'ASC';
      $st = db()->prepare("SELECT * FROM blocks WHERE page_id=? AND sort $op ? ORDER BY sort $ord LIMIT 1");
      $st->execute([$cur['page_id'], $cur['sort']]);
      if ($nb = $st->fetch()) {
        db()->prepare("UPDATE blocks SET sort=? WHERE id=?")->execute([$nb['sort'], $cur['id']]);
        db()->prepare("UPDATE blocks SET sort=? WHERE id=?")->execute([$cur['sort'], $nb['id']]);
      }
    }
  }
}

$pages = db()->query("SELECT * FROM pages ORDER BY sort, id")->fetchAll();
if (!$pageId && $pages) $pageId = (int)$pages[0]['id'];

$st = db()->prepare("SELECT * FROM blocks WHERE page_id=? ORDER BY sort");
$st->execute([$pageId]);
$blocks = $st->fetchAll();

head('Блоки'); ?>
<h1>Блоки страницы</h1>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

<div class="panel row">
  <form method="get" class="row" style="flex:1">
    <div style="flex:1"><label>Страница</label>
      <select name="page" onchange="this.form.submit()">
        <?php foreach ($pages as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= $p['id']==$pageId?'selected':'' ?>><?= h($p['title']) ?> (<?= h($p['slug']) ?>)</option>
        <?php endforeach; ?>
      </select></div>
  </form>
  <form method="post" class="row">
    <input type="hidden" name="a" value="add"><input type="hidden" name="page_id" value="<?= $pageId ?>">
    <div><label>Добавить блок</label>
      <select name="type"><?php foreach ($types as $k=>$v): ?><option value="<?= $k ?>"><?= h($v) ?></option><?php endforeach; ?></select></div>
    <button class="btn" style="align-self:end">+ Добавить</button>
  </form>
</div>

<?php foreach ($blocks as $i => $b):
  $d = json_decode($b['data'] ?? '{}', true) ?: []; ?>
<div class="panel">
  <form method="post">
    <input type="hidden" name="a" value="save">
    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
    <input type="hidden" name="type" value="<?= h($b['type']) ?>">
    <input type="hidden" name="page_id" value="<?= $pageId ?>">
    <div class="row">
      <b><?= h($types[$b['type']] ?? $b['type']) ?></b>
      <span class="tag">#<?= $i+1 ?></span>
      <label class="row" style="margin:0;gap:6px;font-weight:400">
        <input type="checkbox" name="visible" style="width:auto" <?= $b['visible']?'checked':'' ?>> показывать</label>
      <span class="right"></span>
    </div>

    <?php if ($b['type']==='hero'): ?>
      <label>Заголовок</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <label>Подзаголовок</label><input name="subtitle" value="<?= h($d['subtitle'] ?? '') ?>">
      <label>Текст кнопки</label><input name="cta" value="<?= h($d['cta'] ?? '') ?>">

    <?php elseif ($b['type']==='text'): ?>
      <label>Заголовок</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <label>Текст</label><textarea name="content"><?= h($d['content'] ?? '') ?></textarea>

    <?php elseif ($b['type']==='products'): ?>
      <label>Заголовок секции</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <p class="tag">Товары берутся из раздела «Товары» со статусом «опубликован»</p>

    <?php elseif ($b['type']==='features'): ?>
      <label>Заголовок</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <?php for ($j=0; $j<3; $j++): $it = $d['items'][$j] ?? ['t'=>'','d'=>'']; ?>
        <div class="row">
          <div style="flex:1"><label>Пункт <?= $j+1 ?> — заголовок</label><input name="ft[<?= $j ?>]" value="<?= h($it['t']) ?>"></div>
          <div style="flex:2"><label>Описание</label><input name="fd[<?= $j ?>]" value="<?= h($it['d']) ?>"></div>
        </div>
      <?php endfor; ?>

    <?php elseif ($b['type']==='faq'): ?>
      <label>Заголовок</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <?php for ($j=0; $j<5; $j++): $it = $d['items'][$j] ?? ['q'=>'','a'=>'']; ?>
        <div class="row">
          <div style="flex:1"><label>Вопрос <?= $j+1 ?></label><input name="q[<?= $j ?>]" value="<?= h($it['q']) ?>"></div>
          <div style="flex:2"><label>Ответ</label><input name="ans[<?= $j ?>]" value="<?= h($it['a']) ?>"></div>
        </div>
      <?php endfor; ?>
    <?php endif; ?>

    <div class="row" style="margin-top:16px">
      <button class="btn">Сохранить</button>
    </div>
  </form>
  <div class="row" style="margin-top:10px">
    <form method="post" style="display:inline"><input type="hidden" name="a" value="move">
      <input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="dir" value="up">
      <input type="hidden" name="page_id" value="<?= $pageId ?>"><button class="btn sm grey">↑</button></form>
    <form method="post" style="display:inline"><input type="hidden" name="a" value="move">
      <input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="dir" value="down">
      <input type="hidden" name="page_id" value="<?= $pageId ?>"><button class="btn sm grey">↓</button></form>
    <form method="post" style="display:inline" onsubmit="return confirm('Удалить блок?')">
      <input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
      <input type="hidden" name="page_id" value="<?= $pageId ?>"><button class="btn sm red">Удалить</button></form>
  </div>
</div>
<?php endforeach; ?>

<?php if (!$blocks): ?><div class="panel"><p class="tag">На этой странице пока нет блоков.</p></div><?php endif; ?>
<?php foot();
