(function(){
  'use strict';
  var existing=document.querySelector('.reviews-showcase');
  if(existing)return;

  var old=document.querySelector('.revs');
  var texts=[];
  if(old){
    old.querySelectorAll('.rev p').forEach(function(p){
      var t=(p.textContent||'').trim();if(t)texts.push(t);
    });
    var oldSection=old.closest('section');
    if(oldSection)oldSection.remove();
  }
  if(!texts.length)texts=['Отзывы покупателей появятся здесь после публикации.'];

  var faq=null;
  document.querySelectorAll('section').forEach(function(sec){
    var h=sec.querySelector('h2');
    if(h && (h.textContent||'').trim()==='Часто задаваемые вопросы') faq=sec;
  });
  if(!faq || !faq.parentNode)return;

  var sec=document.createElement('section');
  sec.className='reviews-showcase';
  sec.innerHTML='<div class="wrap"><h2>Отзывы</h2><div class="reviews-showcase__grid"></div></div>';
  var grid=sec.querySelector('.reviews-showcase__grid');
  texts.forEach(function(t){var card=document.createElement('article');card.className='reviews-showcase__card';card.textContent=t;grid.appendChild(card);});
  faq.parentNode.insertBefore(sec,faq.nextSibling);

  var style=document.createElement('style');
  style.textContent='.reviews-showcase{background:#fff;padding:88px 0}.reviews-showcase h2{margin:0 0 28px;font-size:clamp(34px,4.5vw,56px);line-height:1;letter-spacing:-.04em}.reviews-showcase__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:18px}.reviews-showcase__card{min-height:190px;background:#575E43;color:#fff;padding:34px 38px;display:flex;align-items:center;font-size:18px;line-height:1.5;font-weight:500}.reviews-showcase__card:before,.reviews-showcase__card:after{display:none!important}@media(max-width:700px){.reviews-showcase{padding:64px 0}.reviews-showcase__card{min-height:160px;padding:28px 24px;font-size:16px}}';
  document.head.appendChild(style);
})();

(function(){
  'use strict';
  var footer=document.querySelector('footer');
  if(!footer)return;
  var wrap=footer.querySelector('.wrap');
  if(!wrap)return;
  wrap.innerHTML='<div class="footer-spec"><div class="footer-spec__brand"><img class="fbrand" src="/assets/logo-white.svg" alt="SWIMMER"><p class="fslogan">Увидимся у воды.</p></div><nav class="footer-spec__links" aria-label="Информация"><a href="#" data-footer-placeholder>Публичная оферта</a><a href="#" data-footer-placeholder>Способы оплаты</a><a href="#" data-footer-placeholder>Гарантия и возврат</a></nav></div>';
  footer.addEventListener('click',function(e){var a=e.target.closest('[data-footer-placeholder]');if(a)e.preventDefault();});
  var style=document.createElement('style');
  style.textContent='.footer-spec{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:48px;align-items:end}.footer-spec__brand .fslogan{margin-top:14px}.footer-spec__links{display:flex;flex-direction:column;gap:10px;align-items:flex-start}.footer-spec__links a{color:inherit;text-decoration:none}.footer-spec__links a:hover{text-decoration:underline}@media(max-width:700px){.footer-spec{grid-template-columns:1fr;gap:34px}.footer-spec__links{gap:12px}}';
  document.head.appendChild(style);
})();

(function(){
  'use strict';
  var style=document.createElement('style');
  style.textContent='header .bar{max-width:1360px;margin:0 auto;padding-left:32px;padding-right:32px}header .logo img{height:24px;width:auto}header .tools{justify-self:end}@media(max-width:900px){header .bar{padding-left:20px;padding-right:20px}header .logo img{height:24px}.cart-open{margin-right:0}}';
  document.head.appendChild(style);
})();
