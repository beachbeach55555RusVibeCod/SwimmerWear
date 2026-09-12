<?php
require dirname(__DIR__) . '/config.php';
if (is_auth()) go('index.php');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $st = db()->prepare("SELECT * FROM users WHERE login=? LIMIT 1");
  $st->execute([trim($_POST['login'] ?? '')]);
  $u = $st->fetch();
  if ($u && password_verify($_POST['pass'] ?? '', $u['pass_hash'])) {
    session_regenerate_id(true);
    $_SESSION['uid'] = $u['id'];
    $_SESSION['login'] = $u['login'];
    go('index.php');
  }
  $err = 'Неверный логин или пароль';
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1"><title>Вход — SWIMMER CMS</title>
<link rel="stylesheet" href="admin.css">
<style>body{display:flex;align-items:center;justify-content:center;min-height:100vh}
.card{background:#fff;border:1px solid var(--line);border-radius:10px;padding:32px;width:340px}
.card .brand{color:var(--ink);text-align:center;margin-bottom:20px}</style>
</head><body>
<form class="card" method="post">
  <div class="brand">SWIMMER<span>CMS</span></div>
  <?php if ($err): ?><div class="err"><?= h($err) ?></div><?php endif; ?>
  <label>Логин</label><input name="login" autofocus required autocomplete="username">
  <label>Пароль</label><input type="password" name="pass" required autocomplete="current-password">
  <button class="btn" style="width:100%;margin-top:18px">Войти</button>
</form></body></html>
