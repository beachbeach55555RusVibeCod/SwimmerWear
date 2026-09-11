<?php
require 'inc.php';
require dirname(__DIR__) . '/inc/blocks.php';
need_auth();

$types = [
  'hero'      => 'Обложка (слайдер + характеристики)',
  'products'  => 'Товары (рельс карточек)',
  'scenarios' => 'Сценарии (слайдер на пол-экрана)',
  'tech'      => 'Технологии (схема мембраны + список)',
  'build'     => 'Конструкция (рельс деталей)',
  'kit'       => 'Комплектация',
  'faq'       => 'Вопросы и ответы',
  'reviews'   => 'Отзывы',
  'text'      => 'Текстовый блок',
];

/** Собирает массив строк из полей вида name[0], name[1]… */
function rows($key, $fields) {
  $out = [];
  $src = $_POST[$key] ?? [];
  $first = $fields[0];
  foreach (($src[$first] ?? []) as $i => $_) {
    $row = [];
    foreach ($fields as $f) $row[$f] = trim($src[$f][$i] ?? '');
    $out[] = $row;
  }
  return $out;
}
/** Отбрасывает строки, у которых пустое ключевое поле. */
function keep($rows, $key) {
  return array_values(array_filter($rows, function ($r) use ($key) { return trim($r[$key] ?? '') !== ''; }));
}
/** Непустые строки из textarea. */
function lines($v) {
  return array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)$v)), 'strlen'));
}

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
    $type = $_POST['type'];
    $d = [];

    if ($type === 'hero') {
      $d = ['t1'=>trim($_POST['t1']), 'h1'=>trim($_POST['h1']), 't2'=>trim($_POST['t2']), 'cta'=>trim($_POST['cta'])];
      $d['slides'] = keep(rows('sl', ['src','label']), 'src');
      $d['specs']  = keep(rows('sp', ['icon','b','span']), 'b');
    }
    if ($type === 'products') $d = ['title'=>trim($_POST['title']), 'lede'=>trim($_POST['lede'])];
    if ($type === 'scenarios') {
      $d = ['title'=>trim($_POST['title']), 'items'=>keep(rows('it', ['src','label','t','text']), 't')];
    }
    if ($type === 'tech') {
      $d = ['title'=>trim($_POST['title']), 'lede'=>trim($_POST['lede']),
            'items'=>keep(rows('it', ['b','p']), 'b')];
    }
    if ($type === 'build') {
      $d = ['title'=>trim($_POST['title']), 'text'=>trim($_POST['text']),
            'details'=>keep(rows('it', ['src','label','t','text']), 't')];
    }
    if ($type === 'kit') {
      $d = ['title'=>trim($_POST['title']),
            'shots'=>array_slice(array_map('trim', $_POST['shots'] ?? []), 0, 3),
            'items'=>lines($_POST['items'] ?? '')];
    }
    if ($type === 'faq') {
      $d = ['title'=>trim($_POST['title']), 'items'=>keep(rows('it', ['q','a']), 'q')];
    }
    if ($type === 'reviews') {
      $d = ['title'=>trim($_POST['title']), 'items'=>lines($_POST['items'] ?? '')];
    }
    if ($type === 'text') {
      $d = ['title'=>trim($_POST['title']), 'content'=>trim($_POST['content'])];
    }

    db()->prepare("UPDATE blocks SET data=?, visible=? WHERE id=?")
        ->execute([json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), isset($_POST['visible']) ? 1 : 0, (int)$_POST['id']]);
    $msg = 'Блок сохранён';
  }

  if ($a === 'del') { db()->prepare("DELETE FROM blocks WHERE id=?")->execute([(int)$_POST['id']]); $msg = 'Блок удалён'; }

  if ($a === 'move') {
    $id = (int)$_POST['id'];
    $st = db()->prepare("SELECT * FROM blocks WHERE id=?"); $st->execute([$id]); $cur = $st->fetch();
    if ($cur) {
      $up = $_POST['dir'] === 'up';
      $st = db()->prepare("SELECT * FROM blocks WHERE page_id=? AND sort " . ($up ? '<' : '>') . " ? ORDER BY sort " . ($up ? 'DESC' : 'ASC') . " LIMIT 1");
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

/** Поле «медиа»: ссылка на файл или пусто → заштрихованный плейсхолдер. */
function media_fields($key, $i, $row) { ?>
  <div style="flex:2"><label>Ссылка на фото или видео</label>
    <input name="<?= $key ?>[src][<?= $i ?>]" value="<?= h($row['src'] ?? '') ?>" placeholder="/uploads/… или https://…"></div>
  <div style="flex:1"><label>Подпись (и текст заглушки)</label>
    <input name="<?= $key ?>[label][<?= $i ?>]" value="<?= h($row['label'] ?? '') ?>"></div>
<?php }

head('Блоки'); ?>
<h1>Блоки страницы</h1>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

<div class="panel row">
  <form method="get" class="row" style="flex:1">
    <div style="flex:1"><label>Страница</label>
      <select name="page" onchange="this.form.submit()">
        <?php foreach ($pages as $p): ?>
          <option value="<?= (int)$p['id'] ?>" <?= $p['id'] == $pageId ? 'selected' : '' ?>><?= h($p['title']) ?> (<?= h($p['slug']) ?>)</option>
        <?php endforeach; ?>
      </select></div>
  </form>
  <form method="post" class="row">
    <input type="hidden" name="a" value="add"><input type="hidden" name="page_id" value="<?= $pageId ?>">
    <div><label>Добавить блок</label>
      <select name="type"><?php foreach ($types as $k => $v): ?><option value="<?= $k ?>"><?= h($v) ?></option><?php endforeach; ?></select></div>
    <button class="btn" style="align-self:end">+ Добавить</button>
  </form>
</div>

<?php foreach ($blocks as $n => $b):
  $d = json_decode($b['data'] ?? '{}', true) ?: [];
  $t = $b['type']; ?>
<div class="panel">
  <form method="post">
    <input type="hidden" name="a" value="save">
    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
    <input type="hidden" name="type" value="<?= h($t) ?>">
    <input type="hidden" name="page_id" value="<?= $pageId ?>">

    <div class="row" style="margin-bottom:6px">
      <b><?= h($types[$t] ?? $t) ?></b>
      <span class="tag">#<?= $n + 1 ?></span>
      <label class="row" style="margin:0;gap:6px;font-weight:400">
        <input type="checkbox" name="visible" style="width:auto" <?= $b['visible'] ? 'checked' : '' ?>> показывать</label>
    </div>

    <?php if ($t === 'hero'): ?>
      <label>Надзаголовок</label><input name="t1" value="<?= h($d['t1'] ?? '') ?>">
      <label>Заголовок (перенос строки = новая строка)</label>
      <textarea name="h1" style="min-height:60px"><?= h($d['h1'] ?? '') ?></textarea>
      <label>Подзаголовок</label><input name="t2" value="<?= h($d['t2'] ?? '') ?>">
      <label>Текст кнопки</label><input name="cta" value="<?= h($d['cta'] ?? '') ?>">
      <p class="tag" style="margin-top:16px">Слайды обложки — .mp4 распознаётся как видео</p>
      <?php for ($i = 0; $i < 4; $i++): $r = $d['slides'][$i] ?? []; ?>
        <div class="row"><?php media_fields('sl', $i, $r); ?></div>
      <?php endfor; ?>
      <p class="tag" style="margin-top:16px">Характеристики под заголовком</p>
      <?php for ($i = 0; $i < 4; $i++): $r = $d['specs'][$i] ?? []; ?>
        <div class="row">
          <div style="width:150px"><label>Иконка</label>
            <select name="sp[icon][<?= $i ?>]">
              <?php foreach (spec_icon_list() as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($r['icon'] ?? '') === $k ? 'selected' : '' ?>><?= h($v) ?></option>
              <?php endforeach; ?>
            </select></div>
          <div style="flex:1"><label>Значение</label><input name="sp[b][<?= $i ?>]" value="<?= h($r['b'] ?? '') ?>"></div>
          <div style="flex:2"><label>Пояснение</label><input name="sp[span][<?= $i ?>]" value="<?= h($r['span'] ?? '') ?>"></div>
        </div>
      <?php endfor; ?>

    <?php elseif ($t === 'products'): ?>
      <label>Заголовок (перенос строки = новая строка)</label>
      <textarea name="title" style="min-height:60px"><?= h($d['title'] ?? '') ?></textarea>
      <label>Подзаголовок</label><input name="lede" value="<?= h($d['lede'] ?? '') ?>">
      <p class="tag">Карточки берутся из раздела «Товары» — публикуются те, у кого статус «онлайн»</p>

    <?php elseif ($t === 'scenarios'): ?>
      <label>Заголовок</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <?php for ($i = 0; $i < 4; $i++): $r = $d['items'][$i] ?? []; ?>
        <div class="row" style="border-top:1px solid var(--line);padding-top:6px">
          <?php media_fields('it', $i, $r); ?>
        </div>
        <div class="row">
          <div style="flex:1"><label>Название сценария</label><input name="it[t][<?= $i ?>]" value="<?= h($r['t'] ?? '') ?>"></div>
          <div style="flex:3"><label>Текст</label><input name="it[text][<?= $i ?>]" value="<?= h($r['text'] ?? '') ?>"></div>
        </div>
      <?php endfor; ?>

    <?php elseif ($t === 'tech'): ?>
      <label>Заголовок</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <label>Подзаголовок</label><input name="lede" value="<?= h($d['lede'] ?? '') ?>">
      <p class="tag" style="margin-top:16px">Схема мембраны рисуется автоматически. Ниже — список справа от неё.</p>
      <?php for ($i = 0; $i < 6; $i++): $r = $d['items'][$i] ?? []; ?>
        <div class="row">
          <div style="flex:1"><label>Название</label><input name="it[b][<?= $i ?>]" value="<?= h($r['b'] ?? '') ?>"></div>
          <div style="flex:3"><label>Описание</label><input name="it[p][<?= $i ?>]" value="<?= h($r['p'] ?? '') ?>"></div>
        </div>
      <?php endfor; ?>

    <?php elseif ($t === 'build'): ?>
      <label>Заголовок</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <label>Текст (абзацы через пустую строку)</label><textarea name="text"><?= h($d['text'] ?? '') ?></textarea>
      <p class="tag" style="margin-top:16px">Детали конструкции — карточки в рельсе справа</p>
      <?php for ($i = 0; $i < 6; $i++): $r = $d['details'][$i] ?? []; ?>
        <div class="row" style="border-top:1px solid var(--line);padding-top:6px">
          <?php media_fields('it', $i, $r); ?>
        </div>
        <div class="row">
          <div style="flex:1"><label>Название</label><input name="it[t][<?= $i ?>]" value="<?= h($r['t'] ?? '') ?>"></div>
          <div style="flex:3"><label>Описание</label><input name="it[text][<?= $i ?>]" value="<?= h($r['text'] ?? '') ?>"></div>
        </div>
      <?php endfor; ?>

    <?php elseif ($t === 'kit'): ?>
      <label>Заголовок</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <div class="row">
        <?php for ($i = 0; $i < 3; $i++): ?>
          <div style="flex:1"><label>Фото <?= $i + 1 ?></label>
            <input name="shots[<?= $i ?>]" value="<?= h($d['shots'][$i] ?? '') ?>" placeholder="/uploads/… или https://…"></div>
        <?php endfor; ?>
      </div>
      <label>Позиции комплекта (по одной в строке, нумеруются сами)</label>
      <textarea name="items"><?= h(implode("\n", $d['items'] ?? [])) ?></textarea>

    <?php elseif ($t === 'faq'): ?>
      <label>Заголовок</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <?php for ($i = 0; $i < 8; $i++): $r = $d['items'][$i] ?? []; ?>
        <label>Вопрос <?= $i + 1 ?></label><input name="it[q][<?= $i ?>]" value="<?= h($r['q'] ?? '') ?>">
        <textarea name="it[a][<?= $i ?>]" style="min-height:60px" placeholder="Ответ"><?= h($r['a'] ?? '') ?></textarea>
      <?php endfor; ?>

    <?php elseif ($t === 'reviews'): ?>
      <label>Заголовок</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <label>Отзывы — по одному в строке</label>
      <textarea name="items" style="min-height:140px"><?= h(implode("\n", $d['items'] ?? [])) ?></textarea>

    <?php elseif ($t === 'text'): ?>
      <label>Заголовок</label><input name="title" value="<?= h($d['title'] ?? '') ?>">
      <label>Текст (абзацы через пустую строку)</label><textarea name="content"><?= h($d['content'] ?? '') ?></textarea>
    <?php endif; ?>

    <div class="row" style="margin-top:16px"><button class="btn">Сохранить</button></div>
  </form>

  <div class="row" style="margin-top:10px">
    <form method="post" style="display:inline"><input type="hidden" name="a" value="move">
      <input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="dir" value="up">
      <input type="hidden" name="page_id" value="<?= $pageId ?>"><button class="btn sm grey">&uarr;</button></form>
    <form method="post" style="display:inline"><input type="hidden" name="a" value="move">
      <input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="dir" value="down">
      <input type="hidden" name="page_id" value="<?= $pageId ?>"><button class="btn sm grey">&darr;</button></form>
    <form method="post" style="display:inline" onsubmit="return confirm('Удалить блок?')">
      <input type="hidden" name="a" value="del"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
      <input type="hidden" name="page_id" value="<?= $pageId ?>"><button class="btn sm red">Удалить</button></form>
  </div>
</div>
<?php endforeach; ?>

<?php if (!$blocks): ?><div class="panel"><p class="tag">На этой странице пока нет блоков.</p></div><?php endif; ?>
<?php foot();
