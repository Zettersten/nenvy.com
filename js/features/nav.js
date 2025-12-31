import { qs, qsa } from '../utils/dom.js';
import { hasGSAP } from '../utils/env.js';

export function initMobileNav({ reducedMotion }) {
  const toggle = qs('.nav-toggle');
  const panel = qs('#nav-panel');
  const closeBtn = qs('.nav-close');
  const panelInner = panel ? qs('.nav-panel-inner', panel) : null;
  const page = qs('#page');
  if (!toggle || !panel || !closeBtn || !panelInner) return;

  let lastFocused = null;
  const supportsPopover = typeof panel.showPopover === 'function';

  // If popover is supported, don't keep it permanently hidden.
  if (supportsPopover && panel.hasAttribute('hidden')) panel.removeAttribute('hidden');

  const focusablesSelector =
    'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

  const getFocusables = () =>
    qsa(focusablesSelector, panel).filter((el) => el instanceof HTMLElement);

  const open = () => {
    lastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    if (supportsPopover) panel.showPopover();
    else panel.hidden = false;
    document.body.classList.add('no-scroll');
    toggle.setAttribute('aria-expanded', 'true');
    if (page && 'inert' in page) page.inert = true;

    if (!reducedMotion && hasGSAP()) {
      const gsap = window.gsap;
      gsap.killTweensOf(panel);
      gsap.killTweensOf(panelInner);
      gsap.set(panel, { autoAlpha: 0 });
      gsap.set(panelInner, { y: 10, opacity: 0 });
      gsap.to(panel, { autoAlpha: 1, duration: 0.22, ease: 'power2.out' });
      gsap.to(panelInner, { y: 0, opacity: 1, duration: 0.5, ease: 'power3.out', delay: 0.04 });
      gsap.fromTo(
        qsa('.nav-panel-links a', panel),
        { y: 10, opacity: 0 },
        { y: 0, opacity: 1, duration: 0.55, stagger: 0.04, ease: 'power3.out', delay: 0.08 }
      );
    }

    window.setTimeout(() => {
      const focusables = getFocusables();
      (focusables[0] || closeBtn).focus();
    }, 0);
  };

  const close = () => {
    const finalize = () => {
      if (supportsPopover) panel.hidePopover();
      else panel.hidden = true;
      panel.style.opacity = '';
      panel.style.visibility = '';
      document.body.classList.remove('no-scroll');
      toggle.setAttribute('aria-expanded', 'false');
      if (page && 'inert' in page) page.inert = false;
      if (lastFocused) lastFocused.focus();
    };

    if (!reducedMotion && hasGSAP()) {
      const gsap = window.gsap;
      gsap.to(panelInner, { y: 8, opacity: 0, duration: 0.22, ease: 'power2.in' });
      gsap.to(panel, { autoAlpha: 0, duration: 0.22, ease: 'power2.in', onComplete: finalize });
      return;
    }

    finalize();
  };

  toggle.addEventListener('click', () => {
    const isOpen = supportsPopover ? panel.matches(':popover-open') : !panel.hidden;
    if (!isOpen) open();
    else close();
  });

  closeBtn.addEventListener('click', close);

  panel.addEventListener('click', (e) => {
    const target = e.target;
    if (!(target instanceof Node)) return;
    if (!panelInner.contains(target)) close();
  });

  // Keep state consistent when popover is closed by browser features.
  if (supportsPopover) {
    panel.addEventListener('toggle', () => {
      const isOpen = panel.matches(':popover-open');
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      document.body.classList.toggle('no-scroll', isOpen);
      if (page && 'inert' in page) page.inert = isOpen;
    });
  }

  qsa('a[href^="#"]', panel).forEach((a) => {
    a.addEventListener('click', () => close());
  });

  document.addEventListener('keydown', (e) => {
    const isOpen = supportsPopover ? panel.matches(':popover-open') : !panel.hidden;
    if (!isOpen) return;

    if (e.key === 'Escape') {
      e.preventDefault();
      close();
      return;
    }

    if (e.key === 'Tab') {
      const focusables = getFocusables();
      if (focusables.length === 0) return;
      const first = focusables[0];
      const last = focusables[focusables.length - 1];

      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  });
}

