(function(){
  'use strict';
  if(document.getElementById('tech')) return;
  var scenarios=document.getElementById('scenarios');
  if(!scenarios || !scenarios.parentNode) return;

  var sec=document.createElement('section');
  sec.id='tech';
  sec.className='tech-fallback';
  sec.innerHTML='\
    <div class="wrap">\
      <div class="sec-head">\
        <h2>Технологии и материалы</h2>\
        <p class="lede">Трёхслойная мембрана работает в обе стороны: не пропускает воду снаружи и выводит пар изнутри.</p>\
      </div>\
      <div class="tech-grid">\
        <div class="tech-diagram">\
          <svg viewBox="0 0 720 390" role="img" aria-label="Схема работы трёхслойной мембраны">\
            <defs><marker id="techArrow" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="6" markerHeight="6" orient="auto"><path d="M0 0L10 5L0 10z" fill="#575E43"/></marker></defs>\
            <g stroke="#575E43" stroke-width="2" fill="none"><path d="M120 28l-16 28"/><path d="M260 18l-16 28"/><path d="M430 28l-16 28"/><path d="M570 18l-16 28"/></g>\
            <text x="42" y="76" font-family="Inter,sans-serif" font-size="13" fill="#7E8688">дождь и снег остаются снаружи</text>\
            <rect x="40" y="92" width="640" height="48" rx="3" fill="#DDE1E1"/>\
            <text x="60" y="122" font-family="Inter,sans-serif" font-size="16" font-weight="600" fill="#191D1E">1 — ВЕРХ 140 g/m + DWR</text>\
            <rect x="40" y="140" width="640" height="72" rx="3" fill="#575E43"/>\
            <text x="60" y="172" font-family="Inter,sans-serif" font-size="16" font-weight="600" fill="#fff">2 — МЕМБРАНА 10K / 10K</text>\
            <text x="60" y="195" font-family="Inter,sans-serif" font-size="12" fill="#E4E8DE">вода не проходит внутрь, пар выходит наружу</text>\
            <rect x="40" y="212" width="640" height="52" rx="3" fill="#E7EAEA"/>\
            <text x="60" y="244" font-family="Inter,sans-serif" font-size="16" font-weight="600" fill="#191D1E">3 — ПОДКЛАДКА WELLSOFT</text>\
            <g class="vapour" stroke="#575E43" stroke-width="2" fill="none" marker-end="url(#techArrow)"><path d="M210 338V270"/><path d="M360 350V270"/><path d="M510 338V270"/></g>\
            <text x="238" y="374" font-family="Inter,sans-serif" font-size="13" fill="#7E8688">пар от тела уходит наружу</text>\
          </svg>\
        </div>\
        <div class="tech-list">\
          <article class="tech-item"><h3>3L MEMBRANE</h3><p>Три слоя соединены в одно полотно: верх, мембрана и подкладка не расходятся при движении.</p></article>\
          <article class="tech-item"><h3>10K / 10K</h3><p>Столб воды 10 000 мм и такая же паропроницаемость — держит ливень и не превращается в парник.</p></article>\
          <article class="tech-item"><h3>140 G/M²</h3><p>Плотная ткань верха: не рвётся о ветки и не парусит на ветру.</p></article>\
          <article class="tech-item"><h3>DWR</h3><p>Пропитка заставляет воду скатываться каплями. Обновляется стиркой со спецсредством.</p></article>\
          <article class="tech-item"><h3>WELLSOFT</h3><p>Подкладка с мягким ворсом — по ощущению ближе к пледу, чем к куртке.</p></article>\
        </div>\
      </div>\
    </div>';

  scenarios.parentNode.insertBefore(sec, scenarios.nextSibling);

  var css=document.createElement('style');
  css.textContent='.tech-fallback{background:#fff}.tech-fallback .tech-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);gap:48px;align-items:start}.tech-fallback .tech-diagram{background:#ECEEEE;padding:30px;display:flex;align-items:center;justify-content:center;min-height:420px}.tech-fallback .tech-diagram svg{width:100%;height:auto}.tech-fallback .tech-list{display:flex;flex-direction:column;gap:0;border-top:1px solid #D7DBDB}.tech-fallback .tech-list .tech-item{padding:18px 0 20px;border-bottom:1px solid #D7DBDB}.tech-fallback .tech-list .tech-item h3{margin:0 0 10px;font-family:"Inter Display","Inter",sans-serif;font-size:16px;line-height:1.1;font-weight:600;letter-spacing:.02em;text-transform:uppercase;color:#191D1E}.tech-fallback .tech-list .tech-item p{margin:0;max-width:360px;font-family:"Inter Regular","Inter",sans-serif;font-size:13px;line-height:1.55;color:#6F7778}.tech-fallback .vapour path{animation:techPulse 2.2s ease-in-out infinite}.tech-fallback .vapour path:nth-child(2){animation-delay:.25s}.tech-fallback .vapour path:nth-child(3){animation-delay:.5s}@keyframes techPulse{0%,100%{opacity:.35;transform:translateY(4px)}50%{opacity:1;transform:translateY(-4px)}}@media(max-width:900px){.tech-fallback .tech-grid{grid-template-columns:1fr;gap:28px}.tech-fallback .tech-diagram{min-height:320px;padding:18px}.tech-fallback .tech-list .tech-item p{max-width:none}}@media(prefers-reduced-motion:reduce){.tech-fallback .vapour path{animation:none}}';
  document.head.appendChild(css);
})();
