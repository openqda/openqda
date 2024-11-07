import {reactive} from 'vue'
import {toRefs} from '@vueuse/core'

const state = reactive({
    target: null,
    challenge: null,
    message: null
})

export const useDeleteDialog = () => {
    const { target, challenge, message } = toRefs(state)
    const open = ({ message, target, challenge }) => {
        state.target = target
        state.challenge = challenge
        state.message = message
    }
    return { target, challenge, open, message }
}
