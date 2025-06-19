import Quill from 'quill';

const Module = Quill.import('core/module');

export class SelectionHash extends Module {
  constructor(quill, options) {
    super(quill, options);
    this.quill = quill;
    this.options = options;
    this.container = document.querySelector(options.container);
    this.listener = this.updateSelection.bind(this);
    this.quill.on('selection-change', this.listener);
    this.quill.on('text-change', this.listener);
    this.value = '0:0';

    // Account for initial contents
    setTimeout(() => this.updateSelection(), 1000);
  }

  clearValue() {
    while (this.container.firstChild) {
      this.container.removeChild(this.container.firstChild);
    }
  }

  updateSelection(range /* , oldRange, source */) {
    this.clearValue();
    if (range && typeof range.index === 'number') {
      const newValue = `${range.index}:${range.index + range.length}`;
      this.container.textContent = newValue;
      this.value = newValue;
    } else {
      this.container.textContent = this.value;
    }
  }

  dispose() {
    this.clearValue();
    this.quill.off('selection-change', this.listener);
    this.quill.off('text-change', this.listener);
  }
}
