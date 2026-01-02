import { qs } from '../utils/dom.js';

function toSafeCssUrl(input) {
  if (!input) return null;

  try {
    const u = new URL(input, window.location.href);
    if (u.protocol !== 'http:' && u.protocol !== 'https:') return null;
    return `url("${u.href.replace(/"/g, '%22')}")`;
  } catch {
    return null;
  }
}

function createCartridgeEl({ labelUrl } = {}) {
  const root = document.createElement('div');
  root.className = 'game-cartridge';
  root.setAttribute('data-game-cartridge', '');
  root.setAttribute('aria-hidden', 'true');

  const safeUrl = toSafeCssUrl(labelUrl);
  if (safeUrl) {
    root.setAttribute('data-label-url', '');
    root.style.setProperty('--gc-label-url', safeUrl);
  }

  root.innerHTML = `
    <div class="gc-case">
      <figure class="gc-front">
        <figure class="gc-base gc-tall"></figure>
        <figure class="gc-base gc-wide"></figure>
        <figure class="gc-linebase"></figure>
        <figure class="gc-line gc-one"></figure>
        <figure class="gc-line gc-two"></figure>
        <figure class="gc-line gc-three"></figure>
        <figure class="gc-line gc-four"></figure>
        <figure class="gc-leftindent"></figure>
        <figure class="gc-rightindent"></figure>
        <figure class="gc-oval"></figure>
        <figure class="gc-label"></figure>
      </figure>

      <figure class="gc-back">
        <figure class="gc-base gc-tall"></figure>
        <figure class="gc-base gc-wide"></figure>
        <figure class="gc-line gc-one"></figure>
        <figure class="gc-line gc-two"></figure>
        <figure class="gc-line gc-three"></figure>
        <figure class="gc-line gc-four"></figure>
      </figure>

      <figure class="gc-rightside">
        <figure class="gc-top"></figure>
        <figure class="gc-bottom"></figure>
        <figure class="gc-line gc-one"></figure>
        <figure class="gc-ceiling gc-one"></figure>
        <figure class="gc-floor gc-one"></figure>
        <figure class="gc-line gc-two"></figure>
        <figure class="gc-ceiling gc-two"></figure>
        <figure class="gc-floor gc-two"></figure>
        <figure class="gc-line gc-three"></figure>
        <figure class="gc-ceiling gc-three"></figure>
        <figure class="gc-floor gc-three"></figure>
        <figure class="gc-line gc-four"></figure>
        <figure class="gc-ceiling gc-four"></figure>
        <figure class="gc-floor gc-four"></figure>
      </figure>

      <figure class="gc-leftside">
        <figure class="gc-bottom"></figure>
        <figure class="gc-line gc-one"></figure>
        <figure class="gc-ceiling gc-one"></figure>
        <figure class="gc-floor gc-one"></figure>
        <figure class="gc-line gc-two"></figure>
        <figure class="gc-ceiling gc-two"></figure>
        <figure class="gc-floor gc-two"></figure>
        <figure class="gc-line gc-three"></figure>
        <figure class="gc-ceiling gc-three"></figure>
        <figure class="gc-floor gc-three"></figure>
        <figure class="gc-line gc-four"></figure>
        <figure class="gc-ceiling gc-four"></figure>
        <figure class="gc-floor gc-four"></figure>
      </figure>

      <figure class="gc-topside">
        <figure class="gc-left"></figure>
        <figure class="gc-right"></figure>
      </figure>

      <figure class="gc-bottomside"></figure>
    </div>
  `;

  return root;
}

export function initGameCartridge({ reducedMotion } = {}) {
  const mount = qs('[data-game-cartridge-mount]') || qs('#page');
  if (!mount) return;
  if (mount.querySelector('[data-game-cartridge]')) return;

  const labelUrl = mount.getAttribute('data-game-cartridge-label') || '';
  const el = createCartridgeEl({ labelUrl });
  el.dataset.paused = 'true';
  mount.appendChild(el);

  if (reducedMotion) return;
  if (!('IntersectionObserver' in window)) return;

  const io = new IntersectionObserver(
    (entries) => {
      const [entry] = entries;
      el.dataset.paused = entry.isIntersecting ? 'false' : 'true';
    },
    { root: null, threshold: 0.15 }
  );

  io.observe(el);
}

