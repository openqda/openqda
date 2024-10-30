import { toRefs, reactive } from 'vue'

const state = reactive({
    prevRange: null,
    range: null,
})

/**
 * Composable for editor selection ranges
 */
export const useRange = () => {
    const { range, prevRange } = toRefs(state)
    const setRange = data => {
        if (data !== null) {
            const { index, length } = data
            const end = index + length
            const start = index
            const r = { index, length, start, end }
            state.prevRange = r
            state.range = r
        }
        else {
            state.range = data
        }
    }
    return {
        range, prevRange, setRange
    }
}
