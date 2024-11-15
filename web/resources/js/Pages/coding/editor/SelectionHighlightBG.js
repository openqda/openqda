import Quill from 'quill';
import { changeRGBOpacity } from '../../../utils/color/changeRGBOpacity.js';

const Parchment = Quill.import('parchment');
const { Attributor, Scope } = Parchment;
const IdAttributor = new Attributor('id', 'data-code-id', {
  scope: Scope.INLINE,
});
const TitleAttributor = new Attributor('title', 'title', {
  scope: Scope.INLINE,
});
const ClassAttributor = new Attributor('class', 'class', {
  scope: Scope.INLINE,
});

Quill.register(IdAttributor, true);
Quill.register(TitleAttributor, true);
Quill.register(ClassAttributor, true);
const Module = Quill.import('core/module');

export class SelectionHighlightBG extends Module {
  constructor(quill, options) {
    super(quill, options);
    this.quill = quill;
    this.options = options;

    this.active = {};
  }

  current(range) {
    const { index, length } = range ?? this.active ?? {};
    if (!length) return;

    const show = !!range;
    if (show) {
      this.active.index = index;
      this.active.length = length;
    }
    this.quill.formatText(index, length, {
      class: show ? 'border-b border-t border-secondary shadow-xl' : '',
    });
  }

  overlap({ start, length }) {
    this.quill.formatText(start, length, {
      class: 'border-b border-2 border-background',
    });
  }

  highlight({ id, title, color, start, length, active }, { opacity } = {}) {
    if (!active) {
      this.quill.formatText(start, length, {
        background: 'transparent',
      });
    } else {
      const selectionTitle = `${title} - ${start}:${start + length} (Right-click to open menu)`;
      const background = changeRGBOpacity(color, opacity);
      this.quill.formatText(start, length, {
        background,
        class:
          'my-0 py-0 border-b border-t border-transparent hover:border-secondary hover:shadow-xl',
        title: selectionTitle,
        id,
      });
    }
  }

  remove({ start, end }) {
    const length = end - start;
    this.quill.formatText(start, length, {
      background: null,
      title: null,
      id: null,
    });
  }
}
