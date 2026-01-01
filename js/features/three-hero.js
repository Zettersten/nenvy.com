import { isCoarsePointer } from '../utils/env.js';

function clamp01(v) {
  return Math.max(0, Math.min(1, v));
}

function easeInOutCubic(t) {
  return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
}

function randomColors(count) {
  return new Array(count)
    .fill(0)
    .map(() => `#${Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0')}`);
}

function initTubes2D({ canvas, hero, preloader }) {
  const ctx = canvas.getContext('2d', { alpha: true, desynchronized: true });
  if (!ctx) {
    if (preloader && typeof preloader.reveal === 'function') preloader.reveal();
    return { stop() {}, start() {} };
  }

  let tubeColors = ['#f967fb', '#53bc28', '#6958d5'];
  const offsets = [
    { x: 0.0, y: 0.0, stiffness: 0.18, lw: 2.8 },
    { x: -20, y: 11, stiffness: 0.15, lw: 2.4 },
    { x: 16, y: -12, stiffness: 0.13, lw: 2.2 },
  ];

  const worldTarget = { x: window.innerWidth * 0.5, y: window.innerHeight * 0.45 };
  const headBase = { x: worldTarget.x, y: worldTarget.y };

  const makeTrail = (i) => {
    const pts = new Array(26).fill(0).map(() => ({ x: headBase.x, y: headBase.y }));
    return { pts, head: { x: headBase.x, y: headBase.y }, desired: { x: headBase.x, y: headBase.y }, ...offsets[i] };
  };

  const trails = [makeTrail(0), makeTrail(1), makeTrail(2)];

  let dpr = 1;
  const resize = () => {
    dpr = Math.min(window.devicePixelRatio || 1, 1.1);
    canvas.width = Math.max(1, Math.floor(window.innerWidth * dpr));
    canvas.height = Math.max(1, Math.floor(window.innerHeight * dpr));
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  };

  const onPointer = (e) => {
    worldTarget.x = e.clientX;
    worldTarget.y = e.clientY;
  };

  const onClick = (event) => {
    const el = event.target;
    if (el && (el.closest('a') || el.closest('button'))) return;
    tubeColors = randomColors(3);
  };

  window.addEventListener('resize', resize, { passive: true });
  document.addEventListener('pointermove', onPointer, { passive: true });
  if (hero) hero.addEventListener('pointerup', onClick, { passive: true });

  resize();

  let running = false;
  let rafId = null;
  let inView = true;
  let lastT = performance.now();
  let revealed = false;

  const drawTrail = (trail, color) => {
    ctx.save();
    ctx.globalCompositeOperation = 'lighter';
    ctx.shadowColor = color;
    ctx.shadowBlur = 18;

    // Soft under-stroke
    ctx.globalAlpha = 0.22;
    ctx.lineWidth = trail.lw * 2.2;
    ctx.strokeStyle = color;
    ctx.beginPath();
    trail.pts.forEach((p, idx) => {
      if (idx === 0) ctx.moveTo(p.x, p.y);
      else ctx.lineTo(p.x, p.y);
    });
    ctx.stroke();

    // Bright core
    ctx.globalAlpha = 0.55;
    ctx.shadowBlur = 8;
    ctx.lineWidth = trail.lw;
    ctx.beginPath();
    trail.pts.forEach((p, idx) => {
      if (idx === 0) ctx.moveTo(p.x, p.y);
      else ctx.lineTo(p.x, p.y);
    });
    ctx.stroke();

    ctx.restore();
  };

  const frame = () => {
    if (!running) return;
    rafId = requestAnimationFrame(frame);

    const now = performance.now();
    const dt = Math.min(0.05, (now - lastT) / 1000);
    lastT = now;

    // Reveal the preloader quickly once we can render frames.
    if (!revealed) {
      revealed = true;
      window.setTimeout(() => {
        if (preloader && typeof preloader.reveal === 'function') preloader.reveal();
      }, 450);
    }

    // Gentle drift if no pointer movement yet.
    if (!('ontouchstart' in window) && (worldTarget.x === 0 || worldTarget.y === 0)) {
      worldTarget.x = window.innerWidth * 0.5;
      worldTarget.y = window.innerHeight * 0.45;
    }

    const lerpAmt = 1 - Math.pow(0.001, dt);
    headBase.x += (worldTarget.x - headBase.x) * lerpAmt;
    headBase.y += (worldTarget.y - headBase.y) * lerpAmt;

    trails.forEach((t) => {
      t.desired.x = headBase.x + t.x;
      t.desired.y = headBase.y + t.y;
      t.head.x += (t.desired.x - t.head.x) * t.stiffness;
      t.head.y += (t.desired.y - t.head.y) * t.stiffness;
      for (let i = t.pts.length - 1; i >= 1; i--) {
        t.pts[i].x = t.pts[i - 1].x;
        t.pts[i].y = t.pts[i - 1].y;
      }
      t.pts[0].x = t.head.x;
      t.pts[0].y = t.head.y;
    });

    ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
    drawTrail(trails[0], tubeColors[0]);
    drawTrail(trails[1], tubeColors[1]);
    drawTrail(trails[2], tubeColors[2]);
  };

  const start = () => {
    if (running) return;
    if (!inView) return;
    running = true;
    frame();
  };

  const stop = () => {
    running = false;
    if (rafId != null) cancelAnimationFrame(rafId);
    rafId = null;
  };

  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver(
      (entries) => {
        const entry = entries[0];
        inView = Boolean(entry && entry.isIntersecting);
        if (!inView) stop();
        else start();
      },
      { rootMargin: '200px 0px' }
    );
    io.observe(canvas);
  }

  // Initial paint
  ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);

  return { start, stop };
}

export function initThreeHero({ reducedMotion, preloader } = {}) {
  const canvas = document.getElementById('canvas');
  const hero = document.getElementById('hero');
  if (!canvas) return { stop() {}, start() {} };
  if (reducedMotion) return { stop() {}, start() {} };

  // Always use 2D to avoid WebGL/Metal context loss issues.
  // This removes the ANGLE/Metal spam and keeps tubes working everywhere.
  return initTubes2D({ canvas, hero, preloader });
}

