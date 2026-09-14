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
})();
