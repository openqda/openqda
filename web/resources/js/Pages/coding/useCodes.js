import { usePage } from '@inertiajs/vue3'
import { computed, reactive, watch } from 'vue'
import { Codebooks } from './codebooks/Codebooks.js'
import { Codes } from './codes/Codes.js'
import { Selections } from './Selections.js'

const state = reactive({
    loaded: false
})

export const useCodes = () => {
    const { allCodes, codebooks, projectId } = usePage().props
    const codeStore = Codes.by(projectId)
    const codebookStore = Codebooks.by(projectId)
    const selectionStore = Selections.by(projectId)

    /**
     * To lazy load codes and codebooks
     */
    const initCodebooks = () => {
        if (state.loaded) { return }

        codebooks.forEach(book => {
            book.active = true
        })

        const selections = []
        const codeList = []
        const parseCodes = (codes, parent = null) => {
            codes.forEach(code => {
                code.active = true
                code.parent = parent

                // parse selections
                if (code.text?.length) {
                    code.text.forEach(selection => {
                        selection.start = Number(selection.start)
                        selection.end = Number(selection.end)
                        selection.length = selection.end - selection.start
                        selection.code = code.id
                        selections.push(selection)
                    })
                }
                codeList.push(code)
                if (code.children?.length) {
                    parseCodes(code.children, code)
                }
            })
        }
        parseCodes(allCodes)
        codebookStore.load(codebooks)
        codeStore.load(codeList)
        selectionStore.load(selections)
        state.loaded = true
    }

    const computedCodes = computed({
        get: () => codeStore.all().filter(c => !c.parent)
    })

    const computedCodebooks = computed({
        get: () => codebookStore.all()
    })

    const toggleCodebook = async ({ id }) => {
       const active = Codebooks.toggle(projectId, id)
       codeStore.all().forEach((code) => {
           if (code.codebook === id) {
               code.active = active
           }
       })
       // await Codebooks.save()
    }

    const toggleCode = async (code) => {
        codeStore.toggle(code.id)
    }

    const selections = computed(() => {
        const selections = [...selectionStore.all()] //.sort((a, b) => b.length - a.length)

        return selections.map(entry => {
            const selection = {...entry}
            // xxx code duplicates might occur and we
            // have to be forgiving and support these
            const type = typeof selection.code
            if (type === 'string') {
                  selection.code = codeStore.entry(selection.code)
            }
            return selection
        })
    })
    const codesInRange = (index, codeId) => {
        const current = selections.value.filter(({ start, end })  => {
            return start <= index && end >= index
        })
        const codes = current.map(sel => sel.code)
        let selection
        if (codeId) {
            selection = current.find(sel => sel.code.id === codeId)
        }
        return {
            selection,
            codes
        }
    }

    return {
        codes: computedCodes,
        codebooks: computedCodebooks,
        selections,
        selectionStore,
        codesInRange,
        toggleCodebook,
        toggleCode,
        initCodebooks,
        sorter: {
            byIndex: useCodes
        }
    }
}
