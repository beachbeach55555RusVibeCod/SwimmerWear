<?php
require 'inc.php';
need_auth();

$msg = '';
$fields = [
  'address' => ['Адрес', 'Например: Москва, ...'],
  'phone' => ['Телефон', 'Рабочий телефон'],
  'email' => ['Рабочий e-mail', 'На этот e-mail приходят заказы с сайта'],
  'tg' => ['Telegram', 'Полная ссылка, например https://t.me/...'],
  'social_vk' => ['ВКонтакте', 'Полная ссылка на страницу или сообщество'],
  'social_instagram' => ['Instagram', 'Полная ссылка на профиль'],
  'social_youtube' => ['YouTube', 'Полная ссылка на канал'],
  'social_whatsapp' => ['WhatsApp', 'Полная ссылка, например https://wa.me/...'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $st = db()->prepare("INSERT INTO settings (`k`,`v`) VALUES (?,?) ON DUPLICATE KEY UPDATE `v`=VALUES(`v`)");
  foreach ($fields as $key => $_) {
    $st->execute([$key, trim((string)($_POST[$key] ?? ''))]);
  }
  $msg = 'Контакты сохранены';
}

$cur = [];
foreach (db()->query("SELECT `k`,`v` FROM settings") as $r) $cur[$r['k']] = $r['v'];

head('Редактор контактов');
?>
<h1>Редактор контактов</h1>
<p class="tag" style="margin:-8px 0 22px">Контакты используются в окне «Контакты», в подвале сайта и для отправки уведомлений о заказах.</p>
<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

<form method="post">
  <div class="panel">
    <h2 style="margin-top:0">Контактные данные</h2>
    <?php foreach (['address','phone','email'] as $key): $f=$fields[$key]; ?>
      <label><?= h($f[0]) ?></label>
      <input name="<?= h($key) ?>" value="<?= h($cur[$key] ?? '') ?>" placeholder="<?= h($f[1]) ?>">
      <?php if ($key === 'email'): ?><p class="tag" style="margin-top:5px">Это рабочий e-mail: на него отправляются новые заказы.</p><?php endif; ?>
    <?php endforeach; ?>
  </div>

  <div class="panel">
    <h2 style="margin-top:0">Социальные сети</h2>
    <p class="tag">Заполняй полной ссылкой. После сохранения соответствующая иконка в футере станет кликабельной. Пустые соцсети в футере не показываются.</p>
    <?php foreach (['tg','social_vk','social_instagram','social_youtube','social_whatsapp'] as $key): $f=$fields[$key]; ?>
      <label><?= h($f[0]) ?></label>
      <input name="<?= h($key) ?>" value="<?= h($cur[$key] ?? '') ?>" placeholder="<?= h($f[1]) ?>">
    <?php endforeach; ?>
  </div>

  <div class="panel"><button class="btn" type="submit">Сохранить контакты</button></div>
</form>
<?php foot();
