import { prefersReducedMotion, hasGSAP } from './utils/env.js';
import { initCursorGlow } from './features/cursor-glow.js';
import { initSmoothScroll } from './features/smooth-scroll.js';
import { initCopyButtons } from './features/copy.js';
import { initMagneticButtons } from './features/magnetic.js';
import { initThreeHero } from './features/three-hero.js';
import { initMotion } from './motion/gsap-motion.js';

document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
  const reducedMotion = prefersReducedMotion();

  // Only hide reveals when GSAP is present; otherwise keep content visible.
  if (!reducedMotion && hasGSAP()) {
    document.documentElement.classList.add('js-motion');
  }

  initCursorGlow({ reducedMotion });
  initSmoothScroll({ reducedMotion });
  initCopyButtons();
  initMagneticButtons({ reducedMotion });

  const three = initThreeHero({ reducedMotion });

  initMotion({ reducedMotion });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) three.stop();
    else three.start();
  });
});

