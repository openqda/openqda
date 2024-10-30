import { reactive, toRefs } from 'vue'

const state = reactive({
    selected: null,
    toDelete: []
})

export const useSelections = () => {
    const { selected, toDelete } = toRefs(state)
    const select = ({ code, parent }) => {
        state.selected = { code, parent };
    };
    const markToDelete = (codes) => {
        state.toDelete = codes
    }
    return {
        selected,
        select,
        toDelete,
        markToDelete,
    }
}
