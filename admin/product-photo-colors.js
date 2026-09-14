(function(){
  'use strict';

  function post(data){
    return fetch('product-photo-color.php',{
      method:'POST',
      body:data,
      headers:{'X-Requested-With':'XMLHttpRequest'}
    }).then(function(r){
      return r.json().then(function(j){
        if(!r.ok || !j.ok) throw new Error(j.error || 'Не удалось сохранить цвет');
        return j;
      });
    });
  }

  function setStatus(box,text,isError){
    var s=box.querySelector('.photo-color-status');
    if(!s){
      s=document.createElement('span');
      s.className='photo-color-status';
      box.appendChild(s);
    }
    s.textContent=text;
    s.classList.toggle('is-error',!!isError);
    clearTimeout(s._timer);
    s._timer=setTimeout(function(){s.textContent='';s.classList.remove('is-error');},1800);
  }

  function nonEmptyOptions(select){
    return Array.prototype.slice.call(select.options).filter(function(o){return (o.value||'').trim()!=='';});
  }

  function normalizeUploadSelect(){
    var form=document.querySelector('form input[name="a"][value="photo_upload"]');
    form=form&&form.closest('form');
    if(!form)return;
    var select=form.querySelector('select[name="color"]');
    if(!select)return;
    var opts=nonEmptyOptions(select);
    if(!opts.length)return;
    Array.prototype.slice.call(select.options).forEach(function(o){if((o.value||'').trim()==='')o.remove();});
    if(!select.value && select.options.length)select.selectedIndex=0;
    select.required=true;
    var label=select.closest('div')&&select.closest('div').querySelector('label');
    if(label)label.textContent='Цвет этих фото — обязательно';
  }

  function normalizePhotoSelect(select){
    var empty=null;
    Array.prototype.slice.call(select.options).forEach(function(o){
      if((o.value||'').trim()==='')empty=o;
    });
    if(empty){
      empty.textContent='Не назначен';
      empty.disabled=true;
    }
  }

  function bindSingle(form){
    var select=form.querySelector('select[name="color"]');
    if(!select || select.dataset.ajaxColorBound==='1') return;
    normalizePhotoSelect(select);
    select.dataset.ajaxColorBound='1';
    select.removeAttribute('onchange');
    select.addEventListener('change',function(){
      if(!select.value)return;
      var fd=new FormData();
      fd.append('pid',form.querySelector('[name="pid"]').value);
      fd.append('photo_id',form.querySelector('[name="photo_id"]').value);
      fd.append('color',select.value);
      select.disabled=true;
      setStatus(form,'Сохраняем…',false);
      post(fd).then(function(){
        setStatus(form,'Сохранено',false);
      }).catch(function(err){
        setStatus(form,err.message,true);
      }).finally(function(){select.disabled=false;});
    });
  }

  function addUnassignedWarning(forms){
    var count=forms.filter(function(form){
      var s=form.querySelector('select[name="color"]');
      return s&&!s.value;
    }).length;
    if(!count)return;
    var thumbs=document.querySelector('.thumbs');
    if(!thumbs)return;
    var note=document.createElement('div');
    note.className='photo-color-warning';
    note.textContent='Есть фото без назначенного цвета: '+count+'. Они не будут показываться в цветовых карточках, пока не выберешь цвет.';
    thumbs.parentNode.insertBefore(note,thumbs);
  }

  function addBulk(forms){
    var thumbs=document.querySelector('.thumbs');
    if(!thumbs || document.querySelector('.photo-color-bulk')) return;
    var first=forms[0].querySelector('select[name="color"]');
    if(!first) return;
    var pid=forms[0].querySelector('[name="pid"]').value;

    var wrap=document.createElement('div');
    wrap.className='photo-color-bulk';
    var label=document.createElement('div');
    label.className='photo-color-bulk__label';
    label.textContent='Один цвет для всех загруженных фото';
    var select=first.cloneNode(true);
    Array.prototype.slice.call(select.options).forEach(function(o){if((o.value||'').trim()==='')o.remove();});
    if(select.options.length)select.selectedIndex=0;
    select.removeAttribute('onchange');
    select.removeAttribute('data-ajax-color-bound');
    var btn=document.createElement('button');
    btn.type='button';
    btn.className='btn sm';
    btn.textContent='Применить ко всем';
    var status=document.createElement('span');
    status.className='photo-color-bulk__status';
    wrap.appendChild(label);
    wrap.appendChild(select);
    wrap.appendChild(btn);
    wrap.appendChild(status);
    thumbs.parentNode.insertBefore(wrap,thumbs);

    btn.addEventListener('click',function(){
      if(!select.value)return;
      var fd=new FormData();
      fd.append('pid',pid);
      fd.append('mode','all');
      fd.append('color',select.value);
      btn.disabled=true;
      select.disabled=true;
      status.textContent='Сохраняем…';
      status.classList.remove('is-error');
      post(fd).then(function(){
        forms.forEach(function(form){
          var s=form.querySelector('select[name="color"]');
          if(s) s.value=select.value;
        });
        var warning=document.querySelector('.photo-color-warning');
        if(warning)warning.remove();
        status.textContent='Применено ко всем фото';
      }).catch(function(err){
        status.textContent=err.message;
        status.classList.add('is-error');
      }).finally(function(){btn.disabled=false;select.disabled=false;});
    });
  }

  function init(){
    normalizeUploadSelect();
    var forms=Array.prototype.slice.call(document.querySelectorAll('.photo-color-form'));
    if(!forms.length) return;
    forms.forEach(bindSingle);
    addBulk(forms);
    addUnassignedWarning(forms);

    if(!document.getElementById('photo-color-ajax-style')){
      var style=document.createElement('style');
      style.id='photo-color-ajax-style';
      style.textContent='.photo-color-form{position:relative}.photo-color-status{display:inline-block;margin-top:5px;font-size:11px;color:#575E43;min-height:14px}.photo-color-status.is-error,.photo-color-bulk__status.is-error{color:#8B2F2F}.photo-color-warning{padding:10px 12px;margin:0 0 12px;border:1px solid #d8b27a;background:#fff8ea;color:#76521f;font-size:12px}.photo-color-bulk{display:grid;grid-template-columns:minmax(220px,1fr) minmax(170px,240px) auto minmax(120px,1fr);gap:10px;align-items:end;padding:14px 16px;margin:0 0 18px;background:#f3f4f4;border:1px solid #dfe3e3}.photo-color-bulk__label{font-size:12px;font-weight:600;align-self:center}.photo-color-bulk__status{font-size:11px;color:#575E43;align-self:center}@media(max-width:760px){.photo-color-bulk{grid-template-columns:1fr}.photo-color-bulk .btn{width:100%}}';
      document.head.appendChild(style);
    }
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',init);
  else init();
})();
