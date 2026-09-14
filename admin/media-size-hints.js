(function(){
  'use strict';

  function getSpec(title){
    title=(title||'').trim().toLowerCase();
    if(title.indexOf('слайд')===0) return {w:1920,h:1200,label:'1920 × 1200 px · горизонтальное',video:true,videoLabel:'1920 × 1200 px · MP4/WebM · горизонтальное · без звука · короткий зацикленный ролик'};
    if(title.indexOf('кадр')===0) return {w:1600,h:1200,label:'1600 × 1200 px · горизонтальное'};
    if(title.indexOf('деталь')===0) return {w:1600,h:1200,label:'1600 × 1200 px · горизонтальное'};
    if(title.indexOf('фото комплекта')===0) return {w:1400,h:1400,label:'1400 × 1400 px · квадрат'};
    if(title.indexOf('большое фото / видео')===0) return {w:2200,h:1400,label:'2200 × 1400 px · горизонтальное',video:true,videoLabel:'2200 × 1400 px · MP4/WebM · горизонтальное · без звука'};
    return {w:1600,h:1200,label:'1600 × 1200 px'};
  }

  function setStatus(box,text,type){
    var el=box.querySelector('.visual-dimension-status');
    if(!el){
      el=document.createElement('div');
      el.className='visual-dimension-status';
      var row=box.querySelector('.visual-upload-row');
      if(row) row.insertAdjacentElement('afterend',el);
      else box.querySelector('.visual-fields').appendChild(el);
    }
    el.className='visual-dimension-status '+(type||'');
    el.textContent=text||'';
    el.style.display=text?'block':'none';
  }

  function specForBox(box){
    var label=box.querySelector('.visual-fields label');
    return getSpec(label?label.textContent:'');
  }

  function compareSize(box,prefix,w,h,spec,extra){
    var targetRatio=spec.w/spec.h;
    var actualRatio=w/h;
    var ratioDiff=Math.abs(actualRatio-targetRatio)/targetRatio;
    extra=extra||'';
    if(ratioDiff>0.03){
      setStatus(box,'⚠ '+prefix+' '+w+' × '+h+' px'+extra+'. Пропорции отличаются от рекомендуемых '+spec.w+' × '+spec.h+' — на сайте края могут обрезаться.','warn');
    }else if(w<spec.w||h<spec.h){
      setStatus(box,'⚠ '+prefix+' '+w+' × '+h+' px'+extra+'. Пропорции подходят, но размер меньше рекомендуемого '+spec.w+' × '+spec.h+' — качество может быть ниже.','warn');
    }else{
      setStatus(box,'✓ '+prefix+' подходит: '+w+' × '+h+' px'+extra+'.','ok');
    }
  }

  function checkVideo(input,file,box,spec){
    if(!spec.video){
      setStatus(box,'Видео выбрано. Для этого блока отдельные требования к видео не заданы.','info');
      return;
    }
    var url=URL.createObjectURL(file);
    var video=document.createElement('video');
    video.preload='metadata';
    video.onloadedmetadata=function(){
      var w=video.videoWidth,h=video.videoHeight;
      var duration=isFinite(video.duration)?' · '+video.duration.toFixed(1).replace('.0','')+' сек':'';
      URL.revokeObjectURL(url);
      compareSize(box,'Видео',w,h,spec,duration);
    };
    video.onerror=function(){URL.revokeObjectURL(url);setStatus(box,'Не удалось проверить размер кадра этого видео. Используй MP4 или WebM.','warn');};
    video.src=url;
  }

  function checkMedia(input){
    var box=input.closest('.visual-media');
    if(!box)return;
    var file=input.files&&input.files[0];
    if(!file){setStatus(box,'','');return;}
    var spec=specForBox(box);

    if(file.type.indexOf('video/')===0){
      checkVideo(input,file,box,spec);
      return;
    }
    if(file.type.indexOf('image/')!==0){setStatus(box,'','');return;}

    var url=URL.createObjectURL(file);
    var img=new Image();
    img.onload=function(){
      var w=img.naturalWidth,h=img.naturalHeight;
      URL.revokeObjectURL(url);
      compareSize(box,'Фото',w,h,spec,'');
    };
    img.onerror=function(){URL.revokeObjectURL(url);setStatus(box,'Не удалось проверить размер этого изображения.','warn');};
    img.src=url;
  }

  function applyHints(){
    document.querySelectorAll('.visual-media').forEach(function(box){
      if(!box.querySelector('.visual-size-hint')){
        var fields=box.querySelector('.visual-fields');
        if(fields){
          var label=fields.querySelector('label');
          if(label){
            var spec=getSpec(label.textContent);
            var hint=document.createElement('div');
            hint.className='visual-size-hint';
            hint.innerHTML='<div><b>Фото:</b> '+spec.label+'</div>'+(spec.video?'<div><b>Видео:</b> '+spec.videoLabel+'</div>':'');
            label.insertAdjacentElement('afterend',hint);
          }
        }
      }
    });

    if(!document.getElementById('visual-size-hints-style')){
      var style=document.createElement('style');
      style.id='visual-size-hints-style';
      style.textContent='.visual-size-hint{display:inline-grid;gap:3px;margin:0 0 8px;padding:6px 8px;border:1px solid #d9dddd;background:#f6f7f7;color:#5d6567;font-size:11px;line-height:1.35;border-radius:2px;font-weight:500}.visual-size-hint b{color:#3f4748;font-weight:700}.visual-dimension-status{display:none;margin:8px 0 10px;padding:8px 10px;border:1px solid #d9dddd;background:#f6f7f7;font-size:12px;line-height:1.4}.visual-dimension-status.ok{border-color:#b9c9b7;background:#f2f7f1;color:#395036}.visual-dimension-status.warn{border-color:#d9c59d;background:#fbf7ee;color:#705927}.visual-dimension-status.info{color:#5d6567}';
      document.head.appendChild(style);
    }
  }

  document.addEventListener('change',function(e){
    var input=e.target.closest?e.target.closest('.visual-file'):null;
    if(input)checkMedia(input);
  });

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',applyHints);
  else applyHints();
})();
