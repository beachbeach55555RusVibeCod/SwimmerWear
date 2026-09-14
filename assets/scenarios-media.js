(function(){
  'use strict';
  if(window.__swScenariosMediaStarted)return;
  window.__swScenariosMediaStarted=true;

  var fallback={title:'Вода. Природа. Свобода.',items:[
    {src:'https://images.unsplash.com/photo-1699645257408-70f18e50991f?auto=format&fit=crop&q=82&w=1920&h=1080',t:'Вода',text:'Длина закрывает поясницу в лодке, капюшон регулируется под ветер с воды.'},
    {src:'https://images.unsplash.com/photo-1610817118922-a7374b775fd9?auto=format&fit=crop&q=82&w=1920&h=1080',t:'Природа',text:'Плотная ткань 140 g/m не боится веток и мокрой травы.'},
    {src:'https://images.unsplash.com/photo-1667331634686-313ec875d91e?auto=format&fit=crop&q=82&w=1920&h=1080',t:'Свобода',text:'Сложили в фирменный мешок, убрали в багажник — и парка едет с вами.'}
  ]};

  function media(src,label){
    if(/\.(mp4|webm)(?:\?|$)/i.test(src||'')){
      var v=document.createElement('video');v.src=src;v.muted=true;v.loop=true;v.playsInline=true;v.autoplay=true;return v;
    }
    var img=document.createElement('img');img.src=src||'';img.alt=label||'';return img;
  }

  function render(data){
    data=data&&Array.isArray(data.items)&&data.items.length?data:fallback;
    var old=document.getElementById('scenarios');
    if(old)old.remove();
    var anchor=document.getElementById('product');
    if(!anchor||!anchor.parentNode)return;

    var sec=document.createElement('section');sec.className='scen';sec.id='scenarios';sec.setAttribute('aria-label','Сценарии использования');
    sec.innerHTML='<div id="scenSlides"></div><div class="wrap sc-in"><h2></h2><div class="sc-box" id="scBox"></div></div><div class="scen-dots" id="scenDots"></div><div class="scen-arrows"><button type="button" class="scen-prev" aria-label="Предыдущий сценарий">&lsaquo;</button><button type="button" class="scen-next" aria-label="Следующий сценарий">&rsaquo;</button></div>';
    anchor.parentNode.insertBefore(sec,anchor.nextSibling);
    sec.querySelector('h2').textContent=data.title||fallback.title;

    var slides=sec.querySelector('#scenSlides'),dots=sec.querySelector('#scenDots'),box=sec.querySelector('#scBox');
    var items=data.items.filter(function(x){return x&&(x.src||x.t||x.text);});
    if(!items.length)items=fallback.items;
    var i=0,t=null;
    items.forEach(function(s,n){
      var slide=document.createElement('div');slide.className='sc'+(n===0?' on':'');
      var med=document.createElement('div');med.className='med';med.appendChild(media(s.src,s.label||s.t));slide.appendChild(med);
      var shade=document.createElement('div');shade.className='shade';slide.appendChild(shade);slides.appendChild(slide);
      var d=document.createElement('button');d.type='button';d.setAttribute('aria-label','Сценарий '+(n+1));d.setAttribute('aria-current',n===0?'true':'false');d.addEventListener('click',function(){go(n);});dots.appendChild(d);
    });
    function paint(){box.innerHTML='';var h=document.createElement('h3');h.textContent=items[i].t||'';var p=document.createElement('p');p.textContent=items[i].text||'';box.appendChild(h);box.appendChild(p);}
    function go(n){var a=slides.children,b=dots.children;if(!a.length)return;a[i].classList.remove('on');b[i].setAttribute('aria-current','false');i=(n+a.length)%a.length;a[i].classList.add('on');b[i].setAttribute('aria-current','true');paint();tick();}
    function tick(){clearInterval(t);if(items.length>1&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches)t=setInterval(function(){go(i+1);},7000);}
    sec.querySelector('.scen-prev').addEventListener('click',function(){go(i-1);});
    sec.querySelector('.scen-next').addEventListener('click',function(){go(i+1);});
    paint();tick();

    if(!document.getElementById('scenarios-media-style')){
      var style=document.createElement('style');style.id='scenarios-media-style';style.textContent='.scen-arrows{position:absolute;top:20px;right:32px;z-index:6;display:flex;gap:8px}.scen-arrows button{width:38px;height:38px;border:1px solid rgba(255,255,255,.45);color:#fff;display:grid;place-items:center;font-size:18px;line-height:1;background:transparent}.scen-arrows button:hover{background:rgba(255,255,255,.16)}@media(max-width:900px){.scen-arrows{top:18px;right:20px}}';document.head.appendChild(style);
    }
  }

  var cfg=window.SW_MEDIA_BLOCKS&&window.SW_MEDIA_BLOCKS.scenarios;
  if(cfg)render(cfg);else fetch('/media-block-data.php',{cache:'no-store'}).then(function(r){return r.json();}).then(function(x){window.SW_MEDIA_BLOCKS=x||{};render(x&&x.scenarios);}).catch(function(){render(fallback);});
})();
