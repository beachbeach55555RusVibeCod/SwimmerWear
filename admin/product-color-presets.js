(function(){
  'use strict';
  document.addEventListener('DOMContentLoaded',function(){
    var params=new URLSearchParams(window.location.search);
    if(params.get('edit') && !document.querySelector('.product-card-back')){
      var h1=document.querySelector('main h1');
      if(h1){
        var back=document.createElement('a');
        back.href='product-cards.php';
        back.className='btn grey product-card-back';
        back.textContent='← К списку карточек';
        h1.insertAdjacentElement('beforebegin',back);
      }
      var backStyle=document.createElement('style');
      backStyle.textContent='.product-card-back{display:inline-flex;align-items:center;margin:0 0 14px;text-decoration:none}';
      document.head.appendChild(backStyle);
    }

    var form=null,input=null;
    Array.prototype.slice.call(document.querySelectorAll('form')).forEach(function(f){
      var i=f.querySelector('input[name="color"]');
      var a=f.querySelector('input[name="a"][value="color_add"]');
      if(i&&a){form=f;input=i;}
    });
    if(!form||!input)return;

    var presets=[
      {name:'хаки',hex:'#575E43'},
      {name:'чёрный',hex:'#22262A'},
      {name:'синий',hex:'#41546B'}
    ];
    var current={};
    document.querySelectorAll('.color-chip').forEach(function(el){
      var t=(el.textContent||'').replace('×','').trim().toLowerCase();
      if(t)current[t]=1;
    });

    var wrap=document.createElement('div');
    wrap.className='admin-color-presets';
    var label=document.createElement('div');
    label.className='admin-color-presets__label';
    label.textContent='Выберите цвет';
    wrap.appendChild(label);

    presets.forEach(function(p){
      var b=document.createElement('button');
      b.type='button';
      b.className='admin-color-preset'+(current[p.name]?' is-added':'');
      b.innerHTML='<span class="admin-color-preset__dot" style="background:'+p.hex+'"></span><span>'+p.name+'</span>'+(current[p.name]?'<span class="admin-color-preset__check">✓</span>':'');
      if(current[p.name]){
        b.disabled=true;
        b.title='Этот цвет уже добавлен';
      }else{
        b.addEventListener('click',function(){
          input.value=p.name;
          if(form.requestSubmit)form.requestSubmit();else form.submit();
        });
      }
      wrap.appendChild(b);
    });

    var field=input.closest('div');
    if(field)field.insertBefore(wrap,input);
    else form.insertBefore(wrap,form.firstChild);

    var style=document.createElement('style');
    style.textContent='.admin-color-presets{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 12px}.admin-color-presets__label{width:100%;font-size:12px;font-weight:600;color:#606768;margin-bottom:2px}.admin-color-preset{display:inline-flex;align-items:center;gap:7px;min-height:34px;padding:7px 11px;border:1px solid #cfd4d5;background:#fff;color:#191d1e;border-radius:2px;font:inherit;font-size:12px;line-height:1;cursor:pointer}.admin-color-preset:hover{border-color:#191d1e}.admin-color-preset__dot{width:10px;height:10px;border-radius:50%;border:1px solid rgba(0,0,0,.15);flex:0 0 10px}.admin-color-preset.is-added{background:#f1f3f3;border-color:#aeb4b5;color:#7a8081;cursor:default}.admin-color-preset__check{margin-left:2px;font-weight:700}.admin-color-preset:disabled{opacity:1}';
    document.head.appendChild(style);
  });
})();
