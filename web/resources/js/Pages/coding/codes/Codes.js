import { AbstractStore } from '../../../state/AbstractStore.js'
import { createStoreRepository } from '../../../state/StoreRepository.js'

class CodeStore extends AbstractStore{
    toggle(codeId) {
        const entry = this.entry(codeId)
        return this.active(codeId, !entry.active)
    }

    active (codeId, value) {
        const entry = this.entry(codeId)
        entry.active = value
        if (entry.children?.length) {
            entry.children.forEach(child => this.active(child.id, value))
        }
        return this
    }

    async save () {
        const key = `${this.projectId}/${this.key}`
    }
}

export const Codes = createStoreRepository({
    key: 'store/codes',
    factory: (options) =>  new CodeStore(options)
})

Codes.entries = projectId => Codes.by(projectId).all()

Codes.sort = (a, b) => Number(a.active) - Number(b.active)
