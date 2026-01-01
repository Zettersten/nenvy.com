import { qsa, qs } from '../utils/dom.js';
import { hasGSAP, hasScrollTrigger, isCoarsePointer } from '../utils/env.js';
import { splitWords } from './split-words.js';

export function initMotion({ reducedMotion }) {
  if (reducedMotion) return;
  if (!hasGSAP()) return;

  const gsap = window.gsap;

  if (hasScrollTrigger()) {
    gsap.registerPlugin(window.ScrollTrigger);
  }

  // Hero: word-level headline + staged UI entrance
  const headline = qs('.hero-headline');
  if (headline && !headline.dataset.split) {
    splitWords(headline);
    headline.dataset.split = 'true';
  }

  const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
  tl.fromTo('.hero-content', { opacity: 0 }, { opacity: 1, duration: 0.3 });
  tl.from('.kicker', { opacity: 0, y: 14, duration: 0.7 }, '<');
  tl.from(
    '.hero-headline .w',
    {
      opacity: 0,
      yPercent: 70,
      rotateX: 75,
      transformOrigin: '50% 100%',
      duration: 1.0,
      stagger: 0.018,
    },
    '-=0.25'
  );
  tl.from('.hero-subhead', { opacity: 0, y: 12, duration: 0.7 }, '-=0.55');
  tl.from('.hero-ctas .btn', { opacity: 0, y: 10, duration: 0.65, stagger: 0.08 }, '-=0.55');
  tl.from('.hero-metrics .metric', { opacity: 0, y: 10, duration: 0.65, stagger: 0.06 }, '-=0.5');

  // Scroll reveals
  qsa('[data-reveal]').forEach((el) => {
    gsap.to(el, {
      opacity: 1,
      y: 0,
      duration: 0.9,
      ease: 'power3.out',
      scrollTrigger: hasScrollTrigger()
        ? {
            trigger: el,
            start: 'top 86%',
          }
        : undefined,
    });
  });

  if (!hasScrollTrigger()) return;
  const ScrollTrigger = window.ScrollTrigger;

  // Marquee: scrubbed speed bias on scroll (JS overrides CSS fallback)
  const marquee = qs('[data-marquee]');
  if (marquee) {
    marquee.style.animation = 'none';
    const tween = gsap.to(marquee, {
      xPercent: -50,
      duration: 18,
      ease: 'none',
      repeat: -1,
    });

    ScrollTrigger.create({
      trigger: '.marquee',
      start: 'top bottom',
      end: 'bottom top',
      onUpdate(self) {
        const v = Math.min(2600, Math.abs(self.getVelocity()));
        const boost = 1 + v / 2200;
        const dir = self.direction === 1 ? 1 : 0.85;
        tween.timeScale(Math.max(0.8, Math.min(2.4, boost * dir)));
      },
    });
  }

  // Process: active step state
  const steps = qsa('.step');
  steps.forEach((step) => {
    ScrollTrigger.create({
      trigger: step,
      start: 'top 62%',
      end: 'bottom 48%',
      onEnter: () => step.classList.add('is-active'),
      onEnterBack: () => step.classList.add('is-active'),
      onLeave: () => step.classList.remove('is-active'),
      onLeaveBack: () => step.classList.remove('is-active'),
    });
  });
}

