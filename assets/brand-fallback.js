(function(){
  'use strict';
  if(document.querySelector('.brand-story'))return;
  var reviews=document.querySelector('.reviews-showcase');
  if(!reviews || !reviews.parentNode)return;

  var sec=document.createElement('section');
  sec.className='brand-story';
  sec.id='brand-story';
  sec.innerHTML='<div class="wrap brand-story__copy"><div class="brand-story__label">О БРЕНДЕ</div><h2>Мы просто всегда любили воду</h2><div class="brand-story__text"><p>SWIMMER вырос из простого желания проводить больше времени у воды — не думая о ветре, сырости и переменчивой погоде.</p><p>Мы делаем вещи спокойными по характеру и практичными по сути. В основе — защита от дождя и ветра, свободная посадка, тёплая мягкая подкладка и материалы, которые рассчитаны не на витрину, а на реальное использование.</p><p>Для нас продукт — это не сезонная декорация, а надёжная вещь для поездок, берега, лодки, дачи и долгих прогулок. Конструкцию и материалы подбираем так, чтобы парку было удобно носить, хранить и брать с собой.</p><p>Производство строится вокруг понятных решений: мембранная ткань, проклеенные швы, функциональные детали и контроль качества на каждом этапе.</p></div><a class="brand-story__link" href="#product">Выбрать надежную вещь для отдыха на природе.</a></div><div class="brand-story__media"><img src="https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&q=88&w=2200" alt="Река и природа" loading="lazy"></div>';
  reviews.parentNode.insertBefore(sec,reviews.nextSibling);

  var style=document.createElement('style');
  style.textContent='.brand-story{padding:96px 0 0;background:#fff}.brand-story__copy{max-width:980px}.brand-story__label{font-size:10px;letter-spacing:.16em;color:#777f80;margin-bottom:22px}.brand-story h2{margin:0 0 36px;font-size:clamp(34px,4.5vw,58px);line-height:1;letter-spacing:-.04em;max-width:12ch}.brand-story__text{max-width:720px;margin-left:auto}.brand-story__text p{margin:0 0 18px;font-size:15px;line-height:1.7;color:#4e5556}.brand-story__link{display:inline-block;margin:8px 0 52px;margin-left:calc(100% - 720px);font-size:14px;font-weight:600;color:#171b1c;text-decoration:underline;text-underline-offset:4px}.brand-story__media{width:100%;height:min(76vh,760px);overflow:hidden;background:#dfe4e4}.brand-story__media img{width:100%;height:100%;object-fit:cover;display:block}@media(max-width:900px){.brand-story{padding-top:72px}.brand-story__text{margin-left:0}.brand-story__link{margin-left:0}.brand-story__media{height:58vh;min-height:420px}}@media(max-width:560px){.brand-story h2{max-width:10ch}.brand-story__text p{font-size:14px}.brand-story__media{height:50vh;min-height:360px}}';
  document.head.appendChild(style);
})();