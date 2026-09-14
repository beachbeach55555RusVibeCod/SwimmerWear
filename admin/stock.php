<?php
require 'inc.php';
need_auth();

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['a'] ?? '') === 'save_stock') {
  $stock = $_POST['stock'] ?? [];
  if (!is_array($stock)) $stock = [];
  try {
    $pdo = db();
    $pdo->beginTransaction();
    $st = $pdo->prepare('UPDATE variants SET stock=? WHERE id=?');
    foreach ($stock as $variantId => $qty) {
      $variantId = (int)$variantId;
      if ($variantId < 1) continue;
      $qty = max(0, min(99999, (int)$qty));
      $st->execute([$qty, $variantId]);
    }
    $pdo->commit();
    $msg = 'Остатки сохранены';
  } catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    $err = 'Не удалось сохранить остатки';
    error_log($e->getMessage());
  }
}

$rows = db()->query("SELECT p.id product_id,p.name,p.sku,p.status,v.id variant_id,v.color,v.size,v.stock
                     FROM products p
                     LEFT JOIN variants v ON v.product_id=p.id
                     ORDER BY p.sort,p.id,v.color,v.id")->fetchAll();

$products = [];
foreach ($rows as $r) {
  $pid = (int)$r['product_id'];
  if (!isset($products[$pid])) {
    $products[$pid] = [
      'id'=>$pid,
      'name'=>$r['name'],
      'sku'=>$r['sku'],
      'status'=>$r['status'],
      'variants'=>[],
    ];
  }
  if (!empty($r['variant_id'])) $products[$pid]['variants'][] = $r;
}

function stock_state($n) {
  $n = (int)$n;
  if ($n <= 0) return ['Нет в наличии','out'];
  if ($n <= 3) return ['Мало','low'];
  return ['В наличии','ok'];
}

head('Склад'); ?>
<style>
.stock-top{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:20px}
.stock-help{color:var(--steel);font-size:13px;max-width:720px;line-height:1.5}
.stock-product{padding:0;overflow:hidden}
.stock-product__head{display:flex;justify-content:space-between;gap:20px;align-items:center;padding:18px 20px;border-bottom:1px solid var(--line)}
.stock-product__title{font-size:17px;font-weight:700}.stock-product__meta{font-size:12px;color:var(--steel);margin-top:4px}
.stock-total{font-size:13px;color:var(--steel);text-align:right}.stock-total b{display:block;color:var(--ink);font-size:20px}
.stock-table td,.stock-table th{vertical-align:middle}.stock-color{font-weight:600}
.stock-control{display:flex;align-items:center;gap:6px;width:180px}.stock-control input{width:82px;text-align:center;font-variant-numeric:tabular-nums}
.stock-step{width:34px;height:34px;border:1px solid var(--line);border-radius:6px;background:#fff;font-size:18px;line-height:1}.stock-step:hover{border-color:var(--ink)}
.stock-badge{display:inline-flex;padding:4px 8px;border-radius:20px;font-size:11px;font-weight:700}.stock-badge.ok{background:#e6f0e2;color:#4a6b3a}.stock-badge.low{background:#fff3d8;color:#806016}.stock-badge.out{background:#fdecec;color:#963d3d}
.stock-savebar{position:sticky;bottom:0;z-index:10;background:rgba(237,239,239,.96);backdrop-filter:blur(8px);border-top:1px solid var(--line);padding:14px 0;margin-top:22px}
.stock-savebar .row{justify-content:flex-end}
.stock-empty{padding:20px;color:var(--steel);font-size:13px}
@media(max-width:760px){.stock-product__head{align-items:flex-start}.stock-table thead{display:none}.stock-table,.stock-table tbody,.stock-table tr,.stock-table td{display:block;width:100%}.stock-table tr{padding:12px 16px;border-bottom:1px solid var(--line)}.stock-table td{border:0;padding:5px 0}.stock-table td:before{content:attr(data-label);display:inline-block;width:88px;color:var(--steel);font-size:11px}.stock-control{display:inline-flex;width:auto}.stock-top{align-items:flex-start;flex-direction:column}}
</style>

<div class="stock-top">
  <div>
    <h1 style="margin-bottom:8px">Склад</h1>
    <div class="stock-help">Здесь задаём фактическое количество каждой модели, цвета и размера. При оформлении заказа остаток автоматически уменьшается. Если остаток равен нулю, оформить этот вариант нельзя.</div>
  </div>
</div>

<?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="err"><?= h($err) ?></div><?php endif; ?>

<form method="post" id="stockForm">
<input type="hidden" name="a" value="save_stock">
<?php foreach ($products as $p): ?>
  <?php $total=0; foreach($p['variants'] as $v) $total += (int)$v['stock']; ?>
  <div class="panel stock-product" data-stock-product>
    <div class="stock-product__head">
      <div><div class="stock-product__title"><?= h($p['name']) ?></div><div class="stock-product__meta">Артикул: <?= h($p['sku']) ?> · <?= $p['status']==='published'?'Опубликован':'Черновик' ?></div></div>
      <div class="stock-total">Всего на складе<b data-product-total><?= $total ?></b></div>
    </div>
    <?php if ($p['variants']): ?>
    <table class="stock-table">
      <thead><tr><th>Цвет</th><th>Размер</th><th>Остаток</th><th>Статус</th></tr></thead>
      <tbody>
      <?php foreach ($p['variants'] as $v): $state=stock_state($v['stock']); ?>
        <tr>
          <td data-label="Цвет" class="stock-color"><?= h($v['color']) ?></td>
          <td data-label="Размер"><?= h($v['size']) ?></td>
          <td data-label="Остаток">
            <div class="stock-control">
              <button class="stock-step" type="button" data-step="-1" aria-label="Уменьшить">−</button>
              <input type="number" min="0" max="99999" step="1" name="stock[<?= (int)$v['variant_id'] ?>]" value="<?= (int)$v['stock'] ?>" data-stock-input>
              <button class="stock-step" type="button" data-step="1" aria-label="Увеличить">+</button>
            </div>
          </td>
          <td data-label="Статус"><span class="stock-badge <?= h($state[1]) ?>" data-stock-badge><?= h($state[0]) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?><div class="stock-empty">У товара пока нет цветов и размеров. Сначала добавь их в «Карточки товара».</div><?php endif; ?>
  </div>
<?php endforeach; ?>

<div class="stock-savebar"><div class="row"><button class="btn" type="submit">Сохранить остатки</button></div></div>
</form>

<script>
(function(){
  function paint(input){
    var n=Math.max(0,parseInt(input.value||'0',10)||0); input.value=n;
    var row=input.closest('tr'), badge=row&&row.querySelector('[data-stock-badge]');
    if(badge){
      badge.className='stock-badge '+(n<=0?'out':n<=3?'low':'ok');
      badge.textContent=n<=0?'Нет в наличии':n<=3?'Мало':'В наличии';
    }
    var product=input.closest('[data-stock-product]');
    if(product){
      var total=0; product.querySelectorAll('[data-stock-input]').forEach(function(x){total+=Math.max(0,parseInt(x.value||'0',10)||0);});
      var out=product.querySelector('[data-product-total]'); if(out)out.textContent=total;
    }
  }
  document.addEventListener('click',function(e){
    var b=e.target.closest('[data-step]'); if(!b)return;
    var input=b.parentNode.querySelector('[data-stock-input]'); if(!input)return;
    input.value=Math.max(0,(parseInt(input.value||'0',10)||0)+parseInt(b.dataset.step,10)); paint(input);
  });
  document.addEventListener('input',function(e){if(e.target.matches('[data-stock-input]'))paint(e.target);});
})();
</script>
<?php foot();
