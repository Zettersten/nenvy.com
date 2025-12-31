import { isCoarsePointer } from '../utils/env.js';

export function initCursorGlow({ reducedMotion }) {
  if (reducedMotion) return;
  if (isCoarsePointer()) return;

  const glow = document.createElement('div');
  glow.className = 'cursor-glow';
  glow.setAttribute('aria-hidden', 'true');
  document.body.appendChild(glow);

  let x = -9999;
  let y = -9999;
  let tx = x;
  let ty = y;
  let raf = null;

  const onMove = (e) => {
    x = e.clientX;
    y = e.clientY;
    if (raf) return;
    raf = requestAnimationFrame(() => {
      raf = null;
      tx += (x - tx) * 0.14;
      ty += (y - ty) * 0.14;
      glow.style.transform = `translate3d(${(tx - 280).toFixed(1)}px, ${(ty - 280).toFixed(1)}px, 0)`;
    });
  };

  window.addEventListener('mousemove', onMove, { passive: true });
}

