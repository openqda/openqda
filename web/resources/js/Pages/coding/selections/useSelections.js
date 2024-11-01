import { reactive, toRefs } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { Selections } from '../Selections.js'

const state = reactive({
    selected: null,
    current: null,
    toDelete: []
})

export const useSelections = () => {
    const { projectId, source } = usePage().props
    const { selected, toDelete, current } = toRefs(state)
    const select = ({ code, parent }) => {
        state.selected = { code, parent };
        return true
    };
    const deleteSelection = async (selection) => {
        const sourceId = source.id
        const code = selection.code
        const { response, error } = await Selections.delete({ projectId, selection, code, sourceId })
        return !error && response.status < 400
    }
    const markToDelete = (codes) => {
        state.toDelete = codes
    }
    const markCurrentByCodeId = (selection) => {
        state.current = selection
    }
    return {
        selected,
        select,
        deleteSelection,
        current,
        toDelete,
        markToDelete,
        markCurrentByCodeId,
    }
}
