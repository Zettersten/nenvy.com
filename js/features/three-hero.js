import { hasThree, isCoarsePointer } from '../utils/env.js';

export function initThreeHero({ reducedMotion }) {
  const canvas = document.getElementById('canvas');
  const hero = document.getElementById('hero');
  if (!canvas) return { stop() {}, start() {} };
  if (!hasThree()) return { stop() {}, start() {} };
  if (reducedMotion) return { stop() {}, start() {} };

  const THREE = window.THREE;

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(55, window.innerWidth / window.innerHeight, 0.1, 60);
  camera.position.set(0, 0, 9);

  let renderer = null;
  try {
    renderer = new THREE.WebGLRenderer({
      canvas,
      alpha: true,
      antialias: true,
      powerPreference: 'high-performance',
    });
  } catch (e) {
    // WebGL context creation can fail (GPU limits, privacy modes, etc.).
    return { stop() {}, start() {} };
  }
  renderer.setClearColor(0x000000, 0);
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  if ('outputEncoding' in renderer) renderer.outputEncoding = THREE.sRGBEncoding;

  const isCoarse = isCoarsePointer();

  // Inspired-by palette (implemented from scratch; no CC-licensed code used).
  let tubeColors = ['#f967fb', '#53bc28', '#6958d5'];
  let lightColors = ['#83f36e', '#fe8a2e', '#ff008a', '#60aed5'];

  const tubesGroup = new THREE.Group();
  scene.add(tubesGroup);

  scene.add(new THREE.AmbientLight(0xffffff, 0.22));

  const lights = lightColors.map((c) => new THREE.PointLight(new THREE.Color(c), 55, 60));
  lights.forEach((l, i) => {
    const s = i % 2 === 0 ? 1 : -1;
    l.position.set(3.8 * s, 2.2 * (i - 1.5), 5.2);
    scene.add(l);
  });

  const makeTubeMaterial = (hex) =>
    new THREE.MeshStandardMaterial({
      color: new THREE.Color(hex),
      emissive: new THREE.Color(hex),
      emissiveIntensity: 0.95,
      roughness: 0.38,
      metalness: 0.12,
      transparent: true,
      opacity: 0.82,
      blending: THREE.AdditiveBlending,
      depthWrite: false,
    });

  const worldTarget = new THREE.Vector3(0, 0, 0);
  const headBase = new THREE.Vector3(0, 0, 0);

  const makeTube = ({ color, radius, stiffness, offset }) => {
    const points = Array.from({ length: 42 }, () => new THREE.Vector3(0, 0, 0));
    const head = new THREE.Vector3(0, 0, 0);
    const desired = new THREE.Vector3(0, 0, 0);
    const curve = new THREE.CatmullRomCurve3(points);
    let geometry = new THREE.TubeGeometry(curve, 140, radius, 10, false);
    const material = makeTubeMaterial(color);
    const mesh = new THREE.Mesh(geometry, material);
    tubesGroup.add(mesh);

    const updateGeometry = () => {
      curve.points = points;
      const next = new THREE.TubeGeometry(curve, 140, radius, 10, false);
      geometry.dispose();
      geometry = next;
      mesh.geometry = geometry;
    };

    return { points, head, desired, offset, stiffness, mesh, material, updateGeometry };
  };

  const tubes = [
    makeTube({ color: tubeColors[0], radius: 0.095, stiffness: 0.16, offset: new THREE.Vector3(0.0, 0.0, 0) }),
    makeTube({ color: tubeColors[1], radius: 0.075, stiffness: 0.13, offset: new THREE.Vector3(-0.35, 0.18, 0) }),
    makeTube({ color: tubeColors[2], radius: 0.065, stiffness: 0.11, offset: new THREE.Vector3(0.32, -0.22, 0) }),
  ];

  const applyColors = (colors) => {
    tubeColors = colors.slice(0, 3);
    tubes.forEach((t, i) => {
      const col = new THREE.Color(tubeColors[i] || tubeColors[0]);
      t.material.color.copy(col);
      t.material.emissive.copy(col);
    });
  };

  const applyLightColors = (colors) => {
    lightColors = colors.slice(0, 4);
    lights.forEach((l, i) => l.color.set(lightColors[i] || lightColors[0]));
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
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  };

  window.addEventListener('resize', onResize, { passive: true });
  if (!isCoarse) {
    document.addEventListener('pointermove', onPointer, { passive: true });
  }
  if (hero) {
    hero.addEventListener('pointerup', onClick, { passive: true });
  }

  let running = false;
  let rafId = null;
  let inView = true;
  let frameCount = 0;
  let lastT = performance.now();

  const frame = () => {
    if (!running) return;
    rafId = requestAnimationFrame(frame);

    const now = performance.now();
    const dt = Math.min(0.05, (now - lastT) / 1000);
    lastT = now;

    // Auto drift on coarse pointer; cursor-follow on desktop.
    if (isCoarse) {
      const t = now / 1000;
      worldTarget.set(Math.sin(t * 0.75) * 2.2, Math.cos(t * 0.62) * 1.15, 0);
    }

    headBase.lerp(worldTarget, 1 - Math.pow(0.001, dt));

    tubesGroup.rotation.z += 0.0012;
    tubesGroup.rotation.x = headBase.y * 0.03;
    tubesGroup.rotation.y = headBase.x * 0.03;

    // Update tube trails (copy points in-place to avoid allocations).
    tubes.forEach((tube) => {
      tube.desired.copy(headBase).add(tube.offset);
      tube.head.lerp(tube.desired, tube.stiffness);

      for (let i = tube.points.length - 1; i >= 1; i--) {
        tube.points[i].copy(tube.points[i - 1]);
      }
      tube.points[0].copy(tube.head);
    });

    // Rebuild tube geometry at ~30fps for perf.
    frameCount += 1;
    if (frameCount % 2 === 0) {
      tubes.forEach((t) => t.updateGeometry());
    }

    // Light dance
    const tt = now / 1000;
    lights.forEach((l, i) => {
      const s = i % 2 === 0 ? 1 : -1;
      l.position.x = 3.2 * s + Math.sin(tt * (0.7 + i * 0.12)) * 0.9;
      l.position.y = 1.8 * (i - 1.5) + Math.cos(tt * (0.9 + i * 0.15)) * 0.6;
      l.position.z = 5.0 + Math.sin(tt * 0.6 + i) * 0.35;
    });

    renderer.render(scene, camera);
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
  renderer.render(scene, camera);

  return { start, stop };
}

