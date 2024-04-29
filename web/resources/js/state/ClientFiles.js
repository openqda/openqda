import { reactive } from 'vue'

export const ClientFiles = {}

const store = reactive({
    name: null,
    file: null,
    content: null,
    set ({ name, file, content }) {
        this.name = name
        this.file = file
        this.content = content
    },
    get () {
        return {
            name: this.name,
            file: this.file,
            contenmt: this.content
        }
    }
})

ClientFiles.set = ({ name, file, content }) => {
    store.set({ name, file, content })
}

ClientFiles.get = () => store.get()
