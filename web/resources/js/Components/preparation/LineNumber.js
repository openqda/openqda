import { debounce } from '../../utils/dom/debounce.js'

export class LineNumber {
  constructor(quill, options) {
    this.quill = quill;
    this.options = options;
    this.container = document.querySelector(options.container);
    quill.on('text-change', this.update.bind(this));
    window.addEventListener('resize', debounce(this.update.bind(this), 100));
    this.update(); // Account for initial contents
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
}
