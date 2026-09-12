(function(){
  'use strict';

  var style = document.createElement('style');
  style.textContent = '.mobile-menu{position:fixed;inset:68px 0 0;background:#EDEFEF;z-index:105;padding:28px;display:none;overflow:auto}.mobile-menu.on{display:block}.mobile-menu a,.mobile-menu button{display:block;width:100%;text-align:left;padding:16px 0;border-bottom:1px solid #D6DADA;font-size:18px}.mobile-menu .close{position:absolute;right:20px;top:12px;width:auto;border:0;font-size:26px}.order-form{display:grid;gap:12px;margin-top:18px}.order-form label{font-size:12px;color:#7E8688}.order-form input,.order-form textarea{width:100%;border:1px solid #D6DADA;padding:12px;background:#fff;font:inherit}.order-form textarea{min-height:90px;resize:vertical}.order-form .order-actions{display:grid;gap:10px;margin-top:4px}.order-status{font-size:14px;color:#575E43}.order-error{color:#8B2F2F}.order-success{padding:18px;background:#EEF5EC;border:1px solid #B9D3B0}.checkout-secondary{display:none}';
  document.head.appendChild(style);

  function getCart(){ return Array.isArray(window.CART) ? window.CART : []; }
  function money(n){ return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,' ') + ' ₽'; }

  // Mobile navigation: the previous burger opened Contacts. Keep all CMS menu items available.
  var menu = document.createElement('div');
  menu.className = 'mobile-menu';
  menu.id = 'mobileMenu';
  menu.setAttribute('aria-label','Мобильное меню');
  var close = document.createElement('button');
  close.className = 'close'; close.type='button'; close.setAttribute('aria-label','Закрыть меню'); close.textContent='×';
  menu.appendChild(close);
  var nav = document.querySelector('.menu');
  if (nav) {
    Array.prototype.forEach.call(nav.children, function(node){
      var copy = node.cloneNode(true);
      copy.removeAttribute('data-open');
      copy.addEventListener('click', function(){ menu.classList.remove('on'); });
      menu.appendChild(copy);
    });
  }
  var catalog = document.createElement('a');
  catalog.href='#product'; catalog.textContent='Каталог';
  catalog.addEventListener('click',function(){menu.classList.remove('on');});
  if (!nav || !nav.children.length) menu.appendChild(catalog);
  document.body.appendChild(menu);

  function openMobile(e){
    e.preventDefault(); e.stopImmediatePropagation();
    menu.classList.add('on');
    document.body.classList.add('lock');
  }
  document.addEventListener('click', function(e){
    var burger = e.target.closest ? e.target.closest('.burger') : null;
    if (burger) openMobile(e);
  }, true);
  close.addEventListener('click',function(){menu.classList.remove('on');document.body.classList.remove('lock');});

  function renderOrderForm(){
    var box = document.getElementById('cartItems');
    if (!box) return;
    var old = box.querySelector('.order-form');
    if (old) old.remove();
    var form = document.createElement('form');
    form.className='order-form'; form.id='orderForm';
    form.innerHTML = '<label>Имя<input name="name" required maxlength="120" autocomplete="name"></label>'+
      '<label>Телефон<input name="phone" required maxlength="40" autocomplete="tel"></label>'+
      '<label>E-mail<input name="email" type="email" maxlength="160" autocomplete="email"></label>'+
      '<label>Комментарий<textarea name="comment" maxlength="2000"></textarea></label>'+ 
      '<input name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">'+
      '<div class="order-status" id="orderStatus"></div>'+ 
      '<div class="order-actions"><button class="add" type="submit">ОТПРАВИТЬ ЗАКАЗ</button><button class="cont" type="button" id="backToCart">Назад в корзину</button></div>';
    box.appendChild(form);
    form.addEventListener('submit', submitOrder);
    document.getElementById('backToCart').addEventListener('click',function(){form.remove();});
  }

  function submitOrder(e){
    e.preventDefault();
    var form=e.currentTarget, status=document.getElementById('orderStatus');
    var data={name:form.name.value.trim(),phone:form.phone.value.trim(),email:form.email.value.trim(),comment:form.comment.value.trim(),website:form.website.value,items:getCart().map(function(it){return {sku:it.sku,color:it.color,size:it.size,qty:it.qty};})};
    status.className='order-status'; status.textContent='Отправляем заказ…';
    fetch('/order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)})
      .then(function(r){return r.json().then(function(x){return {ok:r.ok,data:x};});})
      .then(function(res){
        if(!res.ok || !res.data.ok) throw new Error(res.data.error || 'Не удалось оформить заказ.');
        window.CART.length=0;
        if(typeof window.renderCart==='function') window.renderCart();
        var body=document.getElementById('cartItems');
        body.innerHTML='<div class="order-success"><b>Заказ №'+res.data.order_id+' принят.</b><br>Сумма: '+money(res.data.total)+'. Мы свяжемся с вами для подтверждения.</div>';
      })
      .catch(function(err){status.className='order-status order-error';status.textContent=err.message;});
  }

  document.addEventListener('click',function(e){
    var checkout=e.target.closest ? e.target.closest('#checkout') : null;
    if(!checkout) return;
    e.preventDefault(); e.stopImmediatePropagation();
    if(!getCart().length){ checkout.textContent='КОРЗИНА ПУСТА'; return; }
    renderOrderForm();
    checkout.style.display='none';
  },true);
})();
