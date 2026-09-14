(function(){
  'use strict';
  function apply(){
    var nav=document.querySelector('header .menu');
    if(nav){
      var old=nav.querySelector('[data-open="brand"],a[href="#brand"],a[href="/brand.php"]');
      if(old) old.remove();
      var link=document.createElement('a');
      link.href='/brand.php';
      link.textContent='О бренде';
      var contacts=nav.querySelector('[data-open="contacts"],a[href="#contacts"]');
      if(contacts && contacts.parentNode===nav) contacts.insertAdjacentElement('afterend',link);
      else nav.appendChild(link);
    }
    var brandStory=document.querySelector('.brand-story');
    if(brandStory) brandStory.remove();
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',apply);
  else apply();
  setTimeout(apply,0);
})();
