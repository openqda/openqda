import {reactive, toRefs} from 'vue'

const state = reactive({
    schema: null,
    id: null
})

export const useCreateDialog = () => {
    const { schema, id } = toRefs(state)
    const open = ({ id, schema }) => {
        state.schema = schema
        state.id = id
    }

    return { id, schema, open }
}
