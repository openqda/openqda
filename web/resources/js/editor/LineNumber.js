import { debounce } from '../utils/dom/debounce.js';
import Quill from 'quill';

const Module = Quill.import('core/module');

/**
 * Render line numbers for a given quill instance.
 */
export class LineNumber extends Module {
  constructor(quill, options) {
    super(quill, options);

    this.quill = quill;
    this.options = options;
    this.container = document.querySelector(options.container);
    this.listeners = {
      resize: debounce(
        this.update.bind(this, 'resize'),
        options.resize?.debounce ?? 100
      ),
      textChange: debounce(
        this.update.bind(this, 'format'),
        options.textChange?.debounce ?? 300
      ),
    };

    this.quill.on('text-change', this.listeners.textChange);
    window.addEventListener('resize', this.listeners.resize);
  }

  /**
   * Render the lines into the given container.
   * @return {boolean} returns false if height was not determinable,
   *   otherwise returns true
   */
  update(type, delta /*, old, source */) {
    if (!delta?.ops) return false;

    // Clear old nodes - this is very performance demanding
    // which is why text changes should be done in bulk
    // or debounced when done programmatically
    // however, for user-interaction this is fine because
    // updates occur in much larger intervals (> 50ms)
    while (this.container.firstChild) {
      this.container.removeChild(this.container.firstChild);
    }

    const lines = this.quill.getLines();

    for (let i = 1; i < lines.length + 1; i++) {
      const prev = lines[i - 1].domNode;
      const height = getComputedStyle(prev).height;

      // if layout / computations are not finished, once the editor
      // has been mounted, the line-height might still be 'auto',
      // which will cause a broken result
      // In this case we have to "wait and retry" in a few milliseconds
      if (height === 'auto') {
        return false;
      }

      const node = document.createElement('p');

      // showcase - empty lines
      if (prev.innerHTML === '<br>' || prev.innerText === '\n') {
        node.style.color = 'grey';
      }
      node.style.borderWidth = '0px';
      node.style.lineHeight = `${height}`;
      node.innerHTML = `${i}`;
      this.container.appendChild(node);
    }

    return true;
  }

  dispose() {
    const { resize, textChange } = this.listeners;
    window.removeEventListener('resize', resize);
    this.quill.off('text-change', textChange);
    this.listeners = {};
  }
}
