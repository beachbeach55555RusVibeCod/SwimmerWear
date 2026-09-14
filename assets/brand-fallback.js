(function(){
  'use strict';

  var oldStory=document.querySelector('.brand-story');
  if(oldStory) oldStory.remove();

  var brandButton=document.querySelector('.menu [data-open="brand"]');
  if(brandButton){
    var link=document.createElement('a');
    link.href='/brand.php';
    link.textContent=(brandButton.textContent||'О бренде').trim() || 'О бренде';
    link.className=brandButton.className || '';
    brandButton.replaceWith(link);
  }

  window.addEventListener('load',function(){
    var t1=document.querySelector('.hero .hero-t1');
    if(t1)t1.innerHTML='НАДЕЖНЫЕ ВЕЩИ<br>ДЛЯ ОТДЫХА У ВОДЫ';
  });
})();
