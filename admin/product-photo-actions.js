(function(){
  'use strict';

  function post(fd){
    return fetch('product-photo-actions.php',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(function(r){return r.json().then(function(j){if(!r.ok||!j.ok)throw new Error(j.error||'Не удалось выполнить действие');return j;});});
  }

  function photoId(thumb){
    var i=thumb.querySelector('input[name="photo_id"]');
    return i?i.value:'';
  }
  function productId(thumb){
    var i=thumb.querySelector('input[name="pid"]');
    return i?i.value:'';
  }
  function selectedThumbs(){
    return Array.prototype.slice.call(document.querySelectorAll('.thumb[data-photo-select="1"] input.photo-select-box:checked')).map(function(c){return c.closest('.thumb');});
  }

  function init(){
    var thumbsWrap=document.querySelector('.thumbs');
    if(!thumbsWrap)return;
    var thumbs=Array.prototype.slice.call(thumbsWrap.querySelectorAll('.thumb'));
    if(!thumbs.length)return;

    thumbs.forEach(function(thumb){
      if(!photoId(thumb))return;
      thumb.dataset.photoSelect='1';
      thumb.style.position='relative';
      var label=document.createElement('label');
      label.className='photo-select-toggle';
      label.innerHTML='<input class="photo-select-box" type="checkbox"><span>✓</span>';
      thumb.insertBefore(label,thumb.firstChild);
      var del=thumb.querySelector('form input[name="a"][value="photo_del"]');
      if(del){var f=del.closest('form');f.removeAttribute('onsubmit');f.classList.add('photo-delete-form');}
    });

    var firstSelect=thumbsWrap.querySelector('.photo-color-form select[name="color"]');
    var pid=productId(thumbs[0]);
    var bar=document.createElement('div');
    bar.className='photo-selection-bar';
    var all=document.createElement('button'); all.type='button'; all.className='btn sm grey'; all.textContent='Выбрать все';
    var count=document.createElement('span'); count.className='photo-selection-count'; count.textContent='Выбрано: 0';
    var color=firstSelect?firstSelect.cloneNode(true):document.createElement('select');
    Array.prototype.slice.call(color.options||[]).forEach(function(o){if(!(o.value||'').trim())o.remove();});
    color.removeAttribute('onchange'); color.removeAttribute('data-ajax-color-bound');
    var setColor=document.createElement('button'); setColor.type='button'; setColor.className='btn sm'; setColor.textContent='Назначить цвет';
    var delSelected=document.createElement('button'); delSelected.type='button'; delSelected.className='btn sm red'; delSelected.textContent='Удалить выбранные';
    var status=document.createElement('span'); status.className='photo-selection-status';
    bar.appendChild(all);bar.appendChild(count);bar.appendChild(color);bar.appendChild(setColor);bar.appendChild(delSelected);bar.appendChild(status);
    thumbsWrap.parentNode.insertBefore(bar,thumbsWrap);

    function refresh(){
      var sel=selectedThumbs();
      count.textContent='Выбрано: '+sel.length;
      delSelected.disabled=!sel.length;
      setColor.disabled=!sel.length||!color.value;
      all.textContent=sel.length===thumbsWrap.querySelectorAll('.thumb[data-photo-select="1"]').length&&sel.length?'Снять выбор':'Выбрать все';
      thumbsWrap.querySelectorAll('.thumb[data-photo-select="1"]').forEach(function(t){var c=t.querySelector('.photo-select-box');t.classList.toggle('is-selected',!!(c&&c.checked));});
    }
    thumbsWrap.addEventListener('change',function(e){if(e.target.classList.contains('photo-select-box'))refresh();});
    color.addEventListener('change',refresh);
    all.addEventListener('click',function(){
      var boxes=Array.prototype.slice.call(thumbsWrap.querySelectorAll('.photo-select-box'));
      var mark=!boxes.length?false:boxes.some(function(b){return !b.checked;});
      boxes.forEach(function(b){b.checked=mark;});refresh();
    });

    setColor.addEventListener('click',function(){
      var sel=selectedThumbs(); if(!sel.length||!color.value)return;
      var fd=new FormData();fd.append('pid',pid);fd.append('mode','color');fd.append('color',color.value);
      sel.forEach(function(t){fd.append('ids[]',photoId(t));});
      status.textContent='Сохраняем…';setColor.disabled=true;
      post(fd).then(function(){
        sel.forEach(function(t){var s=t.querySelector('.photo-color-form select[name="color"]');if(s)s.value=color.value;});
        status.textContent='Цвет назначен выбранным фото';
      }).catch(function(e){status.textContent=e.message;}).finally(refresh);
    });

    function removeThumbs(sel){sel.forEach(function(t){t.remove();});refresh();if(!thumbsWrap.querySelector('.thumb')){var p=document.createElement('p');p.className='tag';p.textContent='Фото пока нет';thumbsWrap.insertAdjacentElement('afterend',p);}}
    delSelected.addEventListener('click',function(){
      var sel=selectedThumbs();if(!sel.length)return;
      if(!confirm('Удалить выбранные фото: '+sel.length+'?'))return;
      var fd=new FormData();fd.append('pid',pid);fd.append('mode','delete');sel.forEach(function(t){fd.append('ids[]',photoId(t));});
      status.textContent='Удаляем…';delSelected.disabled=true;
      post(fd).then(function(){removeThumbs(sel);status.textContent='Выбранные фото удалены';}).catch(function(e){status.textContent=e.message;}).finally(refresh);
    });

    document.addEventListener('submit',function(e){
      var form=e.target.closest&&e.target.closest('.photo-delete-form');if(!form)return;
      e.preventDefault();if(!confirm('Удалить фото из карточки?'))return;
      var thumb=form.closest('.thumb'),fd=new FormData();fd.append('pid',productId(thumb));fd.append('mode','delete');fd.append('ids[]',photoId(thumb));
      form.querySelector('button').disabled=true;
      post(fd).then(function(){removeThumbs([thumb]);status.textContent='Фото удалено';}).catch(function(err){status.textContent=err.message;form.querySelector('button').disabled=false;});
    });

    var style=document.createElement('style');style.id='photo-selection-style';
    style.textContent='.photo-selection-bar{display:grid;grid-template-columns:auto auto minmax(150px,220px) auto auto 1fr;gap:8px;align-items:center;padding:12px 14px;margin:0 0 16px;border:1px solid #dfe3e3;background:#f7f8f8}.photo-selection-count,.photo-selection-status{font-size:11px;color:#626a6c}.photo-select-toggle{position:absolute;z-index:5;top:8px;left:8px;margin:0}.photo-select-toggle input{position:absolute;opacity:0}.photo-select-toggle span{display:grid;place-items:center;width:27px;height:27px;background:rgba(255,255,255,.94);border:1px solid #bcc3c4;border-radius:3px;color:transparent;cursor:pointer;font-size:15px;font-weight:700}.photo-select-toggle input:checked+span{background:#202526;border-color:#202526;color:#fff}.thumb.is-selected{box-shadow:inset 0 0 0 2px #202526}@media(max-width:900px){.photo-selection-bar{grid-template-columns:1fr 1fr}.photo-selection-bar select,.photo-selection-status{grid-column:1/-1}}';
    document.head.appendChild(style);refresh();
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
