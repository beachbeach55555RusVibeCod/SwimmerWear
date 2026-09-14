(function(){
  'use strict';
  function applyHeroLayout(){
    var hero=document.querySelector('.hero .hero-in');
    if(!hero)return;
    var t1=hero.querySelector('.hero-t1');
    var h1=hero.querySelector('h1');
    var t2=hero.querySelector('.hero-t2');
    if(t1)t1.innerHTML='НАДЕЖНЫЕ ВЕЩИ<br>ДЛЯ ОТДЫХА У ВОДЫ';
    if(h1)h1.textContent='РЕЧНАЯ ПАРКА';
    if(t2)t2.textContent='Тёплая мембранная парка-плед для дождя, ветра и холодной погоды';

    var old=document.getElementById('hero-reference-layout');
    if(old)old.remove();
    var style=document.createElement('style');
    style.id='hero-reference-layout';
    style.textContent='.hero .hero-in{align-items:flex-start}.hero .hero-t1{margin:0 0 14px!important;font-size:14px!important;line-height:1.2!important;letter-spacing:.06em!important;text-transform:uppercase;color:rgba(255,255,255,.9)!important;max-width:none!important;white-space:nowrap!important}.hero .hero-in h1{margin:0 0 14px!important;font-size:clamp(42px,5.2vw,74px)!important;line-height:.95!important;letter-spacing:-.04em!important;text-transform:uppercase;white-space:nowrap!important;max-width:none!important}.hero .hero-t2{margin:0 0 26px!important;max-width:520px!important;font-size:18px!important;line-height:1.35!important;color:rgba(255,255,255,.92)!important}@media(max-width:900px){.hero .hero-t1{font-size:12px!important;margin-bottom:10px!important;white-space:nowrap!important}.hero .hero-in h1{font-size:clamp(34px,8vw,48px)!important;white-space:nowrap!important;margin-bottom:10px!important}.hero .hero-t2{font-size:15px!important;line-height:1.32!important;max-width:320px!important;margin-bottom:18px!important}}@media(max-width:390px){.hero .hero-in h1{font-size:32px!important;letter-spacing:-.045em!important}}';
    document.head.appendChild(style);
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',function(){setTimeout(applyHeroLayout,0);});
  else setTimeout(applyHeroLayout,0);
  window.addEventListener('load',applyHeroLayout,{once:true});
})();
