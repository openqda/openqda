import { reactive, toRefs } from 'vue'

const state = reactive({
    invivoText: null
})

export const useInvivoText = () => {
    const { invivoText } = toRefs(state)
    const set = txt => {
        state.invivoText = txt
    }
    const unset = () => {
        state.invivoText = null
    }

    return {
        invivoText,
        set,
        unset
    }
}
