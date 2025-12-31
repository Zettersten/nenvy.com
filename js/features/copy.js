import { qsa } from '../utils/dom.js';

export function initCopyButtons() {
  qsa('[data-copy]').forEach((btn) => {
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

