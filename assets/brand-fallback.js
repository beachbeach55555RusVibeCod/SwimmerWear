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

  function fillFooterContacts(){
    var target=document.querySelector('.footer-spec__contacts');
    var source=document.querySelector('#mContacts .contacts-list');
    if(!target||!source)return;
    target.innerHTML='<div class="footer-contact-data">'+source.innerHTML+'</div>';

    if(!document.getElementById('footer-contact-data-style')){
      var style=document.createElement('style');
      style.id='footer-contact-data-style';
      style.textContent='.footer-contact-data{display:grid;gap:9px;font-size:12px;line-height:1.45;color:#AEB4B4;min-width:180px}.footer-contact-data>div{display:grid;gap:2px}.footer-contact-data b{font-size:9px;letter-spacing:.12em;color:#858C8D}.footer-contact-data a{color:inherit;text-decoration:none}.footer-contact-data a:hover{text-decoration:underline}@media(max-width:700px){.footer-contact-data{min-width:0}}';
      document.head.appendChild(style);
    }
  }

  fillFooterContacts();
  window.addEventListener('load',function(){
    var t1=document.querySelector('.hero .hero-t1');
    if(t1)t1.innerHTML='НАДЕЖНЫЕ ВЕЩИ<br>ДЛЯ ОТДЫХА У ВОДЫ';
    fillFooterContacts();
  });
})();
