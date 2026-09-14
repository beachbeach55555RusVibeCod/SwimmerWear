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

function order_fail($pdo, $message, $code=400) {
  if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
  json_fail($message, $code);
}

function send_order_email($orderId, $name, $phone, $email, $comment, $resolved, $total) {
  $to = trim((string)setting('email', ''));
  if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    error_log('Order #' . $orderId . ': work email is not configured');
    return false;
  }

  $lines = [];
  $lines[] = 'Новый заказ №' . $orderId;
  $lines[] = '';
  $lines[] = 'Клиент: ' . $name;
  $lines[] = 'Телефон: ' . $phone;
  if ($email !== '') $lines[] = 'E-mail: ' . $email;
  if ($comment !== '') {
    $lines[] = '';
    $lines[] = 'Комментарий:';
    $lines[] = $comment;
  }
  $lines[] = '';
  $lines[] = 'Состав заказа:';
  foreach ($resolved as [$v,$color,$size,$qty]) {
    $sum = (int)$v['price'] * $qty;
    $lines[] = '- ' . $v['name'] . ' | ' . $v['sku'] . ' | ' . $color . ' | ' . $size . ' | ' . $qty . ' шт. | ' . number_format($sum, 0, '', ' ') . ' ₽';
  }
  $lines[] = '';
  $lines[] = 'Итого: ' . number_format($total, 0, '', ' ') . ' ₽';
  $lines[] = 'Дата: ' . date('d.m.Y H:i');

  $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? 'odejdalike.ru'));
  $host = preg_replace('/:\d+$/', '', $host);
  if (!preg_match('/^[a-z0-9.-]+$/', $host)) $host = 'odejdalike.ru';
  $from = 'noreply@' . $host;
  $headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'From: SWIMMER <' . $from . '>'
  ];
  if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) $headers[] = 'Reply-To: ' . $email;

  $subjectText = 'SWIMMER — новый заказ №' . $orderId;
  $subject = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';
  $sent = @mail($to, $subject, implode("\r\n", $lines), implode("\r\n", $headers));
  if (!$sent) error_log('Order #' . $orderId . ': mail() returned false');
  return $sent;
}

function notify_order_channels($orderId, $name, $phone, $email, $comment, $resolved, $total) {
  return ['email_sent'=>send_order_email($orderId, $name, $phone, $email, $comment, $resolved, $total)];
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

  // DDL нельзя выполнять внутри транзакции MySQL: CREATE TABLE делает неявный COMMIT.
  // Раньше из-за этого заказ мог записаться, а затем commit() выдавал ошибку,
  // и покупатель видел «Не удалось оформить заказ».
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

  $pdo->beginTransaction();

  $resolved = [];
  $total = 0;
  foreach ($items as $item) {
    $productId = (int)($item['product_id'] ?? 0);
    $sku = trim((string)($item['sku'] ?? ''));
    $color = trim((string)($item['color'] ?? ''));
    $size = trim((string)($item['size'] ?? ''));
    $qty = (int)($item['qty'] ?? 0);
    if (($productId < 1 && $sku === '') || $color === '' || $size === '' || $qty < 1 || $qty > 20) {
      order_fail($pdo, 'Проверьте состав заказа.');
    }

    if ($productId > 0) {
      $st = $pdo->prepare("SELECT p.id,p.sku,p.name,p.price,v.id AS variant_id,v.stock
                           FROM products p
                           JOIN variants v ON v.product_id=p.id
                           WHERE p.id=? AND p.status='published'
                             AND LOWER(TRIM(v.color))=LOWER(TRIM(?))
                             AND UPPER(TRIM(v.size))=UPPER(TRIM(?))
                           LIMIT 1 FOR UPDATE");
      $st->execute([$productId,$color,$size]);
    } else {
      $st = $pdo->prepare("SELECT p.id,p.sku,p.name,p.price,v.id AS variant_id,v.stock
                           FROM products p
                           JOIN variants v ON v.product_id=p.id
                           WHERE p.sku=? AND p.status='published'
                             AND LOWER(TRIM(v.color))=LOWER(TRIM(?))
                             AND UPPER(TRIM(v.size))=UPPER(TRIM(?))
                           ORDER BY v.stock DESC, p.id DESC
                           LIMIT 1 FOR UPDATE");
      $st->execute([$sku,$color,$size]);
    }

    $v = $st->fetch();
    if (!$v) order_fail($pdo, 'Один из выбранных вариантов товара больше недоступен.');
    if ((int)$v['stock'] < $qty) {
      order_fail($pdo, 'Недостаточно товара на складе: ' . $v['name'] . ', ' . $color . ', ' . $size . '. Доступно: ' . (int)$v['stock'] . ' шт.');
    }

    $resolved[] = [$v,$color,$size,$qty];
    $total += (int)$v['price'] * $qty;
  }

  $st = $pdo->prepare('INSERT INTO orders (customer_name,phone,email,comment,total) VALUES (?,?,?,?,?)');
  $st->execute([$name,$phone,$email !== '' ? $email : null,$comment !== '' ? $comment : null,$total]);
  $orderId = (int)$pdo->lastInsertId();

  $ins = $pdo->prepare('INSERT INTO order_items (order_id,product_id,sku,name,color,size,price,qty) VALUES (?,?,?,?,?,?,?,?)');
  $dec = $pdo->prepare('UPDATE variants SET stock=stock-? WHERE id=? AND stock>=?');
  foreach ($resolved as [$v,$color,$size,$qty]) {
    $ins->execute([$orderId,(int)$v['id'],$v['sku'],$v['name'],$color,$size,(int)$v['price'],$qty]);
    $dec->execute([$qty,(int)$v['variant_id'],$qty]);
    if ($dec->rowCount() !== 1) order_fail($pdo, 'Остаток изменился. Повторите заказ.');
  }

  $pdo->commit();

  $notification = notify_order_channels($orderId, $name, $phone, $email, $comment, $resolved, $total);
  echo json_encode(['ok'=>true,'order_id'=>$orderId,'total'=>$total] + $notification, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
  error_log('ORDER ERROR: ' . $e->getMessage());
  json_fail('Не удалось оформить заказ. Попробуйте ещё раз.', 500);
}
