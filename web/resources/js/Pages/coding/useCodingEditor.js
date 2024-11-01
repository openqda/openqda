import { computed, shallowReactive } from 'vue'

const state = shallowReactive({
    instance: null,
    timeout: null
})

export const useCodingEditor = () => {
    const setInstance = (newInstance) => {
        if (newInstance) {
            state.instance = newInstance
        }
    }

    const focusSelection = (range) => {
        const { instance } = state;
        const blot = instance.getLeaf(range.start)
        let node = blot?.[0]?.domNode
        node = node.nodeName === '#text' ? node.parentNode : node
        if (node) {
            node.scrollIntoView({
                block: 'start',
                behavior: 'smooth',
            });
        }

        // FIXE: use scrollenv event, once it's widely available
        clearTimeout(state.timeout)
        state.timeout = setTimeout(() => instance.setSelection(range.start, range.end - range.start), 1000)
    }

    return {
        setInstance,
        focusSelection,
        dispose: () => state.instance = null,
        instance: () => state.instance
    }
}
