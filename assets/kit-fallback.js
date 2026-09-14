(function(){
  'use strict';
  if(document.getElementById('kit')) return;
  var sw=window.SW||{}, productShot=(sw.products&&sw.products[0]&&sw.products[0].shots&&sw.products[0].shots[0])||'https://images.unsplash.com/photo-1603252109303-2751441dd157?auto=format&fit=crop&q=82&w=1000&h=1000';
  var sec=document.createElement('section'); sec.id='kit'; sec.className='kit-showcase';
  sec.innerHTML='<div class="wrap kit-showcase__grid"><div class="kit-showcase__copy"><h2>В комплекте</h2><ul><li>Парка</li><li>Фирменный непромокаемый прочный мешок для хранения</li><li>Брендированный зип-пакет</li></ul></div><div class="kit-showcase__gallery"><figure class="kit-shot kit-shot--main"><img src="'+productShot+'" alt="Парка"></figure><figure class="kit-shot"><img src="https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&q=82&w=900&h=1100" alt="Мешок для хранения"></figure><figure class="kit-shot"><img src="https://images.unsplash.com/photo-1605733160314-4fc7dac4bb16?auto=format&fit=crop&q=82&w=900&h=1100" alt="Брендированный зип-пакет"></figure></div></div>';
  var build=document.querySelector('.build-showcase');
  var tech=document.getElementById('tech');
  if(build&&build.parentNode) build.parentNode.insertBefore(sec,build.nextSibling);
  else if(tech&&tech.parentNode) tech.parentNode.insertBefore(sec,tech.nextSibling);
  else document.body.insertBefore(sec,document.querySelector('footer'));
  var style=document.createElement('style');
  style.textContent='.kit-showcase{background:#ECEEEE;padding:82px 0}.kit-showcase__grid{display:grid;grid-template-columns:minmax(220px,.65fr) minmax(0,1.35fr);gap:54px;align-items:center}.kit-showcase__copy h2{margin:0 0 24px;font-size:clamp(34px,4.8vw,58px);line-height:.98;letter-spacing:-.045em}.kit-showcase__copy ul{list-style:none;margin:0;padding:0;display:grid;gap:14px;max-width:420px}.kit-showcase__copy li{position:relative;padding-left:18px;font-size:14px;line-height:1.5;color:#4f5758}.kit-showcase__copy li:before{content:"•";position:absolute;left:0;top:0;color:#575E43}.kit-showcase__gallery{display:grid;grid-template-columns:1.1fr .9fr .9fr;gap:12px;min-width:0}.kit-shot{margin:0;aspect-ratio:4/5;overflow:hidden;background:#dfe3e3}.kit-shot img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .35s ease}.kit-shot:hover img{transform:scale(1.02)}@media(max-width:900px){.kit-showcase{padding:64px 0}.kit-showcase__grid{grid-template-columns:1fr;gap:30px}.kit-showcase__gallery{grid-template-columns:repeat(3,1fr)}}@media(max-width:560px){.kit-showcase{padding:50px 0}.kit-showcase__gallery{grid-template-columns:1fr 1fr}.kit-shot--main{grid-column:1/-1;aspect-ratio:16/10}.kit-shot{aspect-ratio:1/1}}';
  document.head.appendChild(style);
})();