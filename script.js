// Three.js WebGL Animation for Hero Section
let scene, camera, renderer, geometry, material, mesh;
let mouseX = 0, mouseY = 0;
let targetX = 0, targetY = 0;
let windowHalfX = window.innerWidth / 2;
let windowHalfY = window.innerHeight / 2;

function initThreeJS() {
    const canvas = document.getElementById('canvas');
    if (!canvas) return;

    // Scene setup
    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    renderer = new THREE.WebGLRenderer({ 
        canvas: canvas,
        alpha: true,
        antialias: true 
    });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

    // Create multiple geometric shapes
    const group = new THREE.Group();
    
    // Main geometric shape - subtle and elegant
    const geometry1 = new THREE.IcosahedronGeometry(2, 0);
    const material1 = new THREE.MeshBasicMaterial({
        color: 0xffffff,
        wireframe: true,
        transparent: true,
        opacity: 0.15
    });
    const mesh1 = new THREE.Mesh(geometry1, material1);
    group.add(mesh1);

    // Secondary shape
    const geometry2 = new THREE.OctahedronGeometry(1.5, 0);
    const material2 = new THREE.MeshBasicMaterial({
        color: 0xffffff,
        wireframe: true,
        transparent: true,
        opacity: 0.1
    });
    const mesh2 = new THREE.Mesh(geometry2, material2);
    mesh2.rotation.x = Math.PI / 4;
    mesh2.rotation.y = Math.PI / 4;
    group.add(mesh2);

    // Tertiary shape
    const geometry3 = new THREE.TetrahedronGeometry(1, 0);
    const material3 = new THREE.MeshBasicMaterial({
        color: 0xffffff,
        wireframe: true,
        transparent: true,
        opacity: 0.08
    });
    const mesh3 = new THREE.Mesh(geometry3, material3);
    mesh3.rotation.x = -Math.PI / 4;
    mesh3.rotation.y = -Math.PI / 4;
    group.add(mesh3);

    scene.add(group);
    geometry = group;
    
    camera.position.z = 5;

    // Mouse move handler
    document.addEventListener('mousemove', onDocumentMouseMove, false);
    window.addEventListener('resize', onWindowResize, false);

    animate();
}

function onDocumentMouseMove(event) {
    // Reduced sensitivity - normalize mouse position more gently
    const normalizedX = (event.clientX - windowHalfX) / window.innerWidth;
    const normalizedY = (event.clientY - windowHalfY) / window.innerHeight;
    mouseX = normalizedX * 0.3; // Much smaller multiplier
    mouseY = normalizedY * 0.3;
}

function onWindowResize() {
    windowHalfX = window.innerWidth / 2;
    windowHalfY = window.innerHeight / 2;
    
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);
}

function animate() {
    requestAnimationFrame(animate);

    // Smooth interpolation for cursor following
    targetX += (mouseX - targetX) * 0.05;
    targetY += (mouseY - targetY) * 0.05;

    if (geometry) {
        // Rotate based on cursor position - much slower
        geometry.rotation.x += targetY * 0.15;
        geometry.rotation.y += targetX * 0.15;
        
        // Subtle continuous rotation
        geometry.rotation.z += 0.002;

        // Scale based on cursor distance from center - reduced effect
        const scale = 1 + (Math.abs(targetX) + Math.abs(targetY)) * 0.1;
        geometry.scale.set(scale, scale, scale);
    }

    renderer.render(scene, camera);
}

// Smooth scroll enhancement
function initSmoothScroll() {
    // Add smooth scroll behavior for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                const offsetTop = target.offsetTop - 80;
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Parallax effect for sections
function initParallax() {
    const sections = document.querySelectorAll('section');
    
    function handleScroll() {
        const scrollY = window.pageYOffset;
        
        sections.forEach(section => {
            const rect = section.getBoundingClientRect();
            const isVisible = rect.top < window.innerHeight && rect.bottom > 0;
            
            if (isVisible && section.id !== 'hero') {
                const speed = 0.5;
                const yPos = -(scrollY * speed);
                section.style.transform = `translateY(${yPos}px)`;
            }
        });
    }
    
    // Throttle scroll events
    let ticking = false;
    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                handleScroll();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
}

// Intersection Observer for fade-in animations
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe capability cards
    document.querySelectorAll('.capability-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        observer.observe(card);
    });

    // Observe subscription cards
    document.querySelectorAll('.subscription-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
        observer.observe(card);
    });
}

// Initialize everything when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initThreeJS();
    initSmoothScroll();
    initScrollAnimations();
    
    // Initialize parallax only on desktop
    if (window.innerWidth > 768) {
        initParallax();
    }
});

// Handle page visibility to pause/resume animation
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        // Page is hidden, could pause animation here if needed
    } else {
        // Page is visible, resume animation
    }
});
