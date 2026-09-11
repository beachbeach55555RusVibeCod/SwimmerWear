/* SWIMMER — витрина. Данные приходят из PHP в window.SW (см. index.php). */
var SW          = window.SW || {};
var HERO_SLIDES = SW.hero   || [];
var COLORS      = SW.colors || [];
var SIZES       = SW.sizes  || [];
var PRODUCTS    = SW.products  || [];
var SCENARIOS   = SW.scenarios || [];
var DETAILS     = SW.details   || [];
var MODEL_NOTE  = SW.modelNote || '';
var PDESC       = SW.pdesc  || {};

/* ========================== ХЕЛПЕРЫ ========================== */
function colorById(id){ for (var i=0;i<COLORS.length;i++) if (COLORS[i].id===id) return COLORS[i]; return COLORS[0]; }
function money(n){ return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,' ') + ' \u20BD'; }
function el(tag, cls, txt){ var e=document.createElement(tag); if(cls) e.className=cls; if(txt!=null) e.textContent=txt; return e; }

function mediaNode(m, phClass){
  if (m && m.type === 'video' && m.src) {
    var v = document.createElement('video');
    v.src = m.src; v.muted = true; v.loop = true; v.playsInline = true;
    v.setAttribute('playsinline',''); v.preload = 'metadata';
    if (m.poster) v.poster = m.poster;
    return v;
  }
  if (m && m.type === 'image' && m.src) {
    var i = document.createElement('img'); i.src = m.src; i.alt = m.alt || ''; return i;
  }
  var p = el('div', 'ph' + (phClass ? ' ' + phClass : ''));
  p.textContent = (m && m.label) || 'медиа';
  return p;
}

/* ========================== HERO ========================== */
(function(){
  var box = document.getElementById('heroSlides'),
      dots = document.getElementById('heroDots'),
      i = 0, t = null;

  HERO_SLIDES.forEach(function(s, n){
    var slide = el('div','hs');
    if (n === 0) slide.classList.add('on');
    slide.appendChild(mediaNode(s,'dark'));
    box.appendChild(slide);

    var d = el('button');
    d.setAttribute('aria-label','Слайд ' + (n+1));
    d.setAttribute('aria-current', n === 0 ? 'true' : 'false');
    d.addEventListener('click', function(){ go(n); });
    dots.appendChild(d);
  });

  function go(n){
    var a = box.children, b = dots.children;
    if (a.length < 2) return;
    a[i].classList.remove('on'); b[i].setAttribute('aria-current','false');
    var pv = a[i].querySelector('video'); if (pv) pv.pause();
    i = (n + a.length) % a.length;
    a[i].classList.add('on'); b[i].setAttribute('aria-current','true');
    var v = a[i].querySelector('video'); if (v){ v.currentTime = 0; v.play().catch(function(){}); }
    tick();
  }
  function tick(){ clearInterval(t); if (HERO_SLIDES.length > 1) t = setInterval(function(){ go(i+1); }, 6500); }

  document.getElementById('hNext').addEventListener('click', function(){ go(i+1); });
  document.getElementById('hPrev').addEventListener('click', function(){ go(i-1); });

  var x0 = null, hero = document.getElementById('hero');
  hero.addEventListener('touchstart', function(e){ x0 = e.touches[0].clientX; }, {passive:true});
  hero.addEventListener('touchend', function(e){
    if (x0 === null) return;
    var dx = e.changedTouches[0].clientX - x0;
    if (Math.abs(dx) > 50) go(dx < 0 ? i+1 : i-1);
    x0 = null;
  }, {passive:true});

  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) tick();
  var fv = box.querySelector('.hs.on video'); if (fv) fv.play().catch(function(){});
})();

/* ========================== КАТАЛОГ ========================== */
(function(){
  var rail = document.getElementById('rail');

  PRODUCTS.forEach(function(p){
    var c = colorById(p.colorId);
    var card = el('div','card');
    card.setAttribute('role','button');
    card.setAttribute('tabindex','0');

    var shot = el('div','shot');
    shot.appendChild(mediaNode({ type:'image', src:p.shots[0], alt:p.name }));
    card.appendChild(shot);

    var body = el('div','cbody');
    body.appendChild(el('h3', null, p.name));
    var col = el('div','col');
    var dot = el('span','dot'); dot.style.background = c.hex;
    col.appendChild(dot); col.appendChild(el('span', null, c.name));
    body.appendChild(col);
    body.appendChild(el('div','price', money(p.price)));
    card.appendChild(body);

    card.addEventListener('click', function(){ openProduct(p.id); });
    card.addEventListener('keydown', function(e){
      if (e.key === 'Enter' || e.key === ' '){ e.preventDefault(); openProduct(p.id); }
    });

    rail.appendChild(card);
  });

  function step(dir){
    var first = rail.querySelector('.card');
    if (!first) return;
    rail.scrollBy({ left: dir * (first.offsetWidth + 24), behavior:'smooth' });
  }
  document.getElementById('pNext').addEventListener('click', function(){ step(1); });
  document.getElementById('pPrev').addEventListener('click', function(){ step(-1); });
})();

/* ========================== СЦЕНАРИИ ========================== */
(function(){
  var box = document.getElementById('scenSlides'),
      dots = document.getElementById('scenDots'),
      copy = document.getElementById('scBox'),
      i = 0, t = null;

  SCENARIOS.forEach(function(s, n){
    var slide = el('div','sc');
    if (n === 0) slide.classList.add('on');
    var med = el('div','med');
    med.appendChild(mediaNode(s.media,'dark'));
    slide.appendChild(med);
    slide.appendChild(el('div','shade'));
    box.appendChild(slide);

    var d = el('button');
    d.setAttribute('aria-label','Сценарий ' + (n+1));
    d.setAttribute('aria-current', n === 0 ? 'true' : 'false');
    d.addEventListener('click', function(){ go(n); });
    dots.appendChild(d);
  });

  function paint(){
    copy.innerHTML = '';
    copy.appendChild(el('h3', null, SCENARIOS[i].title));
    copy.appendChild(el('p', null, SCENARIOS[i].text));
  }
  function go(n){
    var a = box.children, b = dots.children;
    if (!a.length) return;
    a[i].classList.remove('on'); b[i].setAttribute('aria-current','false');
    var pv = a[i].querySelector('video'); if (pv) pv.pause();
    i = (n + a.length) % a.length;
    a[i].classList.add('on'); b[i].setAttribute('aria-current','true');
    var v = a[i].querySelector('video'); if (v){ v.currentTime = 0; v.play().catch(function(){}); }
    paint(); tick();
  }
  function tick(){ clearInterval(t); if (SCENARIOS.length > 1) t = setInterval(function(){ go(i+1); }, 7000); }

  paint();
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) tick();
})();

/* ========================== КОНСТРУКЦИЯ ========================== */
(function(){
  var rail = document.getElementById('detRail');
  DETAILS.forEach(function(d){
    var card = el('div','det');
    card.appendChild(mediaNode({ type:'image', src:d.src, alt:d.title }));
    var cap = el('div','cap');
    cap.appendChild(el('h3', null, d.title));
    cap.appendChild(el('p', null, d.text));
    card.appendChild(cap);
    rail.appendChild(card);
  });
  function step(dir){
    var first = rail.querySelector('.det');
    if (!first) return;
    rail.scrollBy({ left: dir * (first.offsetWidth + 20), behavior:'smooth' });
  }
  document.getElementById('dNext').addEventListener('click', function(){ step(1); });
  document.getElementById('dPrev').addEventListener('click', function(){ step(-1); });
})();

/* ========================== МОДАЛКИ ========================== */
var ov = document.getElementById('ov');
var SHEETS = { product:'mProduct', sizes:'mSizes', brand:'mBrand', contacts:'mContacts', cart:'mCart' };
var openStack = [];

function openSheet(key){
  var node = document.getElementById(SHEETS[key]);
  if (!node) return;
  node.classList.add('on');
  ov.classList.add('on');
  document.body.classList.add('lock');
  if (openStack.indexOf(key) === -1) openStack.push(key);
}
function closeTop(){
  var key = openStack.pop();
  if (key) document.getElementById(SHEETS[key]).classList.remove('on');
  if (!openStack.length){ ov.classList.remove('on'); document.body.classList.remove('lock'); }
}
function closeAll(){
  openStack.forEach(function(k){ document.getElementById(SHEETS[k]).classList.remove('on'); });
  openStack = [];
  ov.classList.remove('on');
  document.body.classList.remove('lock');
}

document.addEventListener('click', function(e){
  var o = e.target.closest ? e.target.closest('[data-open]') : null;
  if (o){ openSheet(o.getAttribute('data-open')); return; }
  var c = e.target.closest ? e.target.closest('[data-close]') : null;
  if (c){
    if (c.tagName === 'A' && c.getAttribute('href')) closeAll();
    else { e.preventDefault(); closeTop(); }
  }
});
ov.addEventListener('click', closeAll);
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeTop(); });

/* ========================== КАРТОЧКА ТОВАРА ========================== */
var sel = { productId:null, colorId:null, size:null };

function openProduct(id){
  var p = null;
  for (var i=0;i<PRODUCTS.length;i++) if (PRODUCTS[i].id === id) p = PRODUCTS[i];
  if (!p) return;
  sel.productId = p.id; sel.colorId = p.colorId; sel.size = null;
  renderProduct();
  openSheet('product');
}
function currentProduct(){
  for (var i=0;i<PRODUCTS.length;i++) if (PRODUCTS[i].id === sel.productId) return PRODUCTS[i];
  return PRODUCTS[0];
}
function productByColor(name, colorId){
  for (var i=0;i<PRODUCTS.length;i++){
    if (PRODUCTS[i].name === name && PRODUCTS[i].colorId === colorId) return PRODUCTS[i];
  }
  return null;
}

function renderProduct(){
  var p = currentProduct(), c = colorById(sel.colorId);
  document.getElementById('pTitleTop').textContent = p.name + ' ' + c.name.toLowerCase();

  var body = document.getElementById('pBody');
  body.innerHTML = '';
  var grid = el('div','pcard');

  var shots = el('div','pshots');
  var main = document.createElement('img');
  main.className = 'main'; main.src = p.shots[0]; main.alt = p.name;
  shots.appendChild(main);
  var th = el('div','pthumbs');
  p.shots.forEach(function(src){
    var t = document.createElement('img');
    t.src = src; t.alt = '';
    t.addEventListener('click', function(){ main.src = src; });
    th.appendChild(t);
  });
  shots.appendChild(th);
  grid.appendChild(shots);

  var info = el('div','pinfo');
  info.appendChild(el('h2', null, p.name + ' ' + c.name.toLowerCase()));
  info.appendChild(el('div','sku','Артикул: ' + (p.sku || '—')));
  info.appendChild(el('div','pprice', money(p.price)));

  info.appendChild(el('div','opt-label','Цвет: ' + c.name));
  var row = el('div','colors-row');
  COLORS.forEach(function(col){
    var b = el('button','cbtn');
    b.setAttribute('aria-pressed', col.id === sel.colorId ? 'true' : 'false');
    var ic = document.createElement('i'); ic.style.background = col.hex;
    b.appendChild(ic); b.appendChild(el('span', null, col.name));
    var target = productByColor(p.name, col.id);
    if (!target){ b.disabled = true; b.style.opacity = '.4'; }
    b.addEventListener('click', function(){
      if (!target) return;
      sel.productId = target.id; sel.colorId = col.id;
      renderProduct();
    });
    row.appendChild(b);
  });
  info.appendChild(row);

  info.appendChild(el('div','opt-label','Размер'));
  var sr = el('div','sizes-row');
  SIZES.forEach(function(s){
    var b = el('button','sbtn', s);
    b.setAttribute('aria-pressed', s === sel.size ? 'true' : 'false');
    b.addEventListener('click', function(){ sel.size = s; renderProduct(); });
    sr.appendChild(b);
  });
  info.appendChild(sr);

  var sizeLink = el('button','linkish','Таблица размеров');
  sizeLink.addEventListener('click', function(){ openSheet('sizes'); });
  info.appendChild(sizeLink);

  info.appendChild(el('div','model-note', MODEL_NOTE));

  var add = el('button','add','ПОЛОЖИТЬ В КОРЗИНУ');
  add.addEventListener('click', function(){
    if (!sel.size){
      add.textContent = 'ВЫБЕРИТЕ РАЗМЕР';
      setTimeout(function(){ add.textContent = 'ПОЛОЖИТЬ В КОРЗИНУ'; }, 1600);
      return;
    }
    addToCart(p, c, sel.size);
    closeTop();
    openSheet('cart');
  });
  info.appendChild(add);

  var d = el('div','pdesc');
  (p.descr || '').split(/\n+/).forEach(function(par){
    if (par.trim()) d.appendChild(el('p', null, par.trim()));
  });
  if ((PDESC.tags || []).length) {
    var sp = el('div','specs-inline');
    PDESC.tags.forEach(function(s){ sp.appendChild(el('span', null, s)); });
    d.insertBefore(sp, d.children[1] || null);
  }
  info.appendChild(d);

  var acc = el('div','acc'), accHtml = '';
  [['Состав', p.compose], ['Уход', p.care], ['Комплектация', p.kit], ['Доставка', p.delivery]]
    .forEach(function(pair){
      if (!pair[1]) return;
      var rows = pair[1].split(/\n+/).filter(function(x){ return x.trim(); })
                        .map(function(x){ return '<p>' + x.trim() + '</p>'; }).join('');
      accHtml += '<details><summary>' + pair[0] + '</summary><div class="in">' + rows + '</div></details>';
    });
  acc.innerHTML = accHtml;
  if (accHtml) info.appendChild(acc);

  grid.appendChild(info);
  body.appendChild(grid);
}

/* ========================== КОРЗИНА ========================== */
var CART = [];

function addToCart(p, c, size){
  var key = p.id + '|' + size;
  for (var i=0;i<CART.length;i++){
    if (CART[i].key === key){ CART[i].qty++; renderCart(); return; }
  }
  CART.push({ key:key, name:p.name, sku:p.sku, color:c.name,
              size:size, price:p.price, qty:1, shot:p.shots[0] });
  renderCart();
}

function renderCart(){
  var box = document.getElementById('cartItems');
  box.innerHTML = '';
  var total = 0, count = 0;

  if (!CART.length){
    box.appendChild(el('div','empty','Пока пусто. Выберите парку в каталоге.'));
  }

  CART.forEach(function(it){
    total += it.price * it.qty;
    count += it.qty;

    var row = el('div','citem');
    var img = document.createElement('img');
    img.src = it.shot; img.alt = '';
    row.appendChild(img);

    var col = el('div');
    col.appendChild(el('h4', null, it.name + ' ' + it.color.toLowerCase()));
    col.appendChild(el('div','meta','Артикул: ' + (it.sku || '—')));
    col.appendChild(el('div','meta','Цвет: ' + it.color + ' · Размер: ' + it.size));

    var q = el('div','qty');
    var minus = el('button', null, '−');
    var val = el('span', null, String(it.qty));
    var plus = el('button', null, '+');
    minus.addEventListener('click', function(){
      it.qty--;
      if (it.qty < 1) CART = CART.filter(function(x){ return x.key !== it.key; });
      renderCart();
    });
    plus.addEventListener('click', function(){ it.qty++; renderCart(); });
    q.appendChild(minus); q.appendChild(val); q.appendChild(plus);
    col.appendChild(q);

    var r = el('div','row');
    r.appendChild(el('div','price num', money(it.price * it.qty)));
    var rm = el('button','rm','Удалить');
    rm.addEventListener('click', function(){
      CART = CART.filter(function(x){ return x.key !== it.key; });
      renderCart();
    });
    r.appendChild(rm);
    col.appendChild(r);

    row.appendChild(col);
    box.appendChild(row);
  });

  document.getElementById('cartTotal').textContent = money(total);
  document.getElementById('cartCount').textContent = String(count);
}

document.getElementById('checkout').addEventListener('click', function(){
  var b = this;
  if (!CART.length){
    b.textContent = 'КОРЗИНА ПУСТА';
    setTimeout(function(){ b.textContent = 'ОФОРМИТЬ ЗАКАЗ'; }, 1600);
    return;
  }
  b.textContent = 'ПОДКЛЮЧИТЕ ОПЛАТУ';
  setTimeout(function(){ b.textContent = 'ОФОРМИТЬ ЗАКАЗ'; }, 2000);
});

renderCart();
