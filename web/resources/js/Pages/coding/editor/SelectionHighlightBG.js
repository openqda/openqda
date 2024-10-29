import Quill from 'quill';

const Parchment = Quill.import('parchment');
const { Registry, Attributor, Scope } = Parchment
const IdAttributor = new Attributor('id', 'data-code-id', { scope: Scope.INLINE })

Quill.register(IdAttributor, true)
const Module = Quill.import('core/module');

export class SelectionHighlightBG extends Module {
    constructor(quill, options) {
        super(quill, options);
        this.quill = quill;
        this.options = options;
    }

    highlight ({ id, start, end, color }) {
        const length = end - start
        this.quill.formatText(start, length, {
            background: color,
            id,
        })
    }

    remove ({ start, end }) {
        const length = end - start
        this.quill.formatText(start, length, {
            background: null,
            id: null,
        })
    }
}
