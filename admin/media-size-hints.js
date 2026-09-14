(function(){
  'use strict';

  function getHint(title){
    title=(title||'').trim().toLowerCase();
    if(title.indexOf('слайд')===0) return 'Рекомендуемый размер: 1920 × 1200 px · горизонтальное';
    if(title.indexOf('кадр')===0) return 'Рекомендуемый размер: 1600 × 1200 px · горизонтальное';
    if(title.indexOf('деталь')===0) return 'Рекомендуемый размер: 1600 × 1200 px · горизонтальное';
    if(title.indexOf('фото комплекта')===0) return 'Рекомендуемый размер: 1400 × 1400 px · квадрат';
    if(title.indexOf('большое фото / видео')===0) return 'Рекомендуемый размер: 2200 × 1400 px · горизонтальное';
    return 'Рекомендуемый размер: 1600 × 1200 px';
  }

  function applyHints(){
    document.querySelectorAll('.visual-media').forEach(function(box){
      if(box.querySelector('.visual-size-hint')) return;
      var fields=box.querySelector('.visual-fields');
      if(!fields) return;
      var label=fields.querySelector('label');
      if(!label) return;
      var hint=document.createElement('div');
      hint.className='visual-size-hint';
      hint.textContent=getHint(label.textContent);
      label.insertAdjacentElement('afterend',hint);
    });

    if(!document.getElementById('visual-size-hints-style')){
      var style=document.createElement('style');
      style.id='visual-size-hints-style';
      style.textContent='.visual-size-hint{display:inline-block;margin:0 0 8px;padding:4px 7px;border:1px solid #d9dddd;background:#f6f7f7;color:#5d6567;font-size:11px;line-height:1.2;border-radius:2px;font-weight:600}';
      document.head.appendChild(style);
    }
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',applyHints);
  else applyHints();
})();
