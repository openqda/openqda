
export const createStoreRepository = ({ key, factory }) => {
    const instances = new Map()
    const repository = { key }

    repository.by = (projectId) => {
        if ([undefined, null, ''].includes(projectId)) {
            throw new Error(`Expected projectId, got ${projectId}`)
        }
        if (!instances.has(projectId)) {
            const store = factory({ projectId, key })
            instances.set(projectId, store)
        }
        return instances.get(projectId)
    }

    return repository
}
