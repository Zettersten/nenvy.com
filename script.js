/**
 * Motion + interaction layer
 * - GSAP/ScrollTrigger for reveals + parallax (desktop-first, reduced-motion safe)
 * - Three.js hero geometry as an ambient layer (optional)
 * - Copy-to-clipboard microinteraction
 * - Magnetic CTA hover (pointer fine only)
 */

document.documentElement.classList.add('js');

let scene, camera, renderer, geometry;
let mouseX = 0, mouseY = 0;
let targetX = 0, targetY = 0;
let windowHalfX = window.innerWidth / 2;
let windowHalfY = window.innerHeight / 2;
let prefersReducedMotion = false;
let threeIsRunning = false;
let threeRafId = null;

const isCoarsePointer = () => window.matchMedia('(pointer: coarse)').matches;

function initThreeJS() {
  const canvas = document.getElementById('canvas');
  if (!canvas) return;
  if (typeof THREE === 'undefined') return;

  scene = new THREE.Scene();
  camera = new THREE.PerspectiveCamera(70, window.innerWidth / window.innerHeight, 0.1, 1000);
  renderer = new THREE.WebGLRenderer({
    canvas,
    alpha: true,
    antialias: true,
  });
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

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
  geometry = group;
  scene.add(group);

  camera.position.z = 5.2;

  if (!prefersReducedMotion && !isCoarsePointer()) {
    document.addEventListener('mousemove', onDocumentMouseMove, { passive: true });
  }
  window.addEventListener('resize', onWindowResize, { passive: true });

  if (prefersReducedMotion) {
    renderer.render(scene, camera);
    return;
  }

  startThreeAnimation();
}

function onDocumentMouseMove(event) {
  const normalizedX = (event.clientX - windowHalfX) / window.innerWidth;
  const normalizedY = (event.clientY - windowHalfY) / window.innerHeight;
  mouseX = normalizedX * 0.26;
  mouseY = normalizedY * 0.26;
}

function onWindowResize() {
  windowHalfX = window.innerWidth / 2;
  windowHalfY = window.innerHeight / 2;

  if (!camera || !renderer) return;
  camera.aspect = window.innerWidth / window.innerHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(window.innerWidth, window.innerHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
}

function animate() {
  if (!threeIsRunning) return;
  threeRafId = requestAnimationFrame(animate);

  targetX += (mouseX - targetX) * 0.05;
  targetY += (mouseY - targetY) * 0.05;

  if (geometry) {
    geometry.rotation.x += targetY * 0.14;
    geometry.rotation.y += targetX * 0.14;
    geometry.rotation.z += 0.0016;

    const scale = 1 + (Math.abs(targetX) + Math.abs(targetY)) * 0.08;
    geometry.scale.set(scale, scale, scale);
  }

  renderer.render(scene, camera);
}

function startThreeAnimation() {
  if (prefersReducedMotion) return;
  if (threeIsRunning) return;
  threeIsRunning = true;
  animate();
}

function stopThreeAnimation() {
  threeIsRunning = false;
  if (threeRafId != null) {
    cancelAnimationFrame(threeRafId);
    threeRafId = null;
  }
}

function initSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener('click', (e) => {
      const href = anchor.getAttribute('href');
      if (!href || href === '#') return;
      const target = document.querySelector(href);
      if (!target) return;

      e.preventDefault();
      const behavior = prefersReducedMotion ? 'auto' : 'smooth';
      target.scrollIntoView({ behavior, block: 'start' });
      history.pushState(null, '', href);
    });
  });
}

function initCopyButtons() {
  document.querySelectorAll('[data-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const value = btn.getAttribute('data-copy') || '';
      if (!value) return;

      const original = btn.querySelector('span')?.textContent || btn.textContent || '';

      const setLabel = (text) => {
        const span = btn.querySelector('span');
        if (span) span.textContent = text;
      };

      try {
        await navigator.clipboard.writeText(value);
        setLabel('Copied');
      } catch {
        // Fallback for older browsers / permissions issues
        const input = document.createElement('input');
        input.value = value;
        input.setAttribute('readonly', 'true');
        input.style.position = 'fixed';
        input.style.left = '-9999px';
        document.body.appendChild(input);
        input.select();
        try {
          document.execCommand('copy');
          setLabel('Copied');
        } catch {
          setLabel('Copy failed');
        }
        document.body.removeChild(input);
      }

      window.setTimeout(() => setLabel(original.trim() || 'Copy'), 1100);
    });
  });
}

function initMagneticButtons() {
  if (prefersReducedMotion) return;
  if (isCoarsePointer()) return;

  const items = Array.from(document.querySelectorAll('.magnetic'));
  if (items.length === 0) return;

  items.forEach((el) => {
    let raf = null;

    const onMove = (e) => {
      const rect = el.getBoundingClientRect();
      const relX = (e.clientX - rect.left) / rect.width - 0.5;
      const relY = (e.clientY - rect.top) / rect.height - 0.5;
      const dx = relX * 10;
      const dy = relY * 8;

      if (raf) cancelAnimationFrame(raf);
      raf = requestAnimationFrame(() => {
        el.style.transform = `translate3d(${dx.toFixed(2)}px, ${dy.toFixed(2)}px, 0)`;
      });
    };

    const onLeave = () => {
      if (raf) cancelAnimationFrame(raf);
      el.style.transform = '';
    };

    el.addEventListener('mousemove', onMove, { passive: true });
    el.addEventListener('mouseleave', onLeave, { passive: true });
  });
}

function initGSAP() {
  if (prefersReducedMotion) return;
  if (typeof gsap === 'undefined') return;

  if (typeof ScrollTrigger !== 'undefined') {
    gsap.registerPlugin(ScrollTrigger);
  }

  // Hero entrance
  gsap.fromTo(
    '.hero-content',
    { opacity: 0, y: 16 },
    { opacity: 1, y: 0, duration: 1.0, ease: 'power3.out' }
  );

  // Scroll reveals
  const revealEls = gsap.utils.toArray('[data-reveal]');
  revealEls.forEach((el) => {
    gsap.to(el, {
      opacity: 1,
      y: 0,
      duration: 0.9,
      ease: 'power3.out',
      scrollTrigger: typeof ScrollTrigger === 'undefined'
        ? undefined
        : {
            trigger: el,
            start: 'top 86%',
          },
    });
  });

  if (typeof ScrollTrigger === 'undefined') return;

  // Hero parallax orbs (desktop only)
  if (!isCoarsePointer()) {
    document.querySelectorAll('[data-parallax-speed]').forEach((el) => {
      const speed = Number.parseFloat(el.getAttribute('data-parallax-speed') || '0.2');
      if (!Number.isFinite(speed)) return;

      gsap.to(el, {
        y: () => -window.innerHeight * speed,
        ease: 'none',
        scrollTrigger: {
          trigger: '.hero',
          start: 'top top',
          end: 'bottom top',
          scrub: true,
        },
      });
    });
  }

  // Portrait parallax
  const portraitImg = document.querySelector('.portrait img');
  if (portraitImg) {
    gsap.to(portraitImg, {
      scale: 1.08,
      ease: 'none',
      scrollTrigger: {
        trigger: portraitImg,
        start: 'top bottom',
        end: 'bottom top',
        scrub: true,
      },
    });
  }
}

document.addEventListener('DOMContentLoaded', () => {
  prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!prefersReducedMotion && typeof gsap !== 'undefined') {
    document.documentElement.classList.add('js-motion');
  }
  initThreeJS();
  initSmoothScroll();
  initCopyButtons();
  initMagneticButtons();
  initGSAP();
});

document.addEventListener('visibilitychange', () => {
  if (document.hidden) stopThreeAnimation();
  else startThreeAnimation();
});
