import { reactive, toRefs } from 'vue'
import { Codebooks } from '../../Pages/coding/codebooks/Codebooks.js'

const state = reactive({
    codebook: null,
    schema: null
})

export const useCodebookCreate = () => {
    const { codebook, schema } = toRefs(state)
    const open = (cb) => {
        state.codebook = cb
        state.schema = cb  ? Codebooks.schemas.update(cb) : Codebooks.schemas.create()
    }
    const close = () => {
        state.codebook = null
    }

    return {
        codebook,
        schema,
        open,
        close
    }
}
