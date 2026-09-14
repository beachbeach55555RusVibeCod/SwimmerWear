(function(){
  'use strict';

  function formData(obj){
    var p=new URLSearchParams();
    Object.keys(obj).forEach(function(k){p.append(k,obj[k]);});
    return p;
  }
  function request(url,opts){
    return fetch(url,opts).then(function(r){return r.json().then(function(x){if(!r.ok||!x.ok)throw new Error(x.error||'Ошибка');return x;});});
  }
  function findPid(){
    var save=document.querySelector('form input[name="a"][value="save"]');
    if(!save)return 0;
    var form=save.closest('form'), pid=form&&form.querySelector('input[name="pid"]');
    return pid?parseInt(pid.value,10)||0:0;
  }
  function findColorsPanel(){
    var panels=document.querySelectorAll('.panel');
    for(var i=0;i<panels.length;i++){
      var h=panels[i].querySelector('h2');
      if(h&&(h.textContent||'').trim()==='Цвета товара')return panels[i];
    }
    return null;
  }

  var pid=findPid();
  if(!pid)return;
  var colorsPanel=findColorsPanel();
  if(!colorsPanel)return;

  var panel=document.createElement('div');
  panel.className='panel product-sizes-panel';
  panel.innerHTML='<h2 style="margin-top:0">Размеры товара</h2><p class="tag">Размеры применяются ко всем цветам этой карточки и автоматически появляются на складе. Можно добавить XXL, XXXL, «Универсальный» или свой размер.</p><div class="product-size-chips" data-size-chips></div><div class="product-size-presets"><button type="button" class="btn sm grey" data-preset="XXL">+ XXL</button><button type="button" class="btn sm grey" data-preset="XXXL">+ XXXL</button><button type="button" class="btn sm grey" data-preset="Универсальный">+ Универсальный</button></div><form class="row product-size-add" data-size-form><div style="flex:1"><label>Дополнительный размер</label><input name="size" maxlength="16" placeholder="например: XXL или Универсальный"></div><button class="btn" type="submit" style="align-self:end">Добавить размер</button></form><div class="product-size-status" data-size-status></div>';
  colorsPanel.insertAdjacentElement('afterend',panel);

  var style=document.createElement('style');
  style.textContent='.product-size-chips{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0}.product-size-chip{display:inline-flex;align-items:center;gap:8px;min-height:34px;padding:7px 10px;border:1px solid #cfd4d5;background:#fff;font-size:12px}.product-size-chip button{border:0;background:none;color:#8b9192;cursor:pointer;font-size:16px;line-height:1;padding:0}.product-size-chip button:hover{color:#963d3d}.product-size-presets{display:flex;gap:7px;flex-wrap:wrap;margin:0 0 14px}.product-size-add{margin-top:4px}.product-size-status{min-height:20px;margin-top:8px;font-size:12px;color:#576044}.product-size-status.err{color:#963d3d}@media(max-width:700px){.product-size-add{align-items:stretch;flex-direction:column}.product-size-add .btn{align-self:stretch!important}}';
  document.head.appendChild(style);

  var chips=panel.querySelector('[data-size-chips]');
  var status=panel.querySelector('[data-size-status]');
  var input=panel.querySelector('input[name="size"]');

  function setStatus(text,err){status.textContent=text||'';status.className='product-size-status'+(err?' err':'');}
  function render(sizes){
    chips.innerHTML='';
    (sizes||[]).forEach(function(size){
      var chip=document.createElement('span');
      chip.className='product-size-chip';
      var label=document.createElement('span'); label.textContent=size;
      var del=document.createElement('button'); del.type='button'; del.textContent='×'; del.title='Удалить размер '+size;
      del.addEventListener('click',function(){
        if(!confirm('Удалить размер «'+size+'» у всех цветов товара? Остатки этого размера будут удалены.'))return;
        setStatus('Удаляем…');
        request('product-size.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:formData({a:'delete',pid:pid,size:size}).toString()})
          .then(function(x){render(x.sizes);setStatus('Размер удалён');})
          .catch(function(e){setStatus(e.message,true);});
      });
      chip.appendChild(label); chip.appendChild(del); chips.appendChild(chip);
    });
  }
  function addSize(size){
    size=(size||'').trim(); if(!size)return;
    setStatus('Добавляем…');
    request('product-size.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:formData({a:'add',pid:pid,size:size}).toString()})
      .then(function(x){render(x.sizes);input.value='';setStatus('Размер добавлен. Он уже появился на складе.');})
      .catch(function(e){setStatus(e.message,true);});
  }

  panel.querySelector('[data-size-form]').addEventListener('submit',function(e){e.preventDefault();addSize(input.value);});
  panel.querySelectorAll('[data-preset]').forEach(function(btn){btn.addEventListener('click',function(){addSize(btn.getAttribute('data-preset'));});});

  request('product-size.php?pid='+encodeURIComponent(pid)).then(function(x){
    render(x.sizes);
    return request('product-size.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},body:formData({a:'sync',pid:pid}).toString()});
  }).then(function(){setStatus('');}).catch(function(e){setStatus(e.message,true);});
})();
