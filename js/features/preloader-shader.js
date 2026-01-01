export function initPreloaderShader({ reducedMotion }) {
  const wrap = document.querySelector('[data-preloader]');
  if (!wrap) return { reveal() {} };

  const root = document.documentElement;
  root.classList.add('is-loading');

  // This module is DOM-only. The shader is rendered on the hero `#canvas`
  // so we keep a single WebGL context for performance + reliability.

  let done = false;
  let resolveDone = null;
  const donePromise = new Promise((resolve) => (resolveDone = resolve));

  const finish = () => {
    if (done) return donePromise;
    done = true;
    wrap.classList.add('is-done');
    root.classList.remove('is-loading');
    window.setTimeout(() => wrap.remove(), 700);
    if (resolveDone) resolveDone();
    return donePromise;
  };

  // Safety: never block the page forever.
  window.setTimeout(() => finish(), 8000);

  return {
    reveal: () => {
      if (reducedMotion) return finish();
      return finish();
    },
  };
}

