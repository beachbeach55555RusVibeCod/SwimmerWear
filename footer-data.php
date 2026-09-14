<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

$data = [
  'address' => trim((string)setting('address', '')),
  'phone' => trim((string)setting('phone', '')),
  'email' => trim((string)setting('email', '')),
  'socials' => [
    'telegram' => trim((string)setting('tg', '')),
    'vk' => trim((string)setting('social_vk', '')),
    'instagram' => trim((string)setting('social_instagram', '')),
    'youtube' => trim((string)setting('social_youtube', '')),
    'whatsapp' => trim((string)setting('social_whatsapp', '')),
  ],
];

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
