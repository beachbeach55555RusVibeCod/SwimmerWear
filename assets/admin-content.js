(function(){
  'use strict';

  function esc(s){return String(s==null?'':s).replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
  function isVideo(src){return /\.(mp4|webm)(?:\?|$)/i.test(String(src||''));}
  function media(src,label,cls){
    src=String(src||'').trim(); label=String(label||'').trim(); cls=cls||'';
    if(!src)return '<div class="ph '+esc(cls)+'">'+esc(label||'медиа')+'</div>';
    if(isVideo(src))return '<video class="'+esc(cls)+'" src="'+esc(src)+'" autoplay muted loop playsinline preload="metadata" aria-label="'+esc(label)+'"></video>';
    return '<img class="'+esc(cls)+'" src="'+esc(src)+'" alt="'+esc(label)+'" loading="lazy">';
  }

  function applyHero(d){
    if(!d)return;
    var hero=document.querySelector('.hero .hero-in'); if(!hero)return;
    var t1=hero.querySelector('.hero-t1');
    if(d.t1!==undefined){ if(!t1){t1=document.createElement('div');t1.className='hero-t1';hero.insertBefore(t1,hero.firstChild);} t1.textContent=d.t1; }
    var h1=hero.querySelector('h1'); if(h1&&d.h1!==undefined)h1.innerHTML=esc(d.h1).replace(/\n/g,'<br>');
    var t2=hero.querySelector('.hero-t2');
    if(d.t2!==undefined){ if(!t2){t2=document.createElement('p');t2.className='hero-t2';if(h1)h1.after(t2);} t2.textContent=d.t2; }
    var cta=hero.querySelector('.hero-cta'); if(cta&&d.cta!==undefined)cta.textContent=d.cta;
  }

  function replaceScenarios(d){
    if(!d||!Array.isArray(d.items)||!d.items.length)return;
    var old=document.getElementById('scenarios'); if(!old||!old.parentNode)return;
    var items=d.items.filter(function(x){return x&&(x.src||x.t||x.text);}); if(!items.length)return;
    var sec=document.createElement('section'); sec.className='scen'; sec.id='scenarios'; sec.setAttribute('aria-label','Сценарии использования');
    sec.innerHTML='<div id="scenSlides"></div><div class="wrap sc-in"><h2>'+esc(d.title||'')+'</h2><div class="sc-box" id="scBox"></div></div><div class="scen-dots" id="scenDots"></div><div class="scen-arrows"><button type="button" class="scen-prev" aria-label="Предыдущий сценарий">&lsaquo;</button><button type="button" class="scen-next" aria-label="Следующий сценарий">&rsaquo;</button></div>';
    old.parentNode.replaceChild(sec,old);
    var slides=sec.querySelector('#scenSlides'),dots=sec.querySelector('#scenDots'),box=sec.querySelector('#scBox'),i=0,timer=null;
    items.forEach(function(it,n){
      var s=document.createElement('div'); s.className='sc'+(n===0?' on':''); s.innerHTML='<div class="med">'+media(it.src,it.label||it.t,'')+'</div><div class="shade"></div>'; slides.appendChild(s);
      var b=document.createElement('button'); b.type='button'; b.setAttribute('aria-label','Сценарий '+(n+1)); b.addEventListener('click',function(){go(n);}); dots.appendChild(b);
    });
    function paint(){var it=items[i];box.innerHTML='<h3>'+esc(it.t||it.label||'')+'</h3><p>'+esc(it.text||'')+'</p>';Array.prototype.forEach.call(dots.children,function(b,n){b.setAttribute('aria-current',n===i?'true':'false');});}
    function go(n){slides.children[i].classList.remove('on');i=(n+items.length)%items.length;slides.children[i].classList.add('on');paint();restart();}
    function restart(){clearInterval(timer);if(items.length>1&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches)timer=setInterval(function(){go(i+1);},7000);}
    sec.querySelector('.scen-prev').addEventListener('click',function(){go(i-1);}); sec.querySelector('.scen-next').addEventListener('click',function(){go(i+1);});
    paint();restart();
  }

  function replaceBuild(d){
    if(!d||!Array.isArray(d.details)||!d.details.length)return;
    var old=document.querySelector('.build-showcase'); if(!old||!old.parentNode)return;
    var items=d.details.filter(function(x){return x&&(x.src||x.t||x.text);}); if(!items.length)return;
    var sec=document.createElement('section'); sec.className='build build-showcase'; sec.id='construction';
    var paras=String(d.text||'').split(/\n+/).filter(Boolean).map(function(p){return '<p>'+esc(p)+'</p>';}).join('');
    sec.innerHTML='<div class="wrap build-showcase__grid"><div class="build-showcase__copy"><h2>'+esc(d.title||'Продуманная конструкция')+'</h2>'+paras+'</div><div class="build-showcase__slider"><div class="build-showcase__media"><div id="buildEditableMedia"></div><div class="build-showcase__shade"></div><div class="build-showcase__caption"><h3 id="buildSlideTitle"></h3><p id="buildSlideText"></p></div><button class="build-showcase__arrow build-showcase__arrow--prev" type="button" aria-label="Предыдущая деталь">&lsaquo;</button><button class="build-showcase__arrow build-showcase__arrow--next" type="button" aria-label="Следующая деталь">&rsaquo;</button><div class="build-showcase__count"><span id="buildSlideCurrent">01</span> / <span>'+String(items.length).padStart(2,'0')+'</span></div></div></div></div>';
    old.parentNode.replaceChild(sec,old);
    var holder=sec.querySelector('#buildEditableMedia'),title=sec.querySelector('#buildSlideTitle'),text=sec.querySelector('#buildSlideText'),count=sec.querySelector('#buildSlideCurrent'),i=0;
    function paint(){var it=items[i];holder.innerHTML=media(it.src,it.label||it.t,'');var el=holder.firstElementChild;if(el){el.style.position='absolute';el.style.inset='0';el.style.width='100%';el.style.height='100%';el.style.objectFit='cover';}title.textContent=it.t||it.label||'';text.textContent=it.text||'';count.textContent=String(i+1).padStart(2,'0');}
    function go(n){i=(n+items.length)%items.length;paint();}
    sec.querySelector('.build-showcase__arrow--prev').addEventListener('click',function(){go(i-1);});sec.querySelector('.build-showcase__arrow--next').addEventListener('click',function(){go(i+1);});paint();
  }

  function replaceKit(d){
    if(!d)return;
    var old=document.getElementById('kit'); if(!old||!old.parentNode)return;
    var shots=(d.shots||[]).filter(Boolean),labels=d.shot_labels||[];
    var items=(d.items||[]).filter(Boolean);
    var sec=document.createElement('section');sec.id='kit';sec.className='kit-showcase';
    var lis=items.map(function(x){return '<li>'+esc(x)+'</li>';}).join('');
    var figs=shots.map(function(src,i){return '<figure class="kit-shot'+(i===0?' kit-shot--main':'')+'">'+media(src,labels[i]||('Фото комплекта '+(i+1)),'')+'</figure>';}).join('');
    sec.innerHTML='<div class="wrap kit-showcase__grid"><div class="kit-showcase__copy"><h2>'+esc(d.title||'В комплекте')+'</h2><ul>'+lis+'</ul></div><div class="kit-showcase__gallery">'+figs+'</div></div>';
    old.parentNode.replaceChild(sec,old);
  }

  function applyBrand(d){
    if(!d)return;var sec=document.querySelector('.brand-story');if(!sec)return;
    var label=sec.querySelector('.brand-story__label'),h=sec.querySelector('h2'),text=sec.querySelector('.brand-story__text'),link=sec.querySelector('.brand-story__link'),mediaBox=sec.querySelector('.brand-story__media');
    if(label)label.textContent=d.label||'';if(h)h.textContent=d.title||'';
    if(text)text.innerHTML=String(d.text||'').split(/\n\s*\n/).filter(Boolean).map(function(p){return '<p>'+esc(p)+'</p>';}).join('');
    if(link)link.textContent=d.link_text||'';
    if(mediaBox&&d.media_src){mediaBox.innerHTML=media(d.media_src,d.media_label||'Вода','');var el=mediaBox.firstElementChild;if(el){el.style.width='100%';el.style.height='100%';el.style.objectFit='cover';el.style.objectPosition='center 70%';el.style.display='block';}}
  }

  function run(){
    fetch('/content-blocks.php',{cache:'no-store'}).then(function(r){if(!r.ok)throw new Error('content');return r.json();}).then(function(data){
      var b=data.blocks||{};applyHero(b.hero);replaceScenarios(b.scenarios);replaceBuild(b.build);replaceKit(b.kit);applyBrand(data.brand||{});
    }).catch(function(){});
  }

  if(document.readyState==='complete')setTimeout(run,0);else window.addEventListener('load',run,{once:true});
})();
