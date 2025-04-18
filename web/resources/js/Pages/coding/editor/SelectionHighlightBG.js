import Quill from 'quill';
import { changeOpacity } from '../../../utils/color/changeOpacity.js';
import { segmentize } from '../../../domain/selections/Intersections.js';
import { cn } from '../../../utils/css/cn.js';

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

    // TODO add border
  }

  /** @deprecated */
  overlap({ start, length }) {
    this.quill.formatText(start, length, {
      class: 'border-b border-2 border-background',
    });
  }

  add(entries) {
    if (!entries || !entries.length) return;
    const selected = entries.map((e) => ({
      x: e.start,
      y: e.end,
      c: e.code,
    }));
    const segments = segmentize(selected);
    segments.forEach((segment) => {
      const activeCodes = segment.c.reduce(
        (acc, cur) => acc + (cur.active !== false ? 1 : 0),
        0
      );
      if (activeCodes > 1) {
        const format = this.quill.getFormat(segment.x, segment.y - segment.x);
        format.class = cn(format.class, 'border border-primary');
        format.background = 'transparent';
        format.title = `${segment.c.length} overlapping codes: ${segment.c.map((c) => c.name).join(',')} [${segment.x}:${segment.y}]. Right-click to open menu`;
        this.quill.formatText(segment.x, segment.y - segment.x, format);
      } else {
        // XXX: there might be inactive codes in the list, so we need to search
        // for it and fall back to the first code, of none is found
        const code =
          segment.c.find((code) => code.active !== false) ?? segment.c[0];
        if (!code)
          return console.warn(
            `Expected code linked to segment ${segment.x}:${segment.y}, got ${segment.c}`
          );
        this.highlight({
          id: code.id,
          title: code.name,
          color: code.color,
          start: segment.x,
          length: segment.y - segment.x,
          active: code.active ?? true,
        });
      }
    });
  }

  /** @deprecated */
  highlight({ id, title, color, start, length, active }, { opacity } = {}) {
    if (!active) {
      const format = this.quill.getFormat(start, length);
      delete format.title;
      delete format.id;
      format.background = 'transparent';
      format.class = clearClasses(format.class);
      this.quill.formatText(start, length, format);
    } else {
      const selectionTitle = `${title} [${start}:${start + length}]. Right-click to open menu`;
      const background = changeOpacity(color, opacity);
      const format = this.quill.getFormat(start, length);
      format.class = cn(format.class, 'my-0 py-0');
      format.background = background;
      format.title = selectionTitle;
      format.id = id;
      this.quill.formatText(start, length, format);
    }
  }

  remove({ start, end }) {
    const length = end - start;
    const format = this.quill.getFormat(start, length);
    format.title = null;
    format.id = null;
    format.background = null;
    format.class = clearClasses(format.class);
    this.quill.formatText(start, length, format);
  }

  removeAll(selections) {
    selections.forEach((selection) => this.remove(selection));
  }
}

const classList = 'my-0 py-0 border border-primary'.split(' ');
const classes = new RegExp(classList.join('|'), 'gi');
const clearClasses = (c) => {
  if (!c) return c;
  if (typeof c === 'string') return c ? c.replace(classes, '') : c;
  if (c.length) return clearClasses(c.join(' '));
};
