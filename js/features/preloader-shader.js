import { hasThree } from '../utils/env.js';

function clamp01(v) {
  return Math.max(0, Math.min(1, v));
}

function easeInOutCubic(t) {
  return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
}

export function initPreloaderShader({ reducedMotion }) {
  const wrap = document.querySelector('[data-preloader]');
  const canvas = document.getElementById('preloader-canvas');
  if (!wrap || !canvas) return { reveal() {} };

  const root = document.documentElement;
  root.classList.add('is-loading');

  let resolveDone = null;
  const donePromise = new Promise((resolve) => {
    resolveDone = resolve;
  });

  // Reduced motion: keep it simple and get out fast.
  if (reducedMotion) {
    queueMicrotask(() => {
      wrap.classList.add('is-done');
      root.classList.remove('is-loading');
      window.setTimeout(() => wrap.remove(), 700);
      if (resolveDone) resolveDone();
    });
    return { reveal: () => donePromise };
  }

  const canRunShader = hasThree();
  const THREE = window.THREE;

  let renderer = null;
  let scene = null;
  let camera = null;
  let material = null;
  let rafId = null;
  let startedAt = performance.now();
  let revealing = false;
  let done = false;
  let revealStart = 0;
  let revealDuration = 1100;
  let onResize = null;

  const teardown = () => {
    if (rafId != null) cancelAnimationFrame(rafId);
    rafId = null;

    if (onResize) window.removeEventListener('resize', onResize);
    onResize = null;

    if (material) material.dispose();
    material = null;

    if (renderer) renderer.dispose();
    renderer = null;

    scene = null;
    camera = null;
  };

  const reveal = () => {
    if (done) return;
    if (revealing) return;
    revealing = true;
    revealStart = performance.now();
  };

  const finish = () => {
    if (done) return;
    done = true;
    wrap.classList.add('is-done');
    root.classList.remove('is-loading');
    window.setTimeout(() => wrap.remove(), 700);
    teardown();
    if (resolveDone) resolveDone();
  };

  if (!canRunShader) {
    // CSS-only fallback: fade out when the page is ready.
    window.addEventListener('load', () => finish(), { once: true });
    return {
      reveal: () => {
        finish();
        return donePromise;
      },
    };
  }

  scene = new THREE.Scene();
  camera = new THREE.OrthographicCamera(-1, 1, 1, -1, 0, 1);
  renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: false, powerPreference: 'high-performance' });

  const uniforms = {
    u_time: { value: 0 },
    u_progress: { value: 0 },
    u_resolution: { value: new THREE.Vector2(1, 1) },
  };

  material = new THREE.ShaderMaterial({
    transparent: true,
    depthWrite: false,
    uniforms,
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
        vec2 uv = vUv;
        vec2 ruv = (gl_FragCoord.xy / max(u_resolution.xy, vec2(1.0)));
        vec2 p = (ruv - 0.5) * vec2(u_resolution.x / u_resolution.y, 1.0);

        float t = u_time * 0.35;
        float swirl = atan(p.y, p.x);
        float radial = length(p);
        float n = fbm(p * 3.2 + vec2(cos(swirl + t), sin(swirl - t)) * 0.55 + t);

        vec3 c1 = vec3(0.486, 0.227, 0.925); // purple
        vec3 c2 = vec3(0.133, 0.827, 0.933); // cyan
        vec3 c3 = vec3(0.639, 1.000, 0.070); // lime

        float band = smoothstep(0.55, 0.05, radial);
        float glow = smoothstep(0.75, 0.15, radial) * (0.35 + 0.65 * n);

        vec3 col = mix(c1, c2, smoothstep(0.18, 0.82, ruv.x + 0.12 * n));
        col = mix(col, c3, smoothstep(0.15, 0.95, ruv.y + 0.10 * sin(t + swirl)));
        col *= (0.35 + 1.15 * glow) * band;

        // Dissolve reveal: alpha starts at 1, goes to 0.
        float threshold = mix(0.05, 1.05, u_progress);
        float dissolve = smoothstep(threshold - 0.18, threshold + 0.02, n + (1.0 - radial) * 0.55);
        float alpha = 1.0 - dissolve;

        // Subtle grain to avoid banding.
        float g = hash(gl_FragCoord.xy + u_time * 60.0);
        col += (g - 0.5) * 0.04;

        gl_FragColor = vec4(col, alpha);
      }
    `,
  });

  const mesh = new THREE.Mesh(new THREE.PlaneGeometry(2, 2), material);
  scene.add(mesh);

  onResize = () => {
    const w = Math.max(1, window.innerWidth);
    const h = Math.max(1, window.innerHeight);
    renderer.setSize(w, h, false);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    uniforms.u_resolution.value.set(w, h);
  };

  onResize();
  window.addEventListener('resize', onResize, { passive: true });

  const frame = () => {
    rafId = requestAnimationFrame(frame);
    const now = performance.now();
    uniforms.u_time.value = (now - startedAt) / 1000;

    if (revealing) {
      const t = clamp01((now - revealStart) / revealDuration);
      uniforms.u_progress.value = easeInOutCubic(t);
      if (t >= 1) {
        // Render one last frame at full progress before fading the DOM.
        renderer.render(scene, camera);
        finish();
        return;
      }
    }

    renderer.render(scene, camera);
  };

  frame();

  // Safety: never block the page forever.
  window.setTimeout(() => reveal(), 4200);
  window.addEventListener('load', () => reveal(), { once: true });

  return {
    reveal: () => {
      reveal();
      return donePromise;
    },
  };
}

