<?php
require 'inc.php';
need_auth();
$msg = '';
$keys = ['site_name'=>'Название сайта','phone'=>'Телефон','email'=>'E-mail','tg'=>'Telegram','address'=>'Адрес'];

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
    foreach ($keys as $k => $_) $st->execute([$k, $_POST[$k] ?? '']);
    $msg = 'Сохранено';
  }
}
$cur = [];
foreach (db()->query("SELECT `k`,`v` FROM settings") as $r) $cur[$r['k']] = $r['v'];

head('Настройки'); ?>
<h1>Настройки</h1>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>
<div class="panel">
  <form method="post">
    <?php foreach ($keys as $k => $lab): ?>
      <label><?= h($lab) ?></label><input name="<?= $k ?>" value="<?= h($cur[$k] ?? '') ?>">
    <?php endforeach; ?>
    <div style="margin-top:16px"><button class="btn">Сохранить</button></div>
  </form>
</div>
<div class="panel">
  <form method="post">
    <input type="hidden" name="a" value="pass">
    <label>Новый пароль администратора</label><input type="password" name="np" required>
    <div style="margin-top:16px"><button class="btn">Сменить пароль</button></div>
  </form>
</div>
<?php foot();
