import { debounce } from '../utils/dom/debounce.js'
import Quill from 'quill';
const Module = Quill.import('core/module');

const listeners = new WeakMap()

export class LineNumber extends Module {
  constructor(quill, options) {
    super(quill, options);
    this.quill = quill;
    this.options = options;
    this.container = document.querySelector(options.container);
    listeners.set(this, { resize: debounce(this.update.bind(this), 100), textChange: this.update.bind(this) });
    this.quill.on('text-change', listeners.get(this).textChange);
    window.addEventListener('resize', listeners.get(this).resize);

    // Account for initial contents
    setTimeout(() => this.update(), 1000)
  }

  update() {
    // Clear old nodes
    while (this.container.firstChild) {
      this.container.removeChild(this.container.firstChild);
    }

    const lines = this.quill.getLines();

    // Add new nodes
    for (let i = 1; i < lines.length + 1; i++) {
      const prev = lines[i - 1].domNode;
      const height = getComputedStyle(prev).height;
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
  }


  dispose () {
      const { resize, textChange } = listeners.get(this)
      window.removeEventListener('resize', resize);
      this.quill.off('text-change', textChange);
      listeners.delete(this)
  }
}
