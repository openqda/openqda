import { createStoreRepository } from '../../../state/StoreRepository.js'
import { AbstractStore } from '../../../state/AbstractStore.js'

class IntersectionsStore extends AbstractStore {

}

export const Intersections = createStoreRepository({
    key: 'store/intersections',
    factory: options => new IntersectionsStore(options)
})
