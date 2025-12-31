export function prefersReducedMotion() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

export function isCoarsePointer() {
  return window.matchMedia('(pointer: coarse)').matches;
}

export function hasGSAP() {
  return typeof window.gsap !== 'undefined';
}

export function hasScrollTrigger() {
  return typeof window.ScrollTrigger !== 'undefined';
}

export function hasThree() {
  return typeof window.THREE !== 'undefined';
}

