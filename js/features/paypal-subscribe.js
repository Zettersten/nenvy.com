import { qs } from '../utils/dom.js';

const PAYPAL_SDK_SRC =
  'https://www.paypal.com/sdk/js?client-id=ATnObauWTeaii36d5zifRVm7VcG5a34SO1BW0vBdMA7vTOD7hGOoPPkcJEb-RBsSuuVI27oWVSTa2SJ5&vault=true&intent=subscription';

function loadScriptOnce(src) {
  return new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[src="${src}"]`);
    if (existing) {
      if (typeof window.paypal !== 'undefined') resolve();
      else existing.addEventListener('load', () => resolve(), { once: true });
      return;
    }

    const s = document.createElement('script');
    s.src = src;
    s.async = true;
    s.defer = true;
    s.dataset.sdkIntegrationSource = 'button-factory';
    s.addEventListener('load', () => resolve(), { once: true });
    s.addEventListener('error', () => reject(new Error('PayPal SDK failed to load')), { once: true });
    document.head.appendChild(s);
  });
}

export function initPaypalSubscribe({ reducedMotion }) {
  const container = qs('#paypal-button-container-P-2LH401466F9693536NFKFLVA');
  if (!container) return;

  const planId = container.getAttribute('data-paypal-plan-id') || 'P-2LH401466F9693536NFKFLVA';
  const status = qs('.paypal-status');
  const setStatus = (msg) => {
    if (!status) return;
    status.textContent = msg;
  };

  const render = async () => {
    if (container.dataset.paypalRendered === 'true') return;
    container.dataset.paypalRendered = 'true';

    try {
      setStatus('Loading secure checkout…');
      await loadScriptOnce(PAYPAL_SDK_SRC);
      if (typeof window.paypal === 'undefined') throw new Error('PayPal SDK unavailable');

      window.paypal
        .Buttons({
          style: {
            shape: 'rect',
            color: 'black',
            layout: 'horizontal',
            label: 'subscribe',
          },
          createSubscription(data, actions) {
            return actions.subscription.create({ plan_id: planId });
          },
          onApprove(data) {
            setStatus(`Subscription approved. ID: ${data.subscriptionID}`);
          },
          onError() {
            setStatus('Checkout failed to load. Please email erik@nenvy.com.');
          },
        })
        .render('#paypal-button-container-P-2LH401466F9693536NFKFLVA');

      setStatus('');
    } catch {
      setStatus('Checkout failed to load. Please email erik@nenvy.com.');
    }
  };

  // Reduced motion shouldn’t block checkout; still lazy-load for performance.
  const io = new IntersectionObserver(
    (entries) => {
      if (entries.some((e) => e.isIntersecting)) {
        io.disconnect();
        render();
      }
    },
    { rootMargin: '800px 0px' }
  );
  io.observe(container);

  // Keyboard users might tab into the section before it intersects.
  container.addEventListener('focusin', render, { once: true });

  // If the user is already deep-linked, load immediately.
  if (location.hash === '#subscriptions') render();
}

