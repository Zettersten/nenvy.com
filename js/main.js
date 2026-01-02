import { prefersReducedMotion, hasGSAP } from './utils/env.js';
import { initSmoothScroll } from './features/smooth-scroll.js';
import { initCopyButtons } from './features/copy.js';
import { initMagneticButtons } from './features/magnetic.js';
import { initThreeHero } from './features/three-hero.js';
import { initMobileNav } from './features/nav.js';
import { initPaypalSubscribe } from './features/paypal-subscribe.js';
import { initPreloaderShader } from './features/preloader-shader.js';
import { initFooterTetris } from './features/footer-tetris.js';
import { initMotion } from './motion/gsap-motion.js';

document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
  const reducedMotion = prefersReducedMotion();
  const preloader = initPreloaderShader({ reducedMotion });

  // Only hide reveals when GSAP is present; otherwise keep content visible.
  if (!reducedMotion && hasGSAP()) {
    document.documentElement.classList.add('js-motion');
  }

  initSmoothScroll({ reducedMotion });
  initCopyButtons();
  initMagneticButtons({ reducedMotion });
  initMobileNav({ reducedMotion });
  initPaypalSubscribe({ reducedMotion });
  initFooterTetris({ reducedMotion });

  // Heavier features: wait until external scripts finish loading.
  // (GSAP/Three are loaded as deferred classic scripts.)
  const startHeavy = () => {
    const three = initThreeHero({ reducedMotion, preloader });
    initMotion({ reducedMotion, preloader });

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) three.stop();
      else three.start();
    });
  };

  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(startHeavy, { timeout: 2000 });
  } else {
    window.addEventListener('load', startHeavy, { once: true });
  }
});

