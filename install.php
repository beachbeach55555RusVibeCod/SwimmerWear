<?php
require __DIR__ . '/config.php';

$done = false; $err = null;
if (($_POST['go'] ?? '') === '1') {
  try {
    $pdo = db();
    $pdo->exec("SET NAMES utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      login VARCHAR(64) NOT NULL UNIQUE,
      pass_hash VARCHAR(255) NOT NULL,
      email VARCHAR(128) NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS media (
      id INT AUTO_INCREMENT PRIMARY KEY,
      file VARCHAR(255) NOT NULL,
      alt VARCHAR(255) NULL,
      mime VARCHAR(64) NULL,
      filesize INT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
      id INT AUTO_INCREMENT PRIMARY KEY,
      sku VARCHAR(64) NOT NULL UNIQUE,
      name VARCHAR(255) NOT NULL,
      price INT NOT NULL DEFAULT 0,
      old_price INT NULL,
      descr TEXT NULL,
      compose TEXT NULL,
      care TEXT NULL,
      kit TEXT NULL,
      delivery TEXT NULL,
      status VARCHAR(16) NOT NULL DEFAULT 'draft',
      sort INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS variants (
      id INT AUTO_INCREMENT PRIMARY KEY,
      product_id INT NOT NULL,
      color VARCHAR(64) NOT NULL,
      size VARCHAR(16) NOT NULL,
      stock INT NOT NULL DEFAULT 0,
      UNIQUE KEY uniq_variant (product_id, color, size)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS product_media (
      id INT AUTO_INCREMENT PRIMARY KEY,
      product_id INT NOT NULL,
      media_id INT NOT NULL,
      color VARCHAR(64) NULL,
      sort INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pages (
      id INT AUTO_INCREMENT PRIMARY KEY,
      slug VARCHAR(128) NOT NULL UNIQUE,
      title VARCHAR(255) NOT NULL,
      seo_title VARCHAR(255) NULL,
      seo_desc VARCHAR(512) NULL,
      status VARCHAR(16) NOT NULL DEFAULT 'draft',
      sort INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS blocks (
      id INT AUTO_INCREMENT PRIMARY KEY,
      page_id INT NOT NULL,
      type VARCHAR(32) NOT NULL,
      sort INT NOT NULL DEFAULT 0,
      data LONGTEXT NULL,
      visible TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS menu (
      id INT AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(128) NOT NULL,
      url VARCHAR(255) NOT NULL,
      target VARCHAR(16) NULL,
      place VARCHAR(16) NOT NULL DEFAULT 'header',
      sort INT NOT NULL DEFAULT 0,
      visible TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
      `k` VARCHAR(64) PRIMARY KEY,
      `v` TEXT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // admin
    $login = trim($_POST['login'] ?? 'admin');
    $pass  = $_POST['pass'] ?? 'admin';
    $st = $pdo->prepare("INSERT INTO users (login, pass_hash) VALUES (?,?)
                         ON DUPLICATE KEY UPDATE pass_hash = VALUES(pass_hash)");
    $st->execute([$login, password_hash($pass, PASSWORD_DEFAULT)]);

    // главная страница
    $pdo->prepare("INSERT IGNORE INTO pages (slug,title,seo_title,seo_desc,status,sort)
                   VALUES ('/','Главная','SWIMMER — речная парка','Речная парка SWIMMER','published',0)")->execute();
    $pid = $pdo->query("SELECT id FROM pages WHERE slug='/'")->fetchColumn();

    if (!$pdo->query("SELECT COUNT(*) FROM blocks WHERE page_id=" . (int)$pid)->fetchColumn()) {
      $blocks = [
        ['hero', ['title' => 'SWIMMER', 'subtitle' => 'Речная парка для тех, кто у воды', 'cta' => 'Смотреть модель']],
        ['text', ['title' => 'О парке', 'content' => "Мембрана, проклеенные швы, честная посадка.\nСделано для реки, а не для витрины."]],
        ['features', ['title' => 'Коротко о главном', 'items' => [
          ['t' => 'Водозащита', 'd' => '10 000 мм водного столба'],
          ['t' => 'Дыхание', 'd' => '5 000 г/м²/24ч'],
          ['t' => 'Швы', 'd' => 'Проклеены полностью'],
        ]]],
        ['products', ['title' => 'Модели']],
        ['faq', ['title' => 'Вопросы', 'items' => [
          ['q' => 'Как подобрать размер?', 'a' => 'Берите свой обычный — посадка прямая.'],
          ['q' => 'Есть доставка?', 'a' => 'Да, по России.'],
        ]]],
      ];
      $ins = $pdo->prepare("INSERT INTO blocks (page_id,type,sort,data,visible) VALUES (?,?,?,?,1)");
      foreach ($blocks as $i => $b) $ins->execute([$pid, $b[0], $i, json_encode($b[1], JSON_UNESCAPED_UNICODE)]);
    }

    // меню
    if (!$pdo->query("SELECT COUNT(*) FROM menu")->fetchColumn()) {
      $m = $pdo->prepare("INSERT INTO menu (title,url,place,sort) VALUES (?,?,?,?)");
      $m->execute(['Главная', '/', 'header', 0]);
      $m->execute(['Контакты', '/contacts', 'header', 1]);
      $m->execute(['Доставка', '/delivery', 'footer', 0]);
    }

    // настройки
    $s = $pdo->prepare("INSERT INTO settings (`k`,`v`) VALUES (?,?) ON DUPLICATE KEY UPDATE `v`=VALUES(`v`)");
    $s->execute(['site_name', 'SWIMMER']);
    $s->execute(['phone', '']);
    $s->execute(['email', '']);

    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);

    $done = true;
  } catch (Throwable $e) {
    $err = $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Установка SWIMMER CMS</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#EDEFEF;margin:0;padding:40px 16px;color:#191D1E}
.box{max-width:520px;margin:0 auto;background:#fff;padding:32px;border-radius:10px;border:1px solid #D6DADA}
h1{font-size:22px;margin:0 0 16px}
label{display:block;margin:14px 0 6px;font-size:13px;color:#575E43;font-weight:600}
input{width:100%;padding:10px;border:1px solid #D6DADA;border-radius:6px;font-size:14px;box-sizing:border-box}
button{margin-top:20px;width:100%;padding:12px;background:#575E43;color:#fff;border:0;border-radius:6px;font-weight:600;cursor:pointer}
.ok{background:#eef5ec;border:1px solid #b9d3b0;padding:14px;border-radius:6px}
.err{background:#fdecec;border:1px solid #f2b8b8;padding:14px;border-radius:6px;white-space:pre-wrap;font-size:13px}
code{background:#EDEFEF;padding:2px 5px;border-radius:4px}
</style></head><body>
<div class="box">
<h1>Установка SWIMMER CMS</h1>
<?php if ($done): ?>
  <div class="ok">
    <b>Готово.</b><br>Таблицы созданы, главная страница заполнена.<br><br>
    Админка: <a href="admin/">/admin/</a><br>
    Сайт: <a href="/">/</a><br><br>
    <b>Удали файл <code>install.php</code> с сервера.</b>
  </div>
<?php else: ?>
  <?php if ($err): ?><div class="err"><?= h($err) ?></div><?php endif; ?>
  <p style="font-size:14px;color:#7E8688">БД: <code><?= h(DB_NAME) ?></code> на <code><?= h(DB_HOST) ?></code></p>
  <form method="post">
    <input type="hidden" name="go" value="1">
    <label>Логин администратора</label>
    <input name="login" value="admin" required>
    <label>Пароль администратора</label>
    <input name="pass" value="admin" required>
    <button type="submit">Установить</button>
  </form>
<?php endif; ?>
</div></body></html>
