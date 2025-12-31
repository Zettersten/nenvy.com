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

function initCursorGlow() {
  if (prefersReducedMotion) return;
  if (isCoarsePointer()) return;

  const glow = document.createElement('div');
  glow.className = 'cursor-glow';
  glow.setAttribute('aria-hidden', 'true');
  document.body.appendChild(glow);

  let x = -9999;
  let y = -9999;
  let tx = x;
  let ty = y;
  let raf = null;

  const onMove = (e) => {
    x = e.clientX;
    y = e.clientY;
    if (raf) return;
    raf = requestAnimationFrame(() => {
      raf = null;
      tx += (x - tx) * 0.14;
      ty += (y - ty) * 0.14;
      glow.style.transform = `translate3d(${(tx - 280).toFixed(1)}px, ${(ty - 280).toFixed(1)}px, 0)`;
    });
  };

  window.addEventListener('mousemove', onMove, { passive: true });
}

function initMobileNav() {
  const toggle = document.querySelector('.nav-toggle');
  const panel = document.getElementById('nav-panel');
  const closeBtn = document.querySelector('.nav-close');
  if (!toggle || !panel || !closeBtn) return;

  let lastFocused = null;
  const panelInner = panel.querySelector('.nav-panel-inner');

  const focusablesSelector =
    'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

  const getFocusables = () =>
    Array.from(panel.querySelectorAll(focusablesSelector)).filter(
      (el) => el instanceof HTMLElement && !el.hasAttribute('disabled')
    );

  const open = () => {
    lastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    panel.hidden = false;
    document.body.classList.add('no-scroll');
    toggle.setAttribute('aria-expanded', 'true');

    if (!prefersReducedMotion && typeof gsap !== 'undefined') {
      gsap.killTweensOf(panel);
      gsap.killTweensOf(panelInner);
      gsap.set(panel, { autoAlpha: 0 });
      gsap.set(panelInner, { y: 10, opacity: 0 });
      gsap.to(panel, { autoAlpha: 1, duration: 0.22, ease: 'power2.out' });
      gsap.to(panelInner, { y: 0, opacity: 1, duration: 0.5, ease: 'power3.out', delay: 0.04 });
      gsap.fromTo(
        panel.querySelectorAll('.nav-panel-links a'),
        { y: 10, opacity: 0 },
        { y: 0, opacity: 1, duration: 0.55, stagger: 0.04, ease: 'power3.out', delay: 0.08 }
      );
    }

    // Allow paint before focusing.
    window.setTimeout(() => {
      const focusables = getFocusables();
      (focusables[0] || closeBtn).focus();
    }, 0);
  };

  const close = () => {
    const finalize = () => {
      panel.hidden = true;
      panel.style.opacity = '';
      panel.style.visibility = '';
      document.body.classList.remove('no-scroll');
      toggle.setAttribute('aria-expanded', 'false');
      if (lastFocused) lastFocused.focus();
    };

    if (!prefersReducedMotion && typeof gsap !== 'undefined') {
      gsap.to(panelInner, { y: 8, opacity: 0, duration: 0.22, ease: 'power2.in' });
      gsap.to(panel, { autoAlpha: 0, duration: 0.22, ease: 'power2.in', onComplete: finalize });
      return;
    }

    finalize();
  };

  toggle.addEventListener('click', () => {
    if (panel.hidden) open();
    else close();
  });

  closeBtn.addEventListener('click', close);

  panel.addEventListener('click', (e) => {
    const target = e.target;
    if (!(target instanceof Node)) return;
    if (panelInner && !panelInner.contains(target)) close();
  });

  panel.querySelectorAll('a[href^="#"]').forEach((a) => {
    a.addEventListener('click', () => close());
  });

  document.addEventListener('keydown', (e) => {
    if (panel.hidden) return;

    if (e.key === 'Escape') {
      e.preventDefault();
      close();
      return;
    }

    // Focus trap
    if (e.key === 'Tab') {
      const focusables = getFocusables();
      if (focusables.length === 0) return;
      const first = focusables[0];
      const last = focusables[focusables.length - 1];

      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    }
  });
}

function splitWords(el) {
  // Convert text nodes into wrapped word spans while preserving nested elements.
  const walk = (node) => {
    const children = Array.from(node.childNodes);
    for (const child of children) {
      if (child.nodeType === Node.TEXT_NODE) {
        const text = child.textContent || '';
        if (!text.trim()) continue;

        const frag = document.createDocumentFragment();
        const parts = text.split(/(\s+)/);
        for (const part of parts) {
          if (part.trim() === '') {
            const s = document.createElement('span');
            s.className = 'ws';
            s.textContent = ' ';
            frag.appendChild(s);
            continue;
          }
          const w = document.createElement('span');
          w.className = 'w';
          w.textContent = part;
          frag.appendChild(w);
        }
        child.parentNode?.replaceChild(frag, child);
      } else if (child.nodeType === Node.ELEMENT_NODE) {
        walk(child);
      }
    }
  };
  walk(el);
}

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

  // Hero: word-level headline + staged UI entrance
  const headline = document.querySelector('.hero-headline');
  if (headline && !headline.dataset.split) {
    splitWords(headline);
    headline.dataset.split = 'true';
  }

  const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
  tl.fromTo('.hero-content', { opacity: 0 }, { opacity: 1, duration: 0.3 });
  tl.from('.kicker', { opacity: 0, y: 14, duration: 0.7 }, '<');
  tl.from(
    '.hero-headline .w',
    {
      opacity: 0,
      yPercent: 70,
      rotateX: 75,
      transformOrigin: '50% 100%',
      duration: 1.0,
      stagger: 0.018,
    },
    '-=0.25'
  );
  tl.from('.hero-subhead', { opacity: 0, y: 12, duration: 0.7 }, '-=0.55');
  tl.from('.hero-ctas .btn', { opacity: 0, y: 10, duration: 0.65, stagger: 0.08 }, '-=0.55');
  tl.from('.hero-metrics .metric', { opacity: 0, y: 10, duration: 0.65, stagger: 0.06 }, '-=0.5');

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

  // Marquee: scrubbed speed bias on scroll (JS overrides CSS fallback)
  const marquee = document.querySelector('[data-marquee]');
  if (marquee) {
    marquee.style.animation = 'none';
    const tween = gsap.to(marquee, {
      xPercent: -50,
      duration: 18,
      ease: 'none',
      repeat: -1,
    });

    ScrollTrigger.create({
      trigger: '.marquee',
      start: 'top bottom',
      end: 'bottom top',
      onUpdate(self) {
        // velocity-based timescale with clamped bounds
        const v = Math.min(2600, Math.abs(self.getVelocity()));
        const boost = 1 + v / 2200; // ~1..2.18
        const dir = self.direction === 1 ? 1 : 0.85;
        tween.timeScale(Math.max(0.8, Math.min(2.4, boost * dir)));
      },
    });
  }

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

  // Process: active step state + micro depth on scroll
  const steps = gsap.utils.toArray('.step');
  steps.forEach((step) => {
    ScrollTrigger.create({
      trigger: step,
      start: 'top 62%',
      end: 'bottom 48%',
      onEnter: () => step.classList.add('is-active'),
      onEnterBack: () => step.classList.add('is-active'),
      onLeave: () => step.classList.remove('is-active'),
      onLeaveBack: () => step.classList.remove('is-active'),
    });

    gsap.fromTo(
      step,
      { rotateX: 6, transformPerspective: 900, transformOrigin: '50% 50%' },
      {
        rotateX: 0,
        ease: 'none',
        scrollTrigger: {
          trigger: step,
          start: 'top bottom',
          end: 'bottom top',
          scrub: true,
        },
      }
    );
  });
}

document.addEventListener('DOMContentLoaded', () => {
  prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!prefersReducedMotion && typeof gsap !== 'undefined') {
    document.documentElement.classList.add('js-motion');
  }
  initCursorGlow();
  initMobileNav();
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
