<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'error'=>'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
  exit;
}

function json_fail($message, $code=400) {
  http_response_code($code);
  echo json_encode(['ok'=>false,'error'=>$message], JSON_UNESCAPED_UNICODE);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) json_fail('Некорректные данные заказа.');
if (!empty($input['website'])) json_fail('Некорректный запрос.');

$name = trim((string)($input['name'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));
$email = trim((string)($input['email'] ?? ''));
$comment = trim((string)($input['comment'] ?? ''));
$items = $input['items'] ?? [];

if ($name === '' || mb_strlen($name) > 120) json_fail('Укажите имя.');
if ($phone === '' || mb_strlen($phone) > 40) json_fail('Укажите телефон.');
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) json_fail('Проверьте e-mail.');
if (!is_array($items) || !$items) json_fail('Корзина пуста.');
if (count($items) > 30) json_fail('Слишком много позиций.');

try {
  $pdo = db();
  $pdo->beginTransaction();
  $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(32) NOT NULL DEFAULT 'new',
    customer_name VARCHAR(120) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(160) NULL,
    comment TEXT NULL,
    total INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX(status), INDEX(created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    sku VARCHAR(128) NULL,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(100) NOT NULL,
    size VARCHAR(100) NOT NULL,
    price INT NOT NULL,
    qty INT NOT NULL,
    FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX(order_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

  $resolved = [];
  $total = 0;
  foreach ($items as $item) {
    $sku = trim((string)($item['sku'] ?? ''));
    $color = trim((string)($item['color'] ?? ''));
    $size = trim((string)($item['size'] ?? ''));
    $qty = (int)($item['qty'] ?? 0);
    if ($sku === '' || $color === '' || $size === '' || $qty < 1 || $qty > 20) json_fail('Проверьте состав заказа.');

    $st = $pdo->prepare("SELECT p.id,p.sku,p.name,p.price,v.stock FROM products p JOIN variants v ON v.product_id=p.id WHERE p.sku=? AND p.status='published' AND v.color=? AND v.size=? LIMIT 1 FOR UPDATE");
    $st->execute([$sku,$color,$size]);
    $v = $st->fetch();
    if (!$v) json_fail('Один из выбранных вариантов товара больше недоступен.');
    if ((int)$v['stock'] < $qty) json_fail('Недостаточно товара на складе: ' . $v['name'] . ', ' . $color . ', ' . $size . '.');

    $resolved[] = [$v,$color,$size,$qty];
    $total += (int)$v['price'] * $qty;
  }

  $st = $pdo->prepare('INSERT INTO orders (customer_name,phone,email,comment,total) VALUES (?,?,?,?,?)');
  $st->execute([$name,$phone,$email !== '' ? $email : null,$comment !== '' ? $comment : null,$total]);
  $orderId = (int)$pdo->lastInsertId();

  $ins = $pdo->prepare('INSERT INTO order_items (order_id,product_id,sku,name,color,size,price,qty) VALUES (?,?,?,?,?,?,?,?)');
  $dec = $pdo->prepare('UPDATE variants SET stock=stock-? WHERE product_id=? AND color=? AND size=? AND stock>=?');
  foreach ($resolved as [$v,$color,$size,$qty]) {
    $ins->execute([$orderId,(int)$v['id'],$v['sku'],$v['name'],$color,$size,(int)$v['price'],$qty]);
    $dec->execute([$qty,(int)$v['id'],$color,$size,$qty]);
    if ($dec->rowCount() !== 1) json_fail('Остаток изменился. Повторите заказ.');
  }
  $pdo->commit();

  echo json_encode(['ok'=>true,'order_id'=>$orderId,'total'=>$total], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  error_log($e->getMessage());
  json_fail('Не удалось оформить заказ. Попробуйте ещё раз.', 500);
}
