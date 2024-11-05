
export const createStoreRepository = ({ key, factory }) => {
    const instances = new Map()
    const strKey = String(key)
    const repository = { key: strKey }

    /**
     *
     * @param projectId
     * @return {AbstractStore}
     */
    repository.by = (projectId) => {
        if ([undefined, null, ''].includes(projectId)) {
            throw new Error(`Expected projectId, got ${projectId}`)
        }
        const strId = String(projectId)
        if (!instances.has(strId)) {
            const store = factory({ projectId: strId, key })
            instances.set(strId, store)
        }
        return instances.get(strId)
    }

    return repository
}
