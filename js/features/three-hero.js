import { hasThree, isCoarsePointer } from '../utils/env.js';

function clamp01(v) {
  return Math.max(0, Math.min(1, v));
}

function easeInOutCubic(t) {
  return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
}

export function initThreeHero({ reducedMotion, preloader } = {}) {
  const canvas = document.getElementById('canvas');
  const hero = document.getElementById('hero');
  if (!canvas) return { stop() {}, start() {} };
  if (!hasThree()) return { stop() {}, start() {} };
  if (reducedMotion) return { stop() {}, start() {} };

  const THREE = window.THREE;

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
      alpha: true,
      antialias: false,
      powerPreference: 'high-performance',
    });
  } catch (e) {
    // WebGL context creation can fail (GPU limits, privacy modes, etc.).
    return { stop() {}, start() {} };
  }
  renderer.setClearColor(0x000000, 0);
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
  if ('outputEncoding' in renderer) renderer.outputEncoding = THREE.sRGBEncoding;

  const isCoarse = isCoarsePointer();

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

  const makeTrail = ({ color, stiffness, offset, size }) => {
    const points = Array.from({ length: 38 }, () => new THREE.Vector3(0, 0, 0));
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
      opacity: 0.26,
      blending: THREE.AdditiveBlending,
      depthWrite: false,
    });
    const line = new THREE.Line(geometry, lineMat);

    const ptsMat = new THREE.PointsMaterial({
      color: new THREE.Color(color),
      transparent: true,
      opacity: 0.75,
      blending: THREE.AdditiveBlending,
      depthWrite: false,
      size,
      sizeAttenuation: true,
    });
    const pts = new THREE.Points(geometry, ptsMat);

    const group = new THREE.Group();
    group.add(line, pts);
    tubeScene.add(group);

    return { points, positions, positionAttr: attr, head, desired, offset, stiffness, group, lineMat, ptsMat };
  };

  const trails = [
    makeTrail({ color: tubeColors[0], stiffness: 0.18, size: 0.24, offset: new THREE.Vector3(0.0, 0.0, 0) }),
    makeTrail({ color: tubeColors[1], stiffness: 0.15, size: 0.20, offset: new THREE.Vector3(-0.35, 0.18, 0) }),
    makeTrail({ color: tubeColors[2], stiffness: 0.13, size: 0.18, offset: new THREE.Vector3(0.32, -0.22, 0) }),
  ];

  // Apply initial tint.
  applyLightColors(lightColors);

  const applyColors = (colors) => {
    tubeColors = colors.slice(0, 3);
    trails.forEach((t, i) => {
      const col = new THREE.Color(tubeColors[i] || tubeColors[0]);
      t.lineMat.color.copy(col);
      t.ptsMat.color.copy(col);
    });
  };

  const applyLightColors = (colors) => {
    lightColors = colors.slice(0, 4);
    // Light colors are used as a subtle scene tint for the trails.
    const a = new THREE.Color(lightColors[0] || '#60aed5');
    tubeScene.fog = new THREE.FogExp2(a, 0.06);
  };

  const randomColors = (count) =>
    new Array(count)
      .fill(0)
      .map(() => `#${Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0')}`);

  const onPointer = (event) => {
    const x = (event.clientX / window.innerWidth) * 2 - 1;
    const y = -(event.clientY / window.innerHeight) * 2 + 1;
    worldTarget.set(x * 3.2, y * 1.9, 0);
  };

  const onClick = (event) => {
    const el = event.target;
    // Don't hijack clicks meant for UI.
    if (el && (el.closest('a') || el.closest('button'))) return;
    applyColors(randomColors(3));
    applyLightColors(randomColors(4));
  };

  const onResize = () => {
    tubeCam.aspect = window.innerWidth / window.innerHeight;
    tubeCam.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
    preUniforms.u_resolution.value.set(Math.max(1, window.innerWidth), Math.max(1, window.innerHeight));
  };

  window.addEventListener('resize', onResize, { passive: true });
  if (!isCoarse) {
    document.addEventListener('pointermove', onPointer, { passive: true });
  }
  if (hero) {
    hero.addEventListener('pointerup', onClick, { passive: true });
  }

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

  const frame = () => {
    if (!running) return;
    rafId = requestAnimationFrame(frame);

    const now = performance.now();
    const dt = Math.min(0.05, (now - lastT) / 1000);
    lastT = now;

    // Preloader pass first (single context).
    if (phase === 'preloader') {
      preUniforms.u_time.value = (now - phaseStart) / 1000;
      const t = clamp01((now - phaseStart) / 950);
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

    // Auto drift on coarse pointer; cursor-follow on desktop.
    if (isCoarse) {
      const tt = now / 1000;
      worldTarget.set(Math.sin(tt * 0.75) * 2.2, Math.cos(tt * 0.62) * 1.15, 0);
    }

    headBase.lerp(worldTarget, 1 - Math.pow(0.001, dt));

    // Gentle scene drift.
    tubeScene.rotation.z += 0.0012;
    tubeScene.rotation.x = headBase.y * 0.03;
    tubeScene.rotation.y = headBase.x * 0.03;

    // Update tube trails (copy points in-place to avoid allocations).
    trails.forEach((tube) => {
      tube.desired.copy(headBase).add(tube.offset);
      tube.head.lerp(tube.desired, tube.stiffness);

      for (let i = tube.points.length - 1; i >= 1; i--) {
        tube.points[i].copy(tube.points[i - 1]);
      }
      tube.points[0].copy(tube.head);
    });

    // Push points into geometry buffers.
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
    if (rafId != null) {
      cancelAnimationFrame(rafId);
      rafId = null;
    }
  };

  // Only animate when the hero is near/in view (perf).
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

  // Render once for initial paint; animation is gated by intersection + visibility.
  renderer.render(preScene, preCam);

  return { start, stop };
}

