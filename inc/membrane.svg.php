<svg viewBox="0 0 900 520" role="img" aria-label="Схема работы мембраны SWIMMER">
  <defs>
    <marker id="arrowSteam" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
      <path d="M0 0 L10 5 L0 10 z" fill="#171B1C"/>
    </marker>
    <marker id="arrowRain" viewBox="0 0 10 10" refX="5" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
      <path d="M0 0 L10 5 L0 10 z" fill="#575E43"/>
    </marker>
    <pattern id="fabricGrid" width="12" height="12" patternUnits="userSpaceOnUse">
      <path d="M0 6H12M6 0V12" stroke="#B7BDBD" stroke-width="0.7" opacity=".55"/>
    </pattern>
  </defs>

  <style>
    .lbl{font-family:Inter,Arial,sans-serif;fill:#171B1C}
    .muted{font-family:Inter,Arial,sans-serif;fill:#727A7C}
    .steam{animation:steam 2.4s ease-in-out infinite}
    .steam.s2{animation-delay:.45s}.steam.s3{animation-delay:.9s}
    .rain{animation:rain 2.8s ease-in-out infinite}
    .rain.r2{animation-delay:.5s}.rain.r3{animation-delay:1s}
    @keyframes steam{0%{opacity:.15;transform:translateY(14px)}45%{opacity:1}100%{opacity:.05;transform:translateY(-18px)}}
    @keyframes rain{0%,100%{opacity:.35;transform:translateY(-5px)}50%{opacity:1;transform:translateY(7px)}}
    @media (prefers-reduced-motion:reduce){.steam,.rain{animation:none}}
  </style>

  <text class="muted" x="70" y="42" font-size="14" letter-spacing="2">СНАРУЖИ</text>
  <text class="muted" x="70" y="488" font-size="14" letter-spacing="2">ИЗНУТРИ</text>

  <g fill="none" stroke="#575E43" stroke-width="2">
    <path class="rain" d="M140 62c0 0 10 13 10 20a10 10 0 1 1-20 0c0-7 10-20 10-20z"/>
    <path class="rain r2" d="M270 52c0 0 10 13 10 20a10 10 0 1 1-20 0c0-7 10-20 10-20z"/>
    <path class="rain r3" d="M400 68c0 0 10 13 10 20a10 10 0 1 1-20 0c0-7 10-20 10-20z"/>
  </g>
  <path d="M505 78c60 0 95 12 132 44" fill="none" stroke="#575E43" stroke-width="1.7" marker-end="url(#arrowRain)"/>
  <text class="muted" x="650" y="116" font-size="15">вода скатывается с поверхности</text>

  <rect x="70" y="142" width="760" height="70" rx="3" fill="#E4E7E7"/>
  <rect x="70" y="142" width="760" height="70" rx="3" fill="url(#fabricGrid)"/>
  <text class="lbl" x="98" y="174" font-size="18" font-weight="600">ВЕРХНЯЯ ТКАНЬ · 140 g/m</text>
  <text class="muted" x="98" y="197" font-size="14">DWR-пропитка от дождя и снега</text>

  <rect x="70" y="224" width="760" height="100" rx="3" fill="#575E43"/>
  <text x="98" y="260" font-family="Inter,Arial,sans-serif" fill="#fff" font-size="18" font-weight="600">МЕМБРАНА · 10K / 10K</text>
  <text x="98" y="286" font-family="Inter,Arial,sans-serif" fill="#E5E8DF" font-size="14">не пропускает влагу снаружи · выводит пар изнутри</text>
  <g fill="#DDE2D5" opacity=".75">
    <circle cx="590" cy="251" r="3"/><circle cx="615" cy="279" r="2.5"/><circle cx="645" cy="248" r="2"/>
    <circle cx="675" cy="287" r="3"/><circle cx="708" cy="258" r="2.5"/><circle cx="742" cy="286" r="2"/>
  </g>

  <rect x="70" y="336" width="760" height="74" rx="3" fill="#F1F2F2"/>
  <path d="M70 370c45-30 90 30 135 0s90 30 135 0 90 30 135 0 90 30 135 0 90 30 135 0 90 30 125 0" fill="none" stroke="#C8CDCD" stroke-width="5" opacity=".7"/>
  <text class="lbl" x="98" y="368" font-size="18" font-weight="600">ПОДКЛАДКА · WELLSOFT</text>
  <text class="muted" x="98" y="392" font-size="14">мягкая и тёплая, как плед</text>

  <g fill="none" stroke="#171B1C" stroke-width="1.8" marker-end="url(#arrowSteam)">
    <path class="steam" d="M270 462C270 426 270 394 270 350C270 316 270 286 270 244"/>
    <path class="steam s2" d="M420 470C420 430 420 398 420 352C420 316 420 286 420 244"/>
    <path class="steam s3" d="M570 462C570 426 570 394 570 350C570 316 570 286 570 244"/>
  </g>
  <text class="muted" x="620" y="458" font-size="15">пар от тела выходит наружу</text>
</svg>
