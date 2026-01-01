import { qsa } from '../utils/dom.js';

export function initSmoothScroll({ reducedMotion }) {
  qsa('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', (e) => {
      const href = anchor.getAttribute('href');
      if (!href || href === '#') return;
      const target = document.querySelector(href);
      if (!target) return;

      e.preventDefault();
      const behavior = reducedMotion ? 'auto' : 'smooth';
      target.scrollIntoView({ behavior, block: 'start' });
      history.pushState(null, '', href);

      // A11y: move focus to the destination so keyboard + SR users
      // understand that context changed after in-page navigation.
      const focusTarget = target instanceof HTMLElement ? target : null;
      if (focusTarget) {
        const hadTabIndex = focusTarget.hasAttribute('tabindex');
        if (!hadTabIndex) focusTarget.setAttribute('tabindex', '-1');
        focusTarget.focus({ preventScroll: true });
        if (!hadTabIndex) {
          window.setTimeout(() => focusTarget.removeAttribute('tabindex'), 0);
        }
      }
    });
  });
}

