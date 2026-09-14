(function(){
  'use strict';

  var ICONS={
    telegram:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 4L3.8 10.6c-1 .4-1 1.1-.2 1.4l4.4 1.4 1.7 5.1c.2.6.4.8.8.8.3 0 .5-.1.8-.4l2.4-2.3 4.9 3.6c.9.5 1.6.2 1.8-.9L23 5.2C23.2 4.1 22.6 3.5 21 4zM9.1 13l8.7-5.5c.4-.2.8-.1.5.2l-7.2 6.5-.3 3.1L9.1 13z"/></svg>',
    vk:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.2 6.6h3.5c.3 0 .5.1.6.5.7 2.1 1.8 4 3.3 5.7.3.3.5.4.7.3.3-.1.4-.4.4-.8V8.4c0-.7-.2-1.1-.7-1.4-.2-.1-.2-.3.1-.4.7-.4 1.9-.5 3.5-.4.8.1 1.1.5 1.1 1.4v4.5c0 .5.1.8.4.9.2.1.5 0 .8-.3 1.5-1.7 2.6-3.6 3.2-5.7.1-.3.3-.5.7-.5h3.2c.5 0 .8.1.9.4.1.3 0 .7-.2 1.2-.8 1.7-1.9 3.3-3.2 4.8-.4.5-.4.8 0 1.3 1.6 1.5 2.9 3.1 4 4.8.3.5.4.9.2 1.2-.2.3-.5.4-1 .4h-3.6c-.4 0-.7-.1-1-.4l-2.4-2.5c-.3-.3-.6-.4-.8-.3-.3.1-.4.4-.4.9v1.5c0 .6-.3.8-.9.8h-1.6c-2 0-3.8-.8-5.4-2.5-2-2-3.6-4.9-4.8-8.7-.2-.5-.2-.9 0-1.2.2-.4.5-.5 1-.5z"/></svg>',
    instagram:'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.3" cy="6.8" r="1" class="fill-dot"/></svg>',
    youtube:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 12c0-2.4-.2-4-.5-4.9-.3-.8-1-1.5-1.8-1.8C18.5 5 15.9 4.8 12 4.8S5.5 5 4.3 5.3C3.5 5.6 2.8 6.3 2.5 7.1 2.2 8 2 9.6 2 12s.2 4 .5 4.9c.3.8 1 1.5 1.8 1.8 1.2.3 3.8.5 7.7.5s6.5-.2 7.7-.5c.8-.3 1.5-1 1.8-1.8.3-.9.5-2.5.5-4.9z"/><path d="M10 8.8l5 3.2-5 3.2V8.8z" class="play"/></svg>',
    whatsapp:'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.3 3.7A11 11 0 0 0 3 17l-1.4 5.1L6.8 20A11 11 0 0 0 20.3 3.7z"/><path d="M8.5 7.6c.2-.5.4-.5.8-.5h.6c.2 0 .4.1.5.4l1 2.3c.1.3.1.5-.1.7l-.7.8c-.2.2-.2.4-.1.6.6 1.2 1.5 2.1 2.7 2.8.2.1.4.1.6-.1l.9-1.1c.2-.2.4-.3.7-.2l2.3 1.1c.3.1.4.3.4.6 0 .7-.3 1.5-.9 2-.6.6-1.5.9-2.4.9-1.2 0-2.8-.6-4.7-1.8-2.1-1.3-3.6-3-4.4-5-.5-1.1-.6-2.1-.2-3 .2-.3.6-.8 1-1.5z" class="phone"/></svg>'
  };
  var NAMES={telegram:'Telegram',vk:'ВКонтакте',instagram:'Instagram',youtube:'YouTube',whatsapp:'WhatsApp'};

  function safeUrl(value){
    value=(value||'').trim();
    return /^https?:\/\//i.test(value)?value:'';
  }
  function esc(s){return String(s||'').replace(/[&<>"']/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];});}
  function render(data){
    var footer=document.querySelector('footer'); if(!footer)return;
    var target=footer.querySelector('.footer-spec__contacts')||footer.querySelector('.brand-page-footer-contacts');
    if(!target){target=document.createElement('div');target.className='footer-runtime-contacts';var wrap=footer.querySelector('.wrap');if(wrap)wrap.appendChild(target);else footer.appendChild(target);}
    target.classList.add('footer-runtime-contacts');
    var phone=(data.phone||'').trim(), email=(data.email||'').trim(), address=(data.address||'').trim();
    var html='<div class="footer-runtime-contacts__data">';
    if(address)html+='<span>'+esc(address)+'</span>';
    if(phone)html+='<a href="tel:'+esc(phone.replace(/[^+\d]/g,''))+'">'+esc(phone)+'</a>';
    if(email)html+='<a href="mailto:'+esc(email)+'">'+esc(email)+'</a>';
    html+='</div><div class="footer-socials" aria-label="Социальные сети">';
    Object.keys(NAMES).forEach(function(key){
      var url=safeUrl(data.socials&&data.socials[key]);
      if(!url)return;
      html+='<a href="'+esc(url)+'" target="_blank" rel="noopener noreferrer" aria-label="'+NAMES[key]+'" title="'+NAMES[key]+'">'+ICONS[key]+'</a>';
    });
    html+='</div>';
    target.innerHTML=html;
  }

  if(!document.getElementById('footer-socials-style')){
    var style=document.createElement('style');
    style.id='footer-socials-style';
    style.textContent='.footer-runtime-contacts{display:flex;flex-direction:column;gap:14px;align-items:flex-start;min-width:210px}.footer-runtime-contacts__data{display:flex;flex-direction:column;gap:7px;font-size:12px;line-height:1.45;color:#cfd4d4}.footer-runtime-contacts__data a{color:inherit;text-decoration:none}.footer-runtime-contacts__data a:hover{text-decoration:underline}.footer-socials{display:flex;gap:9px;align-items:center;flex-wrap:wrap}.footer-socials a{width:34px;height:34px;border:1px solid rgba(255,255,255,.28);display:grid;place-items:center;color:#fff;text-decoration:none;transition:.18s ease}.footer-socials a:hover{background:#fff;color:#191d1e;border-color:#fff}.footer-socials svg{width:18px;height:18px;fill:currentColor;stroke:currentColor;stroke-width:1.7}.footer-socials svg rect,.footer-socials svg circle{fill:none}.footer-socials .fill-dot,.footer-socials .play,.footer-socials .phone{fill:currentColor;stroke:none}@media(max-width:700px){.footer-runtime-contacts{min-width:0}}';
    document.head.appendChild(style);
  }

  fetch('/footer-data.php',{credentials:'same-origin',cache:'no-store'})
    .then(function(r){if(!r.ok)throw new Error('footer data');return r.json();})
    .then(render)
    .catch(function(){});
})();
