(function(){
  'use strict';

  function cart(){ return Array.isArray(window.CART) ? window.CART : []; }
  function money(n){ return String(n).replace(/\B(?=(\d{3})+(?!\d))/g,' ') + ' ₽'; }
  function esc(s){ return String(s == null ? '' : s).replace(/[&<>"']/g,function(ch){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]; }); }

  var style=document.createElement('style');
  style.textContent='\
.checkout-mail-backdrop{position:fixed;inset:0;background:rgba(14,17,18,.62);z-index:260;display:none;padding:28px;overflow:auto}.checkout-mail-backdrop.on{display:flex;align-items:center;justify-content:center}.checkout-mail-modal{width:min(620px,100%);max-height:calc(100vh - 56px);overflow:auto;background:#fff;border:1px solid #d7dbdb;box-shadow:0 24px 80px rgba(0,0,0,.24)}.checkout-mail-head{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid #e1e4e4}.checkout-mail-head h2{margin:0;font-size:20px;line-height:1.1}.checkout-mail-close{border:0;background:transparent;font:inherit;font-size:26px;line-height:1;cursor:pointer;padding:2px 6px}.checkout-mail-body{padding:22px}.checkout-mail-note{margin:0 0 18px;color:#6f7778;font-size:13px;line-height:1.45}.checkout-mail-summary{display:grid;gap:8px;margin:0 0 20px;padding:14px;background:#f3f4f4;border:1px solid #e0e3e3}.checkout-mail-item{display:flex;justify-content:space-between;gap:14px;font-size:12px}.checkout-mail-item span:last-child{white-space:nowrap}.checkout-mail-form{display:grid;gap:13px}.checkout-mail-form label{display:grid;gap:6px;font-size:12px;color:#6f7778}.checkout-mail-form input,.checkout-mail-form textarea{width:100%;box-sizing:border-box;border:1px solid #cfd4d5;background:#fff;padding:12px 13px;font:inherit;color:#171b1c}.checkout-mail-form textarea{min-height:96px;resize:vertical}.checkout-mail-actions{display:grid;grid-template-columns:1fr auto;gap:10px;margin-top:4px}.checkout-mail-submit{border:1px solid #575e43;background:#575e43;color:#fff;padding:13px 18px;font:inherit;font-size:12px;letter-spacing:.06em;cursor:pointer}.checkout-mail-cancel{border:1px solid #cfd4d5;background:#fff;color:#171b1c;padding:13px 18px;font:inherit;cursor:pointer}.checkout-mail-status{min-height:18px;font-size:12px;color:#575e43}.checkout-mail-status.error{color:#8b2f2f}.checkout-mail-success{padding:18px;border:1px solid #b9d3b0;background:#eef5ec;line-height:1.5}.checkout-mail-success b{display:block;margin-bottom:6px;font-size:17px}@media(max-width:620px){.checkout-mail-backdrop{padding:12px;align-items:flex-end!important}.checkout-mail-modal{max-height:92vh}.checkout-mail-body{padding:18px}.checkout-mail-actions{grid-template-columns:1fr}.checkout-mail-cancel{order:2}}';
  document.head.appendChild(style);

  var backdrop=document.createElement('div');
  backdrop.className='checkout-mail-backdrop';
  backdrop.id='checkoutMailBackdrop';
  backdrop.innerHTML='<div class="checkout-mail-modal" role="dialog" aria-modal="true" aria-labelledby="checkoutMailTitle"><div class="checkout-mail-head"><h2 id="checkoutMailTitle">Оформление заказа</h2><button type="button" class="checkout-mail-close" aria-label="Закрыть">×</button></div><div class="checkout-mail-body" id="checkoutMailBody"></div></div>';
  document.body.appendChild(backdrop);

  function close(){ backdrop.classList.remove('on'); }
  backdrop.querySelector('.checkout-mail-close').addEventListener('click',close);
  backdrop.addEventListener('click',function(e){ if(e.target===backdrop) close(); });
  document.addEventListener('keydown',function(e){ if(e.key==='Escape' && backdrop.classList.contains('on')) close(); });

  function summaryHtml(){
    var total=0;
    var rows=cart().map(function(it){
      var sum=(Number(it.price)||0)*(Number(it.qty)||1); total+=sum;
      return '<div class="checkout-mail-item"><span>'+esc(it.name)+' · '+esc(it.color)+' · '+esc(it.size)+' × '+esc(it.qty)+'</span><span>'+money(sum)+'</span></div>';
    }).join('');
    rows+='<div class="checkout-mail-item" style="padding-top:8px;border-top:1px solid #d7dbdb;font-weight:600"><span>Итого</span><span>'+money(total)+'</span></div>';
    return rows;
  }

  function openForm(){
    var body=document.getElementById('checkoutMailBody');
    body.innerHTML='<p class="checkout-mail-note">Заполните контакты. После отправки заказ сохранится и уведомление с составом заказа уйдёт менеджеру на e-mail.</p><div class="checkout-mail-summary">'+summaryHtml()+'</div><form class="checkout-mail-form" id="checkoutMailForm"><label>Имя<input name="name" required maxlength="120" autocomplete="name"></label><label>Телефон<input name="phone" required maxlength="40" autocomplete="tel"></label><label>E-mail<input name="email" type="email" maxlength="160" autocomplete="email"></label><label>Комментарий<textarea name="comment" maxlength="2000" placeholder="Например: удобное время для звонка"></textarea></label><input name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true"><div class="checkout-mail-status" id="checkoutMailStatus"></div><div class="checkout-mail-actions"><button class="checkout-mail-submit" type="submit">ОТПРАВИТЬ ЗАКАЗ</button><button class="checkout-mail-cancel" type="button">Вернуться в корзину</button></div></form>';
    var form=document.getElementById('checkoutMailForm');
    form.addEventListener('submit',submit);
    form.querySelector('.checkout-mail-cancel').addEventListener('click',close);
    backdrop.classList.add('on');
    setTimeout(function(){ var n=form.querySelector('[name="name"]'); if(n)n.focus(); },30);
  }

  function submit(e){
    e.preventDefault();
    var form=e.currentTarget;
    var status=document.getElementById('checkoutMailStatus');
    var btn=form.querySelector('.checkout-mail-submit');
    var data={
      name:form.name.value.trim(),
      phone:form.phone.value.trim(),
      email:form.email.value.trim(),
      comment:form.comment.value.trim(),
      website:form.website.value,
      items:cart().map(function(it){ return {sku:it.sku,color:it.color,size:it.size,qty:it.qty}; })
    };
    btn.disabled=true;
    status.className='checkout-mail-status';
    status.textContent='Отправляем заказ…';
    fetch('/order.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)})
      .then(function(r){ return r.json().then(function(x){ return {ok:r.ok,data:x}; }); })
      .then(function(res){
        if(!res.ok || !res.data.ok) throw new Error(res.data.error || 'Не удалось оформить заказ.');
        if(Array.isArray(window.CART)) window.CART.length=0;
        if(typeof window.renderCart==='function') window.renderCart();
        var mailText=res.data.email_sent===false ? '<br><span style="color:#8b2f2f">Заказ сохранён, но e-mail уведомление менеджеру не отправилось автоматически.</span>' : '<br>Уведомление менеджеру отправлено на e-mail.';
        document.getElementById('checkoutMailBody').innerHTML='<div class="checkout-mail-success"><b>Заказ №'+esc(res.data.order_id)+' принят</b>Сумма: '+money(res.data.total)+'.'+mailText+'<br>Мы свяжемся с вами для подтверждения.</div><div style="margin-top:14px"><button type="button" class="checkout-mail-cancel" id="checkoutMailDone">Закрыть</button></div>';
        document.getElementById('checkoutMailDone').addEventListener('click',close);
      })
      .catch(function(err){ status.className='checkout-mail-status error'; status.textContent=err.message; btn.disabled=false; });
  }

  document.addEventListener('click',function(e){
    var checkout=e.target.closest ? e.target.closest('#checkout') : null;
    if(!checkout) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    if(!cart().length){ checkout.textContent='КОРЗИНА ПУСТА'; setTimeout(function(){checkout.textContent='ОФОРМИТЬ ЗАКАЗ';},1400); return; }
    openForm();
  },true);
})();