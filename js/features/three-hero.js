// 2D-only implementation (robust vs WebGL context loss)

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

  // Keep config shape similar to the TubesCursor reference snippet.
  const config = {
    tubes: {
      colors: ['#f967fb', '#53bc28', '#6958d5'],
      lights: {
        intensity: 200,
        colors: ['#83f36e', '#fe8a2e', '#ff008a', '#60aed5'],
      },
    },
  };

  let tubeColors = [...config.tubes.colors];
  let lightColors = [...config.tubes.lights.colors];
  let lightIntensity = config.tubes.lights.intensity;

  // Inspired by the behavior docs: multiple tubes, pulsing "lights", click randomizes.
  // Counts are tuned down slightly for perf.
  const tubeCount = 10; // docs mention 12
  const baseRadius = 26;
  const baseStiffness = 0.12; // "smoothness" feel

  const worldTarget = { x: window.innerWidth * 0.5, y: window.innerHeight * 0.45 };
  const headBase = { x: worldTarget.x, y: worldTarget.y };

  const makeTrail = (i) => {
    const pts = new Array(26).fill(0).map(() => ({ x: headBase.x, y: headBase.y }));
    const a = (i / Math.max(1, tubeCount)) * Math.PI * 2;
    const r = baseRadius * (0.55 + (i / tubeCount) * 0.65);
    const stiffness = baseStiffness + (i / tubeCount) * 0.06;
    // Thicker tubes to better match the reference TubesCursor look.
    const lw = 3.4 + (1 - i / tubeCount) * 2.6;
    return {
      pts,
      head: { x: headBase.x, y: headBase.y },
      desired: { x: headBase.x, y: headBase.y },
      x: Math.cos(a) * r,
      y: Math.sin(a) * r,
      stiffness,
      lw,
    };
  };

  const trails = Array.from({ length: tubeCount }, (_, i) => makeTrail(i));

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
    lightColors = randomColors(4);
    // Keep the same overall brightness, but re-randomize palette.
    lightIntensity = config.tubes.lights.intensity;
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

  const drawLightHalo = (t, intensity) => {
    const x = headBase.x;
    const y = headBase.y;
    const r = 220;
    const g = ctx.createRadialGradient(x, y, 0, x, y, r);
    g.addColorStop(0.0, `${lightColors[0]}1a`);
    g.addColorStop(0.35, `${lightColors[1]}10`);
    g.addColorStop(0.7, `${lightColors[2]}08`);
    g.addColorStop(1.0, `#00000000`);
    ctx.save();
    ctx.globalCompositeOperation = 'lighter';
    const intensityScale = Math.max(0.4, Math.min(1.6, (lightIntensity || 200) / 200));
    ctx.globalAlpha = 0.55 * intensity * intensityScale;
    ctx.fillStyle = g;
    ctx.beginPath();
    ctx.arc(x, y, r, 0, Math.PI * 2);
    ctx.fill();
    ctx.restore();
  };

  const drawTrail = (trail, color) => {
    ctx.save();
    ctx.globalCompositeOperation = 'lighter';
    ctx.shadowColor = color;
    ctx.shadowBlur = 22;

    // Soft under-stroke
    ctx.globalAlpha = 0.22;
    ctx.lineWidth = trail.lw * 2.35;
    ctx.strokeStyle = color;
    ctx.beginPath();
    trail.pts.forEach((p, idx) => {
      if (idx === 0) ctx.moveTo(p.x, p.y);
      else ctx.lineTo(p.x, p.y);
    });
    ctx.stroke();

    // Bright core
    ctx.globalAlpha = 0.55;
    ctx.shadowBlur = 10;
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

    const tt = now * 0.002;
    const intensity = 0.75 + Math.sin(tt) * 0.25;

    trails.forEach((t, idx) => {
      // Subtle breathing motion.
      const wobble = Math.sin(tt + idx) * 2.0;
      t.desired.x = headBase.x + t.x;
      t.desired.y = headBase.y + t.y + wobble;
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
    drawLightHalo(tt, intensity);
    trails.forEach((t, i) => drawTrail(t, tubeColors[i % tubeColors.length]));
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

