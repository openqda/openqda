import Quill from 'quill';
import { changeRGBOpacity } from '../../../utils/color/changeRGBOpacity.js'

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

    highlight ({ id, color, start, length, active }) {
        if (!active) {
            this.quill.formatText(start, length, {
                background: 'transparent'
            })
        }
        else {
            const background = changeRGBOpacity(color,1)
            this.quill.formatText(start, length, {
                background,
                id,
            })
        }
    }

    remove ({ start, end }) {
        const length = end - start
        this.quill.formatText(start, length, {
            background: null,
            id: null,
        })
    }
}
