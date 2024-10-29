import { toRefs, reactive } from 'vue'

export const useContextMenu = () => {
    const { selected, openWith, toDelete } = toRefs(state)

    const select = code => {
        selected.value = code
    }
    const open = (codeId) => {
        openWith.value = codeId
    }

    const markToDelete = (codes) => {
        toDelete.value = codes
    }

    return {
        selected,
        select,
        openWith,
        open,
        toDelete,
        markToDelete,
    }
}

const state = reactive({
    selected: null,
    openWith: null,
    toDelete: []
})
