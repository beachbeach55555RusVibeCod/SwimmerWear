(function(){
  'use strict';
  if(document.querySelector('.build-showcase')) return;

  var oldBuild=document.querySelector('.build');
  if(oldBuild) oldBuild.remove();

  var anchor=document.getElementById('tech') || document.getElementById('scenarios');
  if(!anchor || !anchor.parentNode) return;

  var items=[
    {title:'Капюшон',text:'Анатомический капюшон с регулировкой по объёму и глубине. Защищает от ветра и дождя, не перекрывая обзор.',src:'https://images.unsplash.com/photo-1721745740020-ed1e8e0d4db8?auto=format&fit=crop&q=82&w=1200&h=1500'},
    {title:'Воротник',text:'Высокий воротник закрывает шею до подбородка. Внутренняя часть мягкая и комфортная при длительной носке.',src:'https://images.unsplash.com/photo-1654719796836-62b889d4598d?auto=format&fit=crop&q=82&w=1200&h=1500'},
    {title:'Манжеты',text:'Регулируемые манжеты помогают закрыться от дождя и ветра и легко ослабляются, когда становится теплее.',src:'https://images.unsplash.com/photo-1548883354-94bcfe321cbb?auto=format&fit=crop&q=82&w=1200&h=1500'},
    {title:'Карманы',text:'Вместительные внешние карманы и защищённый внутренний карман для телефона, документов и мелочей.',src:'https://images.unsplash.com/photo-1556098539-3019e1bdf05e?auto=format&fit=crop&q=82&w=1200&h=1500'},
    {title:'Молния',text:'Двухзамковая молния под ветрозащитным клапаном. Нижний бегунок даёт больше свободы при ходьбе и посадке.',src:'https://images.unsplash.com/photo-1727515546577-f7d82a47b51d?auto=format&fit=crop&q=82&w=1200&h=1500'}
  ];

  var sec=document.createElement('section');
  sec.className='build build-showcase';
  sec.id='construction';
  sec.innerHTML='<div class="wrap build-showcase__grid"><div class="build-showcase__copy"><h2>Продуманная конструкция</h2><p>Свободный крой рассчитан на второй слой одежды. Удлинённая спинка, высокий воротник и продуманные детали помогают сохранять тепло и свободу движения у воды, в дороге и на природе.</p><p>Ключевые зоны, которые первыми принимают на себя дождь, ветер и нагрузку, сделаны функциональными и простыми в использовании.</p></div><div class="build-showcase__slider"><div class="build-showcase__media"><img id="buildSlideImg" alt=""><div class="build-showcase__shade"></div><div class="build-showcase__caption"><h3 id="buildSlideTitle"></h3><p id="buildSlideText"></p></div><button class="build-showcase__arrow build-showcase__arrow--prev" type="button" aria-label="Предыдущая деталь">&lsaquo;</button><button class="build-showcase__arrow build-showcase__arrow--next" type="button" aria-label="Следующая деталь">&rsaquo;</button><div class="build-showcase__count"><span id="buildSlideCurrent">01</span> / <span>05</span></div></div></div></div>';
  anchor.parentNode.insertBefore(sec,anchor.nextSibling);

  var img=sec.querySelector('#buildSlideImg');
  var title=sec.querySelector('#buildSlideTitle');
  var text=sec.querySelector('#buildSlideText');
  var current=sec.querySelector('#buildSlideCurrent');
  var i=0;
  function paint(){var item=items[i];img.src=item.src;img.alt=item.title;title.textContent=item.title;text.textContent=item.text;current.textContent=String(i+1).padStart(2,'0');}
  function move(dir){i=(i+dir+items.length)%items.length;paint();}
  sec.querySelector('.build-showcase__arrow--prev').addEventListener('click',function(){move(-1);});
  sec.querySelector('.build-showcase__arrow--next').addEventListener('click',function(){move(1);});
  paint();

  var style=document.createElement('style');
  style.textContent='.build-showcase{background:#fff;padding:96px 0}.build-showcase__grid{display:grid;grid-template-columns:minmax(280px,.72fr) minmax(0,1.28fr);gap:72px;align-items:start}.build-showcase__copy{padding-top:8px}.build-showcase__copy h2{max-width:12ch;margin:0 0 24px;font-size:clamp(34px,4.6vw,58px);line-height:.98;letter-spacing:-.045em}.build-showcase__copy p{max-width:42ch;color:#6f7778;font-size:14px;line-height:1.62;margin:0 0 16px}.build-showcase__slider{min-width:0}.build-showcase__media{position:relative;min-height:620px;aspect-ratio:4/5;overflow:hidden;background:#EDEFEF}.build-showcase__media>img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.build-showcase__shade{position:absolute;inset:0;background:linear-gradient(180deg,rgba(12,15,16,.03) 42%,rgba(12,15,16,.72) 100%)}.build-showcase__caption{position:absolute;left:34px;right:78px;bottom:34px;color:#fff;z-index:2}.build-showcase__caption h3{font-size:28px;line-height:1;margin:0 0 10px}.build-showcase__caption p{font-size:13px;line-height:1.5;max-width:48ch;color:rgba(255,255,255,.84);margin:0}.build-showcase__arrow{position:absolute;top:50%;transform:translateY(-50%);z-index:3;width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.92);color:#191D1E;display:grid;place-items:center;font-size:20px;box-shadow:0 2px 12px rgba(0,0,0,.12)}.build-showcase__arrow--prev{left:16px}.build-showcase__arrow--next{right:16px}.build-showcase__arrow:hover{background:#fff}.build-showcase__count{position:absolute;right:30px;bottom:30px;z-index:3;color:rgba(255,255,255,.8);font-size:11px;letter-spacing:.12em}.build-showcase__count span:first-child{color:#fff;font-weight:600}@media(max-width:900px){.build-showcase{padding:72px 0}.build-showcase__grid{grid-template-columns:1fr;gap:32px}.build-showcase__copy h2{max-width:none}.build-showcase__media{min-height:520px;aspect-ratio:4/5}}@media(max-width:560px){.build-showcase{padding:56px 0}.build-showcase__media{min-height:460px}.build-showcase__caption{left:22px;right:56px;bottom:24px}.build-showcase__caption h3{font-size:24px}.build-showcase__arrow--prev{left:10px}.build-showcase__arrow--next{right:10px}}';
  document.head.appendChild(style);
})();