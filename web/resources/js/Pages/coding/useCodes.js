import {usePage} from '@inertiajs/vue3'
import {computed, reactive} from 'vue'
import {Codebooks} from './codebooks/Codebooks.js'
import {Codes} from './codes/Codes.js'
import {Selections} from './selections/Selections.js'
import {randomColor} from '../../utils/random/randomColor.js'

const state = reactive({
    loaded: false
})

const orders = {}
const getOrder = (codebook) => {
    if (!(codebook in orders)) {
        orders[codebook] = 0
    }
    return orders[codebook]++
}

const createCodeSchema = ({ title, description, color, codebooks }) => ({
    title: {
        type: String,
        placeholder: 'Name of the code',
        defaultValue: title
    },
    description: {
        type: String,
        placeholder: 'Code description, optional',
        formType: 'textarea',
        defaultValue: description
    },
    color: {
        type: String,
        formType: 'color',
        defaultValue: color ?? randomColor({ type: 'hex' })
    },
    codebookId: {
        type: Number,
        label: 'Codebook',
        defaultValue: codebooks?.[0]?.id,
        options: codebooks?.map((c) => ({
            value: c.id,
            label: c.name
        }))
    }
})

export const useCodes = () => {
    const { allCodes, codebooks, projectId, source } = usePage().props
    const codeStore = Codes.by(projectId)
    const codebookStore = Codebooks.by(projectId)
    const selectionStore = Selections.by(projectId)

    /**
     * To lazy load codes and codebooks
     */
    const initCodebooks = () => {
        if (state.loaded) {
            return
        }
        codebooks.forEach((book) => {
            book.active = true
        })

        const selections = []
        const codeList = []
        const parseCodes = (codes, parent = null) => {
            codes.forEach((code) => {
                code.active = true
                code.parent = parent
                code.order = getOrder(code.codebook)

                // parse selections
                if (code.text?.length) {
                    code.text.forEach((selection) => {
                        selection.start = Number(selection.start)
                        selection.end = Number(selection.end)
                        selection.length = selection.end - selection.start
                        selection.code = code
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

        codebookStore.add(...codebooks)
        codeStore.add(...codeList)
        selectionStore.add(...selections)
        state.loaded = true
    }

    //---------------------------------------------------------------------------
    // CREATE
    //---------------------------------------------------------------------------
    const createCode = async (options) => {
        ['title', 'color', 'codebookId'].forEach((key) => {
            if (!options[key]) {
                throw new Error(`${key} is required!`)
            }
        })

        const { response, error, code } = await Codes.create({ projectId, ...options })
        if (error) throw error
        if (response.status < 400) {
            return code
        }
    }
    //---------------------------------------------------------------------------
    // UPDATE
    //---------------------------------------------------------------------------
    const updateCode = async ({ id, ...value }) => {
        const entry = codeStore.entry(id)
        const diff = {}
        const restore = {}

        if (value.title !== entry.name) {
            diff.name = value.title
            restore.name = entry.name
        }
        if ((value.description ?? '') !== entry.description) {
            diff.description = (value.description ?? '')
            restore.description = entry.description
        }

        if (value.color !== entry.color) {
            diff.color = value.color
            restore.color = entry.color
        }

        if (Object.keys(diff).length === 0) {
            throw new Error(`Make at least one change in order to update`)
        }

        // optimistic UI
        codeStore.update(id, diff)
        const updatedCode = codeStore.entry(id)
        selectionStore.update((selections) => {
            const updated = []
            selections.forEach(selection => {
                if (selection.code.id === updatedCode.id) {
                    selection.code = updatedCode
                    updated.push(selection)
                }
            })
            return updated
        })

        let response
        const handle = res => {
            if (res.error) {
                codeStore.update(id, restore)
                throw res.error
            }
            if (res.response.status >= 400) {
                codeStore.update(id, restore)
                throw res.response.data.message
            }
        }
        if ('title' in diff) {
            response = await Codes.updateTitle({ projectId, title: diff.name, code: entry })
            handle(response)
        }
        if ('description' in diff) {
            response = await Codes.updateDescription({ projectId, source, description: diff.description, code: entry })
            handle(response)
        }
        if ('color' in diff) {
            response = await Codes.updateColor({ projectId, code: entry, color: diff.color })
            handle(response)
        }

        return true
    }
    //---------------------------------------------------------------------------
    // DELETE
    //---------------------------------------------------------------------------
    const deleteCode = async (code) => {
        // here we do not optistic UI, because
        // adding-back will destroy the code-order
        const { response, error } = await Codes.delete({ projectId, source, code })
        if (error) throw error
        if (response.status >= 400) throw new Error(response.data.message)

        codeStore.remove(code.id)
        const selections = selectionStore.all().filter(selection => selection.code.id === code.id)
        selectionStore.remove(...selections.map(s => s.id))
        return true
    }

    const computedCodes = computed(() => {
        return codeStore.all().filter((c) => !c.parent)
    })

    const computedCodebooks = computed(() => {
        return codebookStore.all()
    })

    const toggleCodebook = async (codebook) => {
        const active = !codebook.active
        codebookStore.active(codebook.id, active)
        codeStore.all().forEach((code) => {
            if (code.codebook === codebook.id && code.active !== active) {
                code.active = active
            }
        })
    }

    const toggleCode = async (code) => {
        const codes = codeStore.toggle(code.id)

        // notify selections updated
        const updatedSelections = []
        const addSelections = (code) => {
            if (code.text?.length) {
                code.text.forEach((selection) =>
                    updatedSelections.push(selectionStore.entry(selection.id))
                )
            }
        }
        codes.forEach((code) => addSelections(code))

        // reactivate codebook, in case it was inactive
        const codebook = codebookStore.entry(code.codebook)
        if (codebook && code.active && !codebook.active) {
            codebookStore.active(codebook.id, true)
        }

        const intersecting = selectionStore.all().filter(s => {

        })

        selectionStore.observable.run('updated', updatedSelections)
    }

    const selections = computed(() => {
        return selectionStore.all().toSorted((a, b) => {
            const length = a.length - b.length
            const start = a.start - b.start
            return length + start
        })
    })

    const overlaps = computed(() => {
        return []
    })

    const selectionsByIndex = (index) => {
        return selections.value.filter(({ start, end }) => {
            return start <= index && end >= index
        })
    }
    const observe = (name, callbacks) => {
        switch (name) {
            case codeStore.key:
                return codeStore.observe(callbacks)
            case codebookStore.key:
                return codebookStore.observe(callbacks)
            case selectionStore.key:
                return selectionStore.observe(callbacks)
            default:
                throw new Error(`Unknown observe name: ${name}`)
        }
    }


    return {
        createCode,
        createCodeSchema,
        updateCode,
        deleteCode,
        codes: computedCodes,
        getCode: (id) => codeStore.entry(id),
        observe,
        codebooks: computedCodebooks,
        selections,
        overlaps,
        selectionStore,
        toggleCodebook,
        toggleCode,
        initCodebooks,
        selectionsByIndex,
        sorter: {
            byIndex: useCodes
        }
    }
}
