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

    /* ---------- контент макета ---------- */
    $U = function ($id, $w, $h) {
      return 'https://images.unsplash.com/photo-' . $id . '?auto=format&fit=crop&q=70&w=' . $w . '&h=' . $h;
    };

    $pdo->prepare("INSERT IGNORE INTO pages (slug,title,seo_title,seo_desc,status,sort)
                   VALUES ('/','Главная','SWIMMER — Речная парка',
                   'Тёплая мембранная парка-плед для дождя, ветра и холодной погоды. Надёжные вещи для отдыха у воды.',
                   'published',0)")->execute();
    $pid = (int)$pdo->query("SELECT id FROM pages WHERE slug='/'")->fetchColumn();

    if (!$pdo->query("SELECT COUNT(*) FROM blocks WHERE page_id=$pid")->fetchColumn()) {
      $blocks = [

        ['hero', [
          't1'  => 'Надёжные вещи для отдыха у воды',
          'h1'  => "Речная\nпарка",
          't2'  => 'Тёплая мембранная парка-плед для дождя, ветра и холодной погоды.',
          'cta' => 'ВЫБРАТЬ',
          'slides' => [
            ['src' => 'https://assets.mixkit.co/videos/15337/15337-360.mp4', 'label' => 'видео — река'],
            ['src' => $U('1660570154241-06a6b165f9ec', 1920, 1080), 'label' => 'Берег и лодка'],
            ['src' => $U('1559022488-570ad0c1e43c', 1920, 1080),    'label' => 'Дождь над водой'],
          ],
          'specs' => [
            ['icon'=>'drop',   'b'=>'10K / 10K', 'span'=>'водостойкость снаружи, отведение пара изнутри'],
            ['icon'=>'fabric', 'b'=>'140 g/m',   'span'=>'прочная плотная ткань'],
            ['icon'=>'dwr',    'b'=>'DWR',       'span'=>'пропитка от дождя и снега'],
            ['icon'=>'fleece', 'b'=>'Wellsoft',  'span'=>'подкладка мягкая, как плед'],
          ],
        ]],

        ['products', [
          'title' => "Надёжно снаружи.\nУютно внутри.",
          'lede'  => 'Одна модель в трёх цветах. Нажмите на карточку, чтобы выбрать размер и посмотреть детали.',
        ]],

        ['scenarios', [
          'title' => 'Вода. Природа. Свобода.',
          'items' => [
            ['src'=>$U('1699645257408-70f18e50991f',1920,1080), 'label'=>'Сплав по реке', 't'=>'Вода',
             'text'=>'Длина закрывает поясницу в лодке, капюшон регулируется под ветер с воды.'],
            ['src'=>$U('1610817118922-a7374b775fd9',1920,1080), 'label'=>'Лес и лагерь', 't'=>'Природа',
             'text'=>'Плотная ткань 140 g/m не боится веток и мокрой травы.'],
            ['src'=>$U('1667331634686-313ec875d91e',1920,1080), 'label'=>'Дорога', 't'=>'Свобода',
             'text'=>'Сложили в фирменный мешок, убрали в багажник — и парка едет с вами.'],
          ],
        ]],

        ['tech', [
          'title' => 'Технологии и материалы',
          'lede'  => 'Трёхслойная мембрана работает в обе стороны: не пропускает воду снаружи и выводит пар изнутри.',
          'items' => [
            ['b'=>'3L MEMBRANE', 'p'=>'Три слоя соединены в одно полотно: верх, мембрана и подкладка не расходятся при движении.'],
            ['b'=>'10K / 10K',   'p'=>'Столб воды 10 000 мм и такая же паропроницаемость — держит ливень и не превращается в парник.'],
            ['b'=>'140 G/M²',    'p'=>'Плотная ткань верха: не рвётся о ветки и не парусит на ветру.'],
            ['b'=>'DWR',         'p'=>'Пропитка заставляет воду скатываться каплями. Обновляется стиркой со спецсредством.'],
            ['b'=>'WELLSOFT',    'p'=>'Подкладка с мягким ворсом — по ощущению ближе к пледу, чем к куртке.'],
          ],
        ]],

        ['build', [
          'title' => 'Продуманная конструкция',
          'text'  => "Свободный крой поверх второго слоя, удлинённая спинка и высокий воротник. Парка садится так, чтобы в ней можно было грести, идти и сидеть у костра, не поправляя её каждые пять минут.\nВсе точки, которые первыми промокают и первыми изнашиваются, усилены и продублированы.",
          'details' => [
            ['src'=>$U('1721745740020-ed1e8e0d4db8',800,1000), 'label'=>'капюшон',  't'=>'Капюшон',  'text'=>'Анатомический, с регулировкой по объёму и глубине. Не падает на глаза.'],
            ['src'=>$U('1654719796836-62b889d4598d',800,1000), 'label'=>'воротник', 't'=>'Воротник', 'text'=>'Высокий, закрывает шею до подбородка. Изнутри подбит мягким ворсом.'],
            ['src'=>$U('1548883354-94bcfe321cbb',800,1000),    'label'=>'манжеты',  't'=>'Манжеты',  'text'=>'Регулируемые: затягиваются в дождь и распускаются, когда жарко.'],
            ['src'=>$U('1556098539-3019e1bdf05e',800,1000),    'label'=>'карманы',  't'=>'Карманы',  'text'=>'Два вместительных внешних и внутренний водонепроницаемый для документов.'],
            ['src'=>$U('1727515546577-f7d82a47b51d',800,1000), 'label'=>'молния',   't'=>'Молния',   'text'=>'Двухзамковая, под ветрозащитным клапаном. Открывается снизу при ходьбе.'],
          ],
        ]],

        ['kit', [
          'title' => 'В комплекте',
          'shots' => [
            $U('1548883354-94bcfe321cbb', 900, 1200),
            $U('1727515546577-f7d82a47b51d', 900, 1200),
            $U('1578948856697-db91d246b7b1', 900, 1200),
          ],
          'items' => ['Парка', 'Фирменный непромокаемый прочный мешок для хранения', 'Брендированный зип-пакет'],
        ]],

        ['faq', [
          'title' => 'Часто задаваемые вопросы',
          'items' => [
            ['q'=>'Можно ли носить парку зимой? До какой температуры она рассчитана?',
             'a'=>'Комфортный диапазон — от +10 до −10 °C. Со вторым слоем (флис или тонкий утеплённый жилет) парка держит и более низкую температуру: подкладка Wellsoft сохраняет тепло, а мембрана не даёт ветру продувать.'],
            ['q'=>'Она полностью непромокаемая или водоотталкивающая?',
             'a'=>'Водонепроницаемая по мембране: столб воды 10 000 мм, швы проклеены. Сверху ткань обработана DWR — вода скатывается каплями, не впитываясь. В долгий ливень парка остаётся сухой изнутри.'],
            ['q'=>'Что надевать под неё?',
             'a'=>'Крой свободный, рассчитан на второй слой. Осенью хватит футболки и лонгслива, ближе к нулю — флис или тонкая утеплённая куртка. Специально брать размер больше не нужно.'],
            ['q'=>'Можно ли стирать в машинке?',
             'a'=>'Да, бережная стирка при 30–40 °C. Без отбеливателя, без машинной сушки, не гладить и не отдавать в химчистку.'],
            ['q'=>'Нужно ли ухаживать за мембраной?',
             'a'=>'Мембрана ухода не требует, а вот DWR со временем стирается. Когда вода перестанет скатываться каплями — постирайте парку со средством для мембранных тканей, пропитка восстановится.'],
            ['q'=>'Сколько длится доставка?',
             'a'=>'По России — 2–5 дней курьером или в пункт выдачи. Отправляем на следующий рабочий день после заказа, трек-номер приходит на почту.'],
          ],
        ]],

        ['reviews', [
          'title' => 'Отзывы',
          'items' => [
            'Шли под дождём четыре часа по реке. Внутри сухо, спина не мокрая — это главное, что я хотел проверить.',
            'Подкладка правда как плед. Сидели вечером у воды, ветер сильный, а в парке было тепло без второго слоя.',
            'Взял хаки, размер L при росте 182. Свободно, но не мешком. Карманы глубокие, телефон во внутреннем не намок.',
          ],
        ]],
      ];

      $ins = $pdo->prepare("INSERT INTO blocks (page_id,type,sort,data,visible) VALUES (?,?,?,?,1)");
      foreach ($blocks as $i => $b) $ins->execute([$pid, $b[0], $i, json_encode($b[1], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    /* ---------- товар, варианты, фото ---------- */
    if (!$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn()) {
      $pdo->prepare("INSERT INTO products (sku,name,price,descr,compose,care,kit,delivery,status,sort)
                     VALUES (?,?,?,?,?,?,?,?, 'published', 0)")->execute([
        'SW-RP', 'Речная парка', 24990,
        "Тёплая мембранная парка для сырой, ветреной и холодной погоды. Создана для отдыха у воды, города, леса и дороги.\nАнатомический регулируемый капюшон и высокий воротник защищают от ветра и осадков. Двухзамковая молния и регулируемые манжеты позволяют адаптировать посадку под движение и погоду. Вместительные внешние карманы рассчитаны на необходимые вещи. Внутренний водонепроницаемый карман — для документов и телефона.\nСвободный крой позволяет носить парку поверх второго слоя.\nНадёжно снаружи. Уютно внутри.",
        "Верх: 100% полиэстер\nМембрана: ТПУ\nПодкладка: 100% полиэстер",
        "Бережная стирка при 30–40 °C\nНе отбеливать\nБез машинной сушки\nНе гладить\nНе подвергать химчистке",
        "Парка + фирменный непромокаемый мешок для хранения + брендированный зип-пакет",
        "По России — 2–5 дней курьером или в пункт выдачи.\nОтправка на следующий рабочий день, трек-номер приходит на почту.",
      ]);
      $prodId = (int)$pdo->lastInsertId();

      $vIns = $pdo->prepare("INSERT IGNORE INTO variants (product_id,color,size,stock) VALUES (?,?,?,?)");
      foreach (['хаки', 'чёрный', 'синий'] as $color)
        foreach (['XS','S','M','L','XL','XXL','Универсальный'] as $size)
          $vIns->execute([$prodId, $color, $size, 5]);

      $shots = [
        'хаки'   => ['1721745740020-ed1e8e0d4db8','1548883354-94bcfe321cbb','1578948856697-db91d246b7b1','1521223890158-f9f7c3d5d504'],
        'чёрный' => ['1654719796836-62b889d4598d','1556098539-3019e1bdf05e','1727515546577-f7d82a47b51d','1548126032-079a0fb0099d'],
        'синий'  => ['1548126032-079a0fb0099d','1567955465163-c355df3b74bf','1655972670403-243839675e06','1551537482-f2075a1d41f2'],
      ];
      $mIns  = $pdo->prepare("INSERT INTO media (file,alt,mime) VALUES (?,?,'image/jpeg')");
      $pmIns = $pdo->prepare("INSERT INTO product_media (product_id,media_id,color,sort) VALUES (?,?,?,?)");
      foreach ($shots as $color => $ids) {
        foreach ($ids as $n => $id) {
          $mIns->execute([$U($id, 900, 1200), 'Речная парка ' . $color]);
          $pmIns->execute([$prodId, (int)$pdo->lastInsertId(), $color, $n]);
        }
      }
    }

    /* ---------- меню ---------- */
    if (!$pdo->query("SELECT COUNT(*) FROM menu")->fetchColumn()) {
      $m = $pdo->prepare("INSERT INTO menu (title,url,place,sort) VALUES (?,?,?,?)");
      $m->execute(['Каталог',    '#product',  'header', 0]);
      $m->execute(['О бренде',   '#brand',    'header', 1]);
      $m->execute(['Контакты',   '#contacts', 'header', 2]);
      $m->execute(['Каталог',    '#product',  'footer', 0]);
      $m->execute(['О бренде',   '#brand',    'footer', 1]);
      $m->execute(['Контакты',   '#contacts', 'footer', 2]);
    }

    /* ---------- настройки ---------- */
    $s = $pdo->prepare("INSERT INTO settings (`k`,`v`) VALUES (?,?) ON DUPLICATE KEY UPDATE `v`=VALUES(`v`)");
    $defaults = [
      'site_name'    => 'SWIMMER',
      'slogan'       => 'Увидимся у воды.',
      'tagline'      => 'Надёжные вещи для отдыха у воды',
      'phone'        => '',
      'email'        => '',
      'tg'           => '',
      'address'      => '',
      'model_note'   => 'Модель: рост 182 см, на фото размер L',
      'product_tags' => '3L MEMBRANE, 10K / 10K, 140 G/M², DWR',
      'colors'       => "хаки|#575E43\nчёрный|#22262A\nсиний|#41546B",
      'size_table'   => "Размер|Рост, см|Грудь, см|Талия, см|Бёдра, см\nXS|160–168|84–88|66–70|92–96\nS|166–174|88–92|70–74|96–100\nM|172–180|92–98|74–80|100–104\nL|178–186|98–104|80–86|104–110\nXL|184–192|104–110|86–94|110–116\nXXL|188–196|110–118|94–102|116–122\nУниверсальный|170–190|92–110|74–94|100–116",
      'size_note'    => 'Мерки сняты по телу. Крой свободный — парка рассчитана на второй слой, брать размер больше не нужно.',
      'brand_title'  => 'Всё началось у реки',
      'brand_text'   => "SWIMMER придумали люди, которые проводят у воды больше времени, чем на суше. Сплавы, утренняя рыбалка, поздние возвращения по холодной воде — вещи для этого всегда приходилось собирать из разных категорий: что-то туристическое, что-то городское, и ничего до конца подходящего.\nМы делаем одежду для одного конкретного сценария: отдых у воды в плохую погоду. Отсюда мембрана вместо модной ткани, длина, закрывающая поясницу в лодке, и подкладка, в которой не холодно сидеть на берегу вечером.\nШьём в России небольшими партиями. Каждую модель носим сами минимум сезон, прежде чем поставить в продажу — поэтому линейка растёт медленно.",
      'brand_image'  => 'https://images.unsplash.com/photo-1465189684280-6a8fa9b19a7a?auto=format&fit=crop&q=70&w=1600&h=700',
    ];
    foreach ($defaults as $k => $v) $s->execute([$k, $v]);

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
