(function(){
  'use strict';
  var style=document.createElement('style');
  style.textContent='.mobile-menu{position:fixed;inset:68px 0 0;background:#EDEFEF;z-index:105;padding:28px;display:none;overflow:auto}.mobile-menu.on{display:block}.mobile-menu a,.mobile-menu button{display:block;width:100%;text-align:left;padding:16px 0;border-bottom:1px solid #D6DADA;font-size:18px}.mobile-menu .close{position:absolute;right:20px;top:12px;width:auto;border:0;font-size:26px}.order-form{display:grid;gap:12px;margin-top:18px}.order-form label{font-size:12px;color:#7E8688}.order-form input,.order-form textarea{width:100%;border:1px solid #D6DADA;padding:12px;background:#fff;font:inherit}.order-form textarea{min-height:90px;resize:vertical}.order-form .order-actions{display:grid;gap:10px;margin-top:4px}.order-status{font-size:14px;color:#575E43}.order-error{color:#8B2F2F}.order-success{padding:18px;background:#EEF5EC;border:1px solid #B9D3B0}';
  document.head.appendChild(style);
  function getCart(){return Array.isArray(window.CART)?window.CART:[];}
  function money(n){return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,' ')+' ₽';}

  var menu=document.createElement('div'); menu.className='mobile-menu'; menu.id='mobileMenu'; menu.setAttribute('aria-label','Мобильное меню');
  var close=document.createElement('button'); close.className='close'; close.type='button'; close.setAttribute('aria-label','Закрыть меню'); close.textContent='×'; menu.appendChild(close);
  var nav=document.querySelector('.menu');
  if(nav){Array.prototype.forEach.call(nav.children,function(node){
    var copy=node.cloneNode(true), key=node.getAttribute('data-open');
    copy.removeAttribute('data-open');
    copy.addEventListener('click',function(e){
      e.preventDefault(); menu.classList.remove('on'); document.body.classList.remove('lock');
      if(key && typeof window.openSheet==='function') window.openSheet(key);
      else if(copy.getAttribute('href')) location.hash=copy.getAttribute('href');
    });
    menu.appendChild(copy);
  });}
  if(!nav || !nav.children.length){var catalog=document.createElement('a');catalog.href='#product';catalog.textContent='Каталог';menu.appendChild(catalog);}
  document.body.appendChild(menu);
  document.addEventListener('click',function(e){var b=e.target.closest?e.target.closest('.burger'):null;if(!b)return;e.preventDefault();e.stopImmediatePropagation();menu.classList.add('on');document.body.classList.add('lock');},true);
  close.addEventListener('click',function(){menu.classList.remove('on');document.body.classList.remove('lock');});

  function renderOrderForm(){
    var box=document.getElementById('cartItems'); if(!box)return;
    var old=box.querySelector('.order-form'); if(old)old.remove();
    var form=document.createElement('form'); form.className='order-form'; form.id='orderForm';
    form.innerHTML='<label>Имя<input name="name" required maxlength="120" autocomplete="name"></label><label>Телефон<input name="phone" required maxlength="40" autocomplete="tel"></label><label>E-mail<input name="email" type="email" maxlength="160" autocomplete="email"></label><label>Комментарий<textarea name="comment" maxlength="2000"></textarea></label><input name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true"><div class="order-status" id="orderStatus"></div><div class="order-actions"><button class="add" type="submit">ОТПРАВИТЬ ЗАКАЗ</button><button class="cont" type="button" id="backToCart">Назад в корзину</button></div>';
    box.appendChild(form); form.addEventListener('submit',submitOrder);
    document.getElementById('backToCart').addEventListener('click',function(){form.remove();var c=document.getElementById('checkout');if(c)c.style.display='';});
  }
  function submitOrder(e){
    e.preventDefault(); var form=e.currentTarget,status=document.getElementById('orderStatus');
    var data={name:form.name.value.trim(),phone:form.phone.value.trim(),email:form.email.value.trim(),comment:form.comment.value.trim(),website:form.website.value,items:getCart().map(function(it){return{sku:it.sku,color:it.color,size:it.size,qty:it.qty};})};
    status.className='order-status';status.textContent='Отправляем заказ…';
    fetch('/order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)}).then(function(r){return r.json().then(function(x){return{ok:r.ok,data:x};});}).then(function(res){
      if(!res.ok||!res.data.ok)throw new Error(res.data.error||'Не удалось оформить заказ.');
      window.CART.length=0; if(typeof window.renderCart==='function')window.renderCart();
      document.getElementById('cartItems').innerHTML='<div class="order-success"><b>Заказ №'+res.data.order_id+' принят.</b><br>Сумма: '+money(res.data.total)+'. Мы свяжемся с вами для подтверждения.</div>';
    }).catch(function(err){status.className='order-status order-error';status.textContent=err.message;});
  }
  document.addEventListener('click',function(e){
    var checkout=e.target.closest?e.target.closest('#checkout'):null;if(!checkout)return;
    e.preventDefault();e.stopImmediatePropagation();if(!getCart().length){checkout.textContent='КОРЗИНА ПУСТА';return;}
    renderOrderForm();checkout.style.display='none';
  },true);
})();

(function(){
  'use strict';
  function icon(type){
    var shapes={
      drop:'<path d="M12 3c3 4.2 5.5 7 5.5 10a5.5 5.5 0 1 1-11 0C6.5 10 9 7.2 12 3z"/><path d="M9.5 20.5h9"/>',
      fabric:'<rect x="3.5" y="3.5" width="17" height="17"/><path d="M3.5 9h17M3.5 15h17M9 3.5v17M15 3.5v17"/>',
      dwr:'<path d="M4 15.5c2.2-2.6 5-3.9 8-3.9s5.8 1.3 8 3.9"/><circle cx="8.5" cy="7" r="1.6"/><circle cx="15" cy="5.6" r="1.6"/><path d="M4 20h16"/>',
      fleece:'<path d="M4 8.5c1.6-2 3.2-2 4.8 0s3.2 2 4.8 0 3.2-2 4.8 0"/><path d="M4 13c1.6-2 3.2-2 4.8 0s3.2 2 4.8 0 3.2-2 4.8 0"/><path d="M4 17.5c1.6-2 3.2-2 4.8 0s3.2 2 4.8 0 3.2-2 4.8 0"/>'
    };
    return '<svg viewBox="0 0 24 24" aria-hidden="true">'+shapes[type]+'</svg>';
  }
  var hero=document.querySelector('.hero .hero-in');
  if(hero){
    var t1=hero.querySelector('.hero-t1'); if(t1)t1.textContent='НАДЕЖНЫЕ ВЕЩИ ДЛЯ ОТДЫХА У ВОДЫ';
    var h1=hero.querySelector('h1'); if(h1)h1.textContent='РЕЧНАЯ ПАРКА';
    var t2=hero.querySelector('.hero-t2'); if(t2)t2.textContent='Тёплая мембранная парка-плед для дождя, ветра и холодной погоды';
    var specs=hero.querySelector('.specs');
    if(!specs){specs=document.createElement('div');specs.className='specs';}
    specs.innerHTML=
      '<div class="spec">'+icon('drop')+'<div><b>10K / 10K</b><span>водостойкость снаружи, отведение пара изнутри</span></div></div>'+ 
      '<div class="spec">'+icon('fabric')+'<div><b>140 g/m</b><span>прочная плотная ткань</span></div></div>'+ 
      '<div class="spec">'+icon('dwr')+'<div><b>DWR</b><span>пропитка от дождя и снега</span></div></div>'+ 
      '<div class="spec">'+icon('fleece')+'<div><b>Wellsoft</b><span>подкладка мягкая, как плед</span></div></div>';
    var cta=hero.querySelector('.hero-cta');
    if(!cta){cta=document.createElement('a');cta.className='hero-cta';hero.appendChild(cta);}
    cta.href='#product';cta.textContent='ВЫБРАТЬ';
    if(specs.parentNode!==hero)hero.insertBefore(specs,cta); else if(specs.nextElementSibling!==cta)hero.insertBefore(specs,cta);
  }
  var css=document.createElement('style');
  css.textContent='.hero-veil{background:linear-gradient(90deg,rgba(10,13,14,.72) 0%,rgba(10,13,14,.52) 36%,rgba(10,13,14,.12) 72%),linear-gradient(180deg,rgba(10,13,14,.04),rgba(10,13,14,.38))}.hero-in{padding-top:108px;padding-bottom:46px;justify-content:flex-end;align-items:flex-start}.hero-t1{font-size:11px;line-height:1.25;letter-spacing:.14em;margin-bottom:12px;max-width:300px}.hero-in h1{font-size:clamp(48px,6.6vw,88px);line-height:.92;letter-spacing:-.05em;max-width:none;margin:0;text-transform:uppercase}.hero-t2{font-size:16px;line-height:1.42;max-width:520px;margin-top:14px}.hero .specs{display:grid;grid-template-columns:repeat(4,minmax(125px,1fr));gap:22px;max-width:780px;margin-top:30px;border-top:0;padding-top:0}.hero .spec{display:grid;grid-template-columns:28px 1fr;gap:10px;align-items:start;padding-right:0;min-height:0;border-right:0}.hero .spec svg{width:24px;height:24px;stroke:rgba(255,255,255,.95);stroke-width:1.35;fill:none;margin-top:1px}.hero .spec b{display:block;font-size:14px;line-height:1.2;letter-spacing:.01em}.hero .spec span{display:block;margin-top:5px;font-size:10px;line-height:1.35;max-width:150px;color:rgba(255,255,255,.72)}.hero-cta{margin-top:26px;min-width:150px;text-align:center;padding:13px 30px;font-size:11px;letter-spacing:.14em;background:#fff;color:#171B1C;border:1px solid #fff}.hero-cta:hover{background:transparent;color:#fff}@media(max-width:900px){.hero .specs{grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 16px;max-width:560px}.hero-in h1{font-size:clamp(46px,13vw,76px)}.hero-t2{font-size:15px;max-width:480px}}@media(max-width:560px){.hero-in{padding-bottom:30px}.hero .specs{margin-top:24px}.hero .spec span{font-size:9px}.hero-cta{margin-top:22px}}';
  document.head.appendChild(css);
})();

(function(){
  'use strict';
  var data=[
    {title:'Вода',text:'Длина закрывает поясницу в лодке, капюшон регулируется под ветер с воды.',src:'https://images.unsplash.com/photo-1699645257408-70f18e50991f?auto=format&fit=crop&q=82&w=1920&h=1080'},
    {title:'Природа',text:'Плотная ткань 140 g/m не боится веток и мокрой травы.',src:'https://images.unsplash.com/photo-1610817118922-a7374b775fd9?auto=format&fit=crop&q=82&w=1920&h=1080'},
    {title:'Свобода',text:'Сложили в фирменный мешок, убрали в багажник — и парка едет с вами.',src:'https://images.unsplash.com/photo-1667331634686-313ec875d91e?auto=format&fit=crop&q=82&w=1920&h=1080'}
  ];
  var sec=document.getElementById('scenarios');
  if(!sec){
    sec=document.createElement('section');sec.className='scen';sec.id='scenarios';sec.setAttribute('aria-label','Сценарии использования');
    sec.innerHTML='<div id="scenSlides"></div><div class="wrap sc-in"><h2>Вода. Природа. Свобода.</h2><div class="sc-box" id="scBox"></div></div><div class="scen-dots" id="scenDots"></div>';
    var product=document.getElementById('product');
    if(product && product.parentNode) product.parentNode.insertBefore(sec,product.nextSibling); else document.querySelector('main,body').appendChild(sec);
  }
  var title=sec.querySelector('.sc-in h2'); if(title) title.textContent='Вода. Природа. Свобода.';
  var slides=sec.querySelector('#scenSlides'), dots=sec.querySelector('#scenDots'), box=sec.querySelector('#scBox');
  if(!slides||!dots||!box)return;
  if(slides.children.length)return;
  var i=0,t=null;
  data.forEach(function(s,n){
    var slide=document.createElement('div');slide.className='sc'+(n===0?' on':'');
    slide.innerHTML='<div class="med"><img src="'+s.src+'" alt="'+s.title+'"></div><div class="shade"></div>';
    slides.appendChild(slide);
    var d=document.createElement('button');d.type='button';d.setAttribute('aria-label','Сценарий '+(n+1));d.setAttribute('aria-current',n===0?'true':'false');
    d.addEventListener('click',function(){go(n);});dots.appendChild(d);
  });
  function paint(){box.innerHTML='<h3>'+data[i].title+'</h3><p>'+data[i].text+'</p>';}
  function go(n){
    var a=slides.children,b=dots.children;if(!a.length)return;
    a[i].classList.remove('on');b[i].setAttribute('aria-current','false');
    i=(n+a.length)%a.length;a[i].classList.add('on');b[i].setAttribute('aria-current','true');paint();tick();
  }
  function tick(){clearInterval(t);if(data.length>1)t=setInterval(function(){go(i+1);},7000);}
  paint();
  if(!window.matchMedia('(prefers-reduced-motion: reduce)').matches)tick();
})();

(function(){
  'use strict';
  var sec=document.getElementById('scenarios');
  if(!sec || sec.querySelector('.scen-arrows')) return;
  var arrows=document.createElement('div');
  arrows.className='scen-arrows';
  arrows.innerHTML='<button type="button" class="scen-prev" aria-label="Предыдущий сценарий">&lsaquo;</button><button type="button" class="scen-next" aria-label="Следующий сценарий">&rsaquo;</button>';
  sec.appendChild(arrows);
  function move(dir){
    var dots=sec.querySelectorAll('#scenDots button');
    if(!dots.length) return;
    var current=0;
    for(var i=0;i<dots.length;i++) if(dots[i].getAttribute('aria-current')==='true'){current=i;break;}
    dots[(current+dir+dots.length)%dots.length].click();
  }
  arrows.querySelector('.scen-prev').addEventListener('click',function(){move(-1);});
  arrows.querySelector('.scen-next').addEventListener('click',function(){move(1);});
  var css=document.createElement('style');
  css.textContent='.scen-arrows{position:absolute;top:20px;right:32px;z-index:6;display:flex;gap:8px}.scen-arrows button{width:38px;height:38px;border:1px solid rgba(255,255,255,.45);color:#fff;display:grid;place-items:center;font-size:18px;line-height:1;background:transparent}.scen-arrows button:hover{background:rgba(255,255,255,.16)}@media(max-width:900px){.scen-arrows{top:18px;right:20px}}';
  document.head.appendChild(css);
})();

(function(){
  'use strict';
  if(document.getElementById('tech')) return;
  var s=document.createElement('script');
  s.src='/assets/tech-fallback.js?v=2';
  s.defer=true;
  document.body.appendChild(s);
})();

(function(){
  'use strict';
  if(document.querySelector('.build-showcase')) return;
  var s=document.createElement('script');
  s.src='/assets/build-fallback.js?v=4';
  s.defer=true;
  document.body.appendChild(s);
})();
