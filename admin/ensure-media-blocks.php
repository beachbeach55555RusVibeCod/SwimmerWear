<?php
// Гарантирует, что медиа-блоки существуют в БД и поэтому всегда видны в редакторе.
// Блоки создаются скрытыми: витрина продолжает использовать текущий runtime-рендер,
// а данные для него читаются через /media-block-data.php.

$homeId = (int)db()->query("SELECT id FROM pages WHERE slug='/' LIMIT 1")->fetchColumn();
if (!$homeId) return;

function ensure_media_block($pageId, $type, array $data) {
  $st = db()->prepare("SELECT id FROM blocks WHERE page_id=? AND type=? LIMIT 1");
  $st->execute([$pageId, $type]);
  if ($st->fetchColumn()) return;
  $max = (int)db()->query("SELECT COALESCE(MAX(sort),-1) FROM blocks WHERE page_id=".(int)$pageId)->fetchColumn();
  db()->prepare("INSERT INTO blocks (page_id,type,sort,data,visible) VALUES (?,?,?,?,0)")
    ->execute([$pageId, $type, $max + 1, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
}

ensure_media_block($homeId, 'scenarios', [
  'title'=>'Вода. Природа. Свобода.',
  'items'=>[
    ['src'=>'https://images.unsplash.com/photo-1699645257408-70f18e50991f?auto=format&fit=crop&q=82&w=1920&h=1080','label'=>'Вода','t'=>'Вода','text'=>'Длина закрывает поясницу в лодке, капюшон регулируется под ветер с воды.'],
    ['src'=>'https://images.unsplash.com/photo-1610817118922-a7374b775fd9?auto=format&fit=crop&q=82&w=1920&h=1080','label'=>'Природа','t'=>'Природа','text'=>'Плотная ткань 140 g/m не боится веток и мокрой травы.'],
    ['src'=>'https://images.unsplash.com/photo-1667331634686-313ec875d91e?auto=format&fit=crop&q=82&w=1920&h=1080','label'=>'Свобода','t'=>'Свобода','text'=>'Сложили в фирменный мешок, убрали в багажник — и парка едет с вами.'],
  ],
]);

ensure_media_block($homeId, 'build', [
  'title'=>'Продуманная конструкция',
  'text'=>"Свободный крой рассчитан на второй слой одежды. Удлинённая спинка, высокий воротник и продуманные детали помогают сохранять тепло и свободу движения у воды, в дороге и на природе.\nКлючевые зоны, которые первыми принимают на себя дождь, ветер и нагрузку, сделаны функциональными и простыми в использовании.",
  'details'=>[
    ['src'=>'https://images.unsplash.com/photo-1721745740020-ed1e8e0d4db8?auto=format&fit=crop&q=82&w=1200&h=1500','label'=>'Капюшон','t'=>'Капюшон','text'=>'Анатомический капюшон с регулировкой по объёму и глубине. Защищает от ветра и дождя, не перекрывая обзор.'],
    ['src'=>'https://images.unsplash.com/photo-1654719796836-62b889d4598d?auto=format&fit=crop&q=82&w=1200&h=1500','label'=>'Воротник','t'=>'Воротник','text'=>'Высокий воротник закрывает шею до подбородка. Внутренняя часть мягкая и комфортная при длительной носке.'],
    ['src'=>'https://images.unsplash.com/photo-1548883354-94bcfe321cbb?auto=format&fit=crop&q=82&w=1200&h=1500','label'=>'Манжеты','t'=>'Манжеты','text'=>'Регулируемые манжеты помогают закрыться от дождя и ветра и легко ослабляются, когда становится теплее.'],
    ['src'=>'https://images.unsplash.com/photo-1556098539-3019e1bdf05e?auto=format&fit=crop&q=82&w=1200&h=1500','label'=>'Карманы','t'=>'Карманы','text'=>'Вместительные внешние карманы и защищённый внутренний карман для телефона, документов и мелочей.'],
    ['src'=>'https://images.unsplash.com/photo-1727515546577-f7d82a47b51d?auto=format&fit=crop&q=82&w=1200&h=1500','label'=>'Молния','t'=>'Молния','text'=>'Двухзамковая молния под ветрозащитным клапаном. Нижний бегунок даёт больше свободы при ходьбе и посадке.'],
  ],
]);

ensure_media_block($homeId, 'kit', [
  'title'=>'В комплекте',
  'shots'=>[
    'https://images.unsplash.com/photo-1603252109303-2751441dd157?auto=format&fit=crop&q=82&w=1000&h=1000',
    'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&q=82&w=900&h=1100',
    'https://images.unsplash.com/photo-1605733160314-4fc7dac4bb16?auto=format&fit=crop&q=82&w=900&h=1100',
  ],
  'shot_labels'=>['Парка','Мешок для хранения','Брендированный зип-пакет'],
  'items'=>['Парка','Фирменный непромокаемый прочный мешок для хранения','Брендированный зип-пакет'],
]);
