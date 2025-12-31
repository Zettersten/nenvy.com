import { qsa } from '../utils/dom.js';
import { isCoarsePointer } from '../utils/env.js';

export function initMagneticButtons({ reducedMotion }) {
  if (reducedMotion) return;
  if (isCoarsePointer()) return;

  const items = qsa('.magnetic');
  if (items.length === 0) return;

  items.forEach((el) => {
    let raf = null;

    const onMove = (e) => {
      const rect = el.getBoundingClientRect();
      const relX = (e.clientX - rect.left) / rect.width - 0.5;
      const relY = (e.clientY - rect.top) / rect.height - 0.5;
      const dx = relX * 10;
      const dy = relY * 8;

      if (raf) cancelAnimationFrame(raf);
      raf = requestAnimationFrame(() => {
        el.style.transform = `translate3d(${dx.toFixed(2)}px, ${dy.toFixed(2)}px, 0)`;
      });
    };

    const onLeave = () => {
      if (raf) cancelAnimationFrame(raf);
      el.style.transform = '';
    };

    el.addEventListener('mousemove', onMove, { passive: true });
    el.addEventListener('mouseleave', onLeave, { passive: true });
  });
}

