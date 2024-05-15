export class LineNumber {
  constructor(quill, options) {
    this.quill = quill;
    this.options = options;
    this.container = document.querySelector(options.container);
    quill.on('text-change', this.update.bind(this));
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
      const height = lines[i - 1].domNode.offsetHeight;

      const node = document.createElement('div');

      // showcase - empty lines
      if (lines[i - 1].domNode.innerHTML === '<br>') {
        node.style.color = 'red';
      }

      node.style.lineHeight = `${height}px`;
      node.innerHTML = `${i}`;
      this.container.appendChild(node);
    }
  }
}
