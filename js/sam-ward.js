import { prefersReducedMotion } from './utils/env.js';
import { initSmoothScroll } from './features/smooth-scroll.js';
import { initMobileNav } from './features/nav.js';
import { initPaypalSubscribe } from './features/paypal-subscribe.js';

document.documentElement.classList.add('js');

document.addEventListener('DOMContentLoaded', () => {
  const reducedMotion = prefersReducedMotion();

  initSmoothScroll({ reducedMotion });
  initMobileNav({ reducedMotion });
  initPaypalSubscribe({ reducedMotion });
});
