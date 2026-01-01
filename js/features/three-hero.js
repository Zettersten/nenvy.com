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

function getWebGLContext(canvas) {
  try {
    const attrs = { alpha: true, antialias: false, premultipliedAlpha: true, powerPreference: 'high-performance' };
    return canvas.getContext('webgl2', attrs) || canvas.getContext('webgl', attrs) || canvas.getContext('experimental-webgl', attrs);
  } catch {
    return null;
  }
}

function initTubes2D({ canvas, hero, preloader }) {
  const ctx = canvas.getContext('2d', { alpha: true, desynchronized: true });
  if (!ctx) {
    if (preloader && typeof preloader.reveal === 'function') preloader.reveal();
    return { stop() {}, start() {} };
  }

  let tubeColors = ['#f967fb', '#53bc28', '#6958d5'];
  const offsets = [
    { x: 0.0, y: 0.0, stiffness: 0.18, lw: 3.2 },
    { x: -22, y: 12, stiffness: 0.15, lw: 2.7 },
    { x: 18, y: -14, stiffness: 0.13, lw: 2.4 },
  ];

  const worldTarget = { x: window.innerWidth * 0.5, y: window.innerHeight * 0.45 };
  const headBase = { x: worldTarget.x, y: worldTarget.y };

  const makeTrail = (i) => {
    const pts = new Array(34).fill(0).map(() => ({ x: headBase.x, y: headBase.y }));
    return { pts, head: { x: headBase.x, y: headBase.y }, desired: { x: headBase.x, y: headBase.y }, ...offsets[i] };
  };

  const trails = [makeTrail(0), makeTrail(1), makeTrail(2)];

  let dpr = 1;
  const resize = () => {
    dpr = Math.min(window.devicePixelRatio || 1, 1.25);
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
    ctx.shadowBlur = 24;

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

  // Prefer 2D on coarse pointer for perf/reliability.
  const isCoarse = isCoarsePointer();
  if (isCoarse) return initTubes2D({ canvas, hero, preloader });

  const THREE = window.THREE;
  if (!THREE) return initTubes2D({ canvas, hero, preloader });

  const gl = getWebGLContext(canvas);
  if (!gl) return initTubes2D({ canvas, hero, preloader });

  // WebGL path (single context, no noisy console errors).
  // Preloader shader uniforms (defined early so resize handlers are safe).
  const preUniforms = {
    u_time: { value: 0 },
    u_progress: { value: 0 },
    u_resolution: { value: new THREE.Vector2(Math.max(1, window.innerWidth), Math.max(1, window.innerHeight)) },
  };

  let renderer = null;
  try {
    renderer = new THREE.WebGLRenderer({
      canvas,
      context: gl,
      alpha: true,
      antialias: false,
      powerPreference: 'high-performance',
    });
  } catch (e) {
    return initTubes2D({ canvas, hero, preloader });
  }

  renderer.setClearColor(0x000000, 0);
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.25));
  if ('outputEncoding' in renderer) renderer.outputEncoding = THREE.sRGBEncoding;

  // Inspired-by palette (implemented from scratch).
  let tubeColors = ['#f967fb', '#53bc28', '#6958d5'];
  let lightColors = ['#83f36e', '#fe8a2e', '#ff008a', '#60aed5'];

  // Scenes: preloader shader and tubes share one WebGL context.
  const preScene = new THREE.Scene();
  const preCam = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);

  const tubeScene = new THREE.Scene();
  const tubeCam = new THREE.PerspectiveCamera(55, window.innerWidth / window.innerHeight, 0.1, 60);
  tubeCam.position.set(0, 0, 9);

  const worldTarget = new THREE.Vector3(0, 0, 0);
  const headBase = new THREE.Vector3(0, 0, 0);

  const applyLightColors = (colors) => {
    lightColors = colors.slice(0, 4);
    const a = new THREE.Color(lightColors[0] || '#60aed5');
    tubeScene.fog = new THREE.FogExp2(a, 0.06);
  };

  const makeTrail = ({ color, stiffness, offset, size }) => {
    const points = Array.from({ length: 34 }, () => new THREE.Vector3(0, 0, 0));
    const head = new THREE.Vector3(0, 0, 0);
    const desired = new THREE.Vector3(0, 0, 0);
    const positions = new Float32Array(points.length * 3);

    const geometry = new THREE.BufferGeometry();
    const attr = new THREE.BufferAttribute(positions, 3);
    if ('setUsage' in attr) attr.setUsage(THREE.DynamicDrawUsage);
    geometry.setAttribute('position', attr);

    const lineMat = new THREE.LineBasicMaterial({
      color: new THREE.Color(color),
      transparent: true,
      opacity: 0.24,
      blending: THREE.AdditiveBlending,
      depthWrite: false,
    });
    const line = new THREE.Line(geometry, lineMat);

    const ptsMat = new THREE.PointsMaterial({
      color: new THREE.Color(color),
      transparent: true,
      opacity: 0.70,
      blending: THREE.AdditiveBlending,
      depthWrite: false,
      size,
      sizeAttenuation: true,
    });
    const pts = new THREE.Points(geometry, ptsMat);

    const group = new THREE.Group();
    group.add(line, pts);
    tubeScene.add(group);

    return { points, positions, positionAttr: attr, head, desired, offset, stiffness, lineMat, ptsMat };
  };

  const trails = [
    makeTrail({ color: tubeColors[0], stiffness: 0.18, size: 0.22, offset: new THREE.Vector3(0.0, 0.0, 0) }),
    makeTrail({ color: tubeColors[1], stiffness: 0.15, size: 0.19, offset: new THREE.Vector3(-0.35, 0.18, 0) }),
    makeTrail({ color: tubeColors[2], stiffness: 0.13, size: 0.17, offset: new THREE.Vector3(0.32, -0.22, 0) }),
  ];

  const applyColors = (colors) => {
    tubeColors = colors.slice(0, 3);
    trails.forEach((t, i) => {
      const col = new THREE.Color(tubeColors[i] || tubeColors[0]);
      t.lineMat.color.copy(col);
      t.ptsMat.color.copy(col);
    });
  };

  applyLightColors(lightColors);

  const onPointer = (event) => {
    const x = (event.clientX / window.innerWidth) * 2 - 1;
    const y = -(event.clientY / window.innerHeight) * 2 + 1;
    worldTarget.set(x * 3.2, y * 1.9, 0);
  };

  const onClick = (event) => {
    const el = event.target;
    if (el && (el.closest('a') || el.closest('button'))) return;
    applyColors(randomColors(3));
    applyLightColors(randomColors(4));
  };

  const onResize = () => {
    tubeCam.aspect = window.innerWidth / window.innerHeight;
    tubeCam.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.25));
    preUniforms.u_resolution.value.set(Math.max(1, window.innerWidth), Math.max(1, window.innerHeight));
  };

  window.addEventListener('resize', onResize, { passive: true });
  document.addEventListener('pointermove', onPointer, { passive: true });
  if (hero) hero.addEventListener('pointerup', onClick, { passive: true });

  const preMaterial = new THREE.ShaderMaterial({
    transparent: true,
    depthWrite: false,
    uniforms: preUniforms,
    vertexShader: `
      varying vec2 vUv;
      void main() {
        vUv = uv;
        gl_Position = vec4(position.xy, 0.0, 1.0);
      }
    `,
    fragmentShader: `
      precision highp float;
      varying vec2 vUv;
      uniform float u_time;
      uniform float u_progress;
      uniform vec2 u_resolution;

      float hash(vec2 p) {
        p = fract(p * vec2(123.34, 345.45));
        p += dot(p, p + 34.345);
        return fract(p.x * p.y);
      }

      float noise(vec2 p) {
        vec2 i = floor(p);
        vec2 f = fract(p);
        float a = hash(i);
        float b = hash(i + vec2(1.0, 0.0));
        float c = hash(i + vec2(0.0, 1.0));
        float d = hash(i + vec2(1.0, 1.0));
        vec2 u = f * f * (3.0 - 2.0 * f);
        return mix(a, b, u.x) + (c - a) * u.y * (1.0 - u.x) + (d - b) * u.x * u.y;
      }

      float fbm(vec2 p) {
        float v = 0.0;
        float a = 0.55;
        for (int i = 0; i < 5; i++) {
          v += a * noise(p);
          p *= 2.02;
          a *= 0.5;
        }
        return v;
      }

      void main() {
        vec2 ruv = (gl_FragCoord.xy / max(u_resolution.xy, vec2(1.0)));
        vec2 p = (ruv - 0.5) * vec2(u_resolution.x / u_resolution.y, 1.0);

        float t = u_time * 0.35;
        float swirl = atan(p.y, p.x);
        float radial = length(p);
        float n = fbm(p * 3.2 + vec2(cos(swirl + t), sin(swirl - t)) * 0.55 + t);

        vec3 c1 = vec3(0.486, 0.227, 0.925);
        vec3 c2 = vec3(0.133, 0.827, 0.933);
        vec3 c3 = vec3(0.639, 1.000, 0.070);

        float band = smoothstep(0.55, 0.05, radial);
        float glow = smoothstep(0.75, 0.15, radial) * (0.35 + 0.65 * n);

        vec3 col = mix(c1, c2, smoothstep(0.18, 0.82, ruv.x + 0.12 * n));
        col = mix(col, c3, smoothstep(0.15, 0.95, ruv.y + 0.10 * sin(t + swirl)));
        col *= (0.35 + 1.15 * glow) * band;

        float threshold = mix(0.05, 1.05, u_progress);
        float dissolve = smoothstep(threshold - 0.18, threshold + 0.02, n + (1.0 - radial) * 0.55);
        float alpha = 1.0 - dissolve;

        float g = hash(gl_FragCoord.xy + u_time * 60.0);
        col += (g - 0.5) * 0.04;

        gl_FragColor = vec4(col, alpha);
      }
    `,
  });

  const preMesh = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), preMaterial);
  preScene.add(preMesh);

  let running = false;
  let rafId = null;
  let inView = true;
  let lastT = performance.now();
  let phase = 'preloader';
  let phaseStart = performance.now();
  let overlayHidden = false;
  let frameCount = 0;

  const frame = () => {
    if (!running) return;
    rafId = requestAnimationFrame(frame);

    const now = performance.now();
    const dt = Math.min(0.05, (now - lastT) / 1000);
    lastT = now;

    // Render/update at ~30fps to keep FPS high.
    frameCount += 1;
    if (frameCount % 2 !== 0) return;

    if (phase === 'preloader') {
      preUniforms.u_time.value = (now - phaseStart) / 1000;
      const t = clamp01((now - phaseStart) / 650);
      preUniforms.u_progress.value = easeInOutCubic(t);
      renderer.render(preScene, preCam);

      if (t >= 1 && !overlayHidden) {
        overlayHidden = true;
        if (preloader && typeof preloader.reveal === 'function') preloader.reveal();
        phase = 'tubes';
        phaseStart = now;
      }
      return;
    }

    headBase.lerp(worldTarget, 1 - Math.pow(0.001, dt));
    tubeScene.rotation.z += 0.0012;
    tubeScene.rotation.x = headBase.y * 0.03;
    tubeScene.rotation.y = headBase.x * 0.03;

    trails.forEach((tube) => {
      tube.desired.copy(headBase).add(tube.offset);
      tube.head.lerp(tube.desired, tube.stiffness);

      for (let i = tube.points.length - 1; i >= 1; i--) {
        tube.points[i].copy(tube.points[i - 1]);
      }
      tube.points[0].copy(tube.head);
    });

    trails.forEach((tube) => {
      for (let i = 0; i < tube.points.length; i++) {
        const p = tube.points[i];
        const j = i * 3;
        tube.positions[j + 0] = p.x;
        tube.positions[j + 1] = p.y;
        tube.positions[j + 2] = p.z - i * 0.06;
      }
      tube.positionAttr.needsUpdate = true;
    });

    renderer.render(tubeScene, tubeCam);
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

  // Initial paint (preloader frame)
  renderer.render(preScene, preCam);

  return { start, stop };
}

