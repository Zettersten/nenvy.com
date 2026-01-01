import { hasThree, isCoarsePointer } from '../utils/env.js';

export function initThreeHero({ reducedMotion }) {
  const canvas = document.getElementById('canvas');
  if (!canvas) return { stop() {}, start() {} };
  if (!hasThree()) return { stop() {}, start() {} };
  if (reducedMotion) return { stop() {}, start() {} };

  const THREE = window.THREE;

  let scene = new THREE.Scene();
  let camera = new THREE.PerspectiveCamera(70, window.innerWidth / window.innerHeight, 0.1, 1000);
  let renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

  let mouseX = 0;
  let mouseY = 0;
  let targetX = 0;
  let targetY = 0;
  let windowHalfX = window.innerWidth / 2;
  let windowHalfY = window.innerHeight / 2;

  const group = new THREE.Group();
  const makeMesh = (geo, opacity) => {
    const mat = new THREE.MeshBasicMaterial({
      color: 0xffffff,
      wireframe: true,
      transparent: true,
      opacity,
    });
    return new THREE.Mesh(geo, mat);
  };

  const mesh1 = makeMesh(new THREE.IcosahedronGeometry(2.15, 0), 0.14);
  const mesh2 = makeMesh(new THREE.OctahedronGeometry(1.6, 0), 0.09);
  const mesh3 = makeMesh(new THREE.TetrahedronGeometry(1.15, 0), 0.07);

  mesh2.rotation.set(Math.PI / 4, Math.PI / 4, 0);
  mesh3.rotation.set(-Math.PI / 4, -Math.PI / 4, 0);

  group.add(mesh1, mesh2, mesh3);
  scene.add(group);

  camera.position.z = 5.2;

  const onMouseMove = (event) => {
    const normalizedX = (event.clientX - windowHalfX) / window.innerWidth;
    const normalizedY = (event.clientY - windowHalfY) / window.innerHeight;
    mouseX = normalizedX * 0.26;
    mouseY = normalizedY * 0.26;
  };

  const onResize = () => {
    windowHalfX = window.innerWidth / 2;
    windowHalfY = window.innerHeight / 2;
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  };

  window.addEventListener('resize', onResize, { passive: true });
  if (!reducedMotion && !isCoarsePointer()) {
    document.addEventListener('mousemove', onMouseMove, { passive: true });
  }

  let running = false;
  let rafId = null;
  let inView = true;

  const frame = () => {
    if (!running) return;
    rafId = requestAnimationFrame(frame);

    targetX += (mouseX - targetX) * 0.05;
    targetY += (mouseY - targetY) * 0.05;

    group.rotation.x += targetY * 0.14;
    group.rotation.y += targetX * 0.14;
    group.rotation.z += 0.0016;

    const scale = 1 + (Math.abs(targetX) + Math.abs(targetY)) * 0.08;
    group.scale.set(scale, scale, scale);

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

