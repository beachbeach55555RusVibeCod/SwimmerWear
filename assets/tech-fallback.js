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
        <p class="lede">Трёхслойная мембрана не пропускает воду снаружи и помогает отводить пар изнутри.</p>\
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
          <div><b>3L MEMBRANE</b><p>Три слоя соединены в одно полотно.</p></div>\
          <div><b>10K / 10K</b><p>Водостойкость снаружи и отведение пара изнутри.</p></div>\
          <div><b>140 G/M² + DWR</b><p>Плотная верхняя ткань и водоотталкивающая обработка.</p></div>\
          <div><b>WELLSOFT</b><p>Мягкая внутренняя подкладка для тепла и комфорта.</p></div>\
        </div>\
      </div>\
    </div>';

  scenarios.parentNode.insertBefore(sec, scenarios.nextSibling);

  var css=document.createElement('style');
  css.textContent='.tech-fallback{background:#fff}.tech-fallback .tech-diagram{background:#ECEEEE;padding:30px;display:flex;align-items:center;justify-content:center;min-height:420px}.tech-fallback .tech-diagram svg{width:100%;height:auto}.tech-fallback .vapour path{animation:techPulse 2.2s ease-in-out infinite}.tech-fallback .vapour path:nth-child(2){animation-delay:.25s}.tech-fallback .vapour path:nth-child(3){animation-delay:.5s}@keyframes techPulse{0%,100%{opacity:.35;transform:translateY(4px)}50%{opacity:1;transform:translateY(-4px)}}@media(max-width:900px){.tech-fallback .tech-diagram{min-height:320px;padding:18px}}@media(prefers-reduced-motion:reduce){.tech-fallback .vapour path{animation:none}}';
  document.head.appendChild(css);
})();
