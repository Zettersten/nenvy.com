export function splitWords(el) {
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
        // Some elements (like gradient-clipped text) must remain a single text run.
        // Treat them as an atomic "word" so animations still work without breaking paint.
        const elChild = /** @type {HTMLElement} */ (child);
        if (elChild.matches?.('.accent, [data-split-atomic]')) {
          elChild.classList.add('w');
          continue;
        }
        walk(elChild);
      }
    }
  };
  walk(el);
}

