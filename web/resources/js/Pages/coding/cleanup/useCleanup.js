import { reactive, toRef } from 'vue'

const state = reactive({})

export const useCleanup = () => {
    const entries = toRef(state);

    const add = entry => {
        state[entry.id] = entry
    }

    const remove = id => {
        delete state[id];
    }

    return {
        add, remove, entries
    }
}
