import {AbstractStore} from '../../../state/AbstractStore.js'
import {createStoreRepository} from '../../../state/StoreRepository.js'

class CodebookStore extends AbstractStore {
    active (codebookId, value) {
        const entry = this.entry(codebookId)

        if (typeof value === 'boolean') {
            entry.active = !entry.active
            return this // for chaining
        }

        return entry.active
    }

    init (docs) {
        if (this.size.value === 0 && docs.size !== 0) {
            docs.forEach((book) => {
                book.active = true
            })
            this.add(...docs)
        }
    }
}

export const Codebooks = createStoreRepository({
    key: 'store/codebooks',
    factory: (options) => new CodebookStore(options)
})

Codebooks.toggle = (projectId, codebookId) => {
    const store = Codebooks.by(projectId)
    const book = store.entry(codebookId)
    const newValue = !book?.active
    store.active(codebookId, newValue)
    return newValue
}

Codebooks.active = (projectId, codebookId) =>
    Codebooks.by(projectId).entry(codebookId).active

Codebooks.entries = (projectId) => Codebooks.by(projectId).all()
