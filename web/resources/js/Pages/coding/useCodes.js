import { usePage } from '@inertiajs/vue3'
import { computed, reactive } from 'vue'
import { Codebooks } from './codebooks/Codebooks.js'
import { Codes } from './codes/Codes.js'
import { Selections } from './Selections.js'

const state = reactive({
    loaded: false
})

export const useCodes = () => {
    const { allCodes, codebooks, projectId } = usePage().props

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
        Codebooks.by(projectId).load(codebooks)
        Codes.by(projectId).load(codeList)
        Selections.by(projectId).load(selections)
        state.loaded = true
    }

    const computedCodes = computed({
        get: () => Codes.entries(projectId).filter(c => !c.parent)
    })

    const computedCodebooks = computed({
        get: () => Codebooks.entries(projectId)
    })

    const toggleCodebook = async ({ id }) => {
       const active = Codebooks.toggle(projectId, id)
       Codes.entries(projectId).forEach((code) => {
           if (code.codebook === id) {
               code.active = active
           }
       })
       // await Codebooks.save()
    }

    const toggleCode = async (code) => {
        Codes.by(projectId).toggle(code.id)
    }

    const selections = computed(() => {
        const selections = Selections.by(projectId).all().sort((a, b) => b.length - a.length)
        const codes = Codes.by(projectId)

        return selections.map(selection => {
            // xxx code duplicates might occur and we
            // have to be forgiving and support these
            selection.code = typeof selection.code === 'object'
                ? selection.code
                : codes.entry(selection.code)
            return selection
        })
    })

    const codesInRange = (index) => {
        const current = selections.value.filter(({ start, end })  => {
            return start <= index && end >= index
        })
        return current.map(sel => sel.code)
    }

    return {
        codes: computedCodes,
        codebooks: computedCodebooks,
        selections,
        codesInRange,
        toggleCodebook,
        toggleCode,
        initCodebooks
    }
}
