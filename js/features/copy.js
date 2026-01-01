import { qsa } from '../utils/dom.js';

export function initCopyButtons() {
  qsa('[data-copy]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const value = btn.getAttribute('data-copy') || '';
      if (!value) return;

      const labelEl = btn.querySelector('[data-copy-label]');
      const liveEl = btn.querySelector('[data-copy-live]');
      const original = labelEl?.textContent || btn.querySelector('span')?.textContent || btn.textContent || '';

      const setLabel = (text) => {
        if (labelEl) labelEl.textContent = text;
      };

      const announce = (text) => {
        if (!liveEl) return;
        // Clear first so repeated messages are announced reliably.
        liveEl.textContent = '';
        window.setTimeout(() => (liveEl.textContent = text), 10);
      };

      try {
        await navigator.clipboard.writeText(value);
        setLabel('Copied');
        announce('Copied to clipboard');
      } catch {
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
          announce('Copied to clipboard');
        } catch {
          setLabel('Copy failed');
          announce('Copy failed');
        }
        document.body.removeChild(input);
      }

      window.setTimeout(() => setLabel(original.trim() || 'Copy'), 1100);
    });
  });
}

