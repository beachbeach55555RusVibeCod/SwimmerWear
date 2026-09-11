<?php
require 'inc.php';
need_auth();
$msg = '';

/* ключ => [подпись, тип: line|area, подсказка] */
$fields = [
  'Сайт' => [
    'site_name' => ['Название бренда', 'line', ''],
    'slogan'    => ['Слоган в подвале', 'line', ''],
    'tagline'   => ['Строка внизу подвала', 'line', ''],
  ],
  'Контакты' => [
    'phone'   => ['Телефон', 'line', ''],
    'email'   => ['E-mail', 'line', ''],
    'tg'      => ['Telegram', 'line', 'Ссылка целиком'],
    'address' => ['Адрес', 'line', ''],
  ],
  'Карточка товара' => [
    'model_note'   => ['Подпись про модель', 'line', 'Например: рост 182 см, на фото размер L'],
    'product_tags' => ['Плашки характеристик', 'line', 'Через запятую'],
    'colors'       => ['Цвета и их коды', 'area', 'По строке на цвет: название|#HEX. Название должно совпадать с цветом варианта товара'],
  ],
  'Таблица размеров' => [
    'size_table' => ['Таблица', 'area', 'Колонки через |, первая строка — шапка'],
    'size_note'  => ['Примечание под таблицей', 'line', ''],
  ],
  'Окно «О бренде»' => [
    'brand_title' => ['Заголовок', 'line', ''],
    'brand_text'  => ['Текст', 'area', 'Абзацы — с новой строки'],
    'brand_image' => ['Фото внизу', 'line', '/uploads/… или https://…'],
  ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_POST['a'] ?? '') === 'pass') {
    $new = $_POST['np'] ?? '';
    if (strlen($new) >= 4) {
      db()->prepare("UPDATE users SET pass_hash=? WHERE id=?")
          ->execute([password_hash($new, PASSWORD_DEFAULT), (int)$_SESSION['uid']]);
      $msg = 'Пароль изменён';
    } else $msg = 'Пароль слишком короткий';
  } else {
    $st = db()->prepare("INSERT INTO settings (`k`,`v`) VALUES (?,?) ON DUPLICATE KEY UPDATE `v`=VALUES(`v`)");
    foreach ($fields as $group) foreach ($group as $k => $_) $st->execute([$k, trim($_POST[$k] ?? '')]);
    $msg = 'Сохранено';
  }
}

$cur = [];
foreach (db()->query("SELECT `k`,`v` FROM settings") as $r) $cur[$r['k']] = $r['v'];

head('Настройки'); ?>
<h1>Настройки</h1>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

<form method="post">
  <?php foreach ($fields as $group => $items): ?>
  <div class="panel">
    <b><?= h($group) ?></b>
    <?php foreach ($items as $k => $f): ?>
      <label><?= h($f[0]) ?></label>
      <?php if ($f[1] === 'area'): ?>
        <textarea name="<?= $k ?>"><?= h($cur[$k] ?? '') ?></textarea>
      <?php else: ?>
        <input name="<?= $k ?>" value="<?= h($cur[$k] ?? '') ?>">
      <?php endif; ?>
      <?php if ($f[2]): ?><p class="tag" style="margin-top:4px"><?= h($f[2]) ?></p><?php endif; ?>
    <?php endforeach; ?>
  </div>
  <?php endforeach; ?>
  <div class="panel"><button class="btn">Сохранить настройки</button></div>
</form>

<div class="panel">
  <form method="post">
    <input type="hidden" name="a" value="pass">
    <label>Новый пароль администратора</label><input type="password" name="np" required>
    <div style="margin-top:16px"><button class="btn">Сменить пароль</button></div>
  </form>
</div>
<?php foot();
