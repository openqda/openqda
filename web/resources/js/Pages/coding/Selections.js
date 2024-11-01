import { randomUUID } from '../../utils/randomUUID.js'
import { request } from '../../utils/http/BackendRequest.js'
import { createStoreRepository } from '../../state/StoreRepository.js'
import { AbstractStore } from '../../state/AbstractStore.js'

class SelectionsStore extends AbstractStore {
    print() {
        console.debug(this.entries)
    }
}

export const Selections = createStoreRepository({
    key: 'store/selections',
    factory: options => new SelectionsStore(options)
})

/**
 * Stores a selection in DB
 * @param projectId
 * @param sourceId
 * @param code
 * @param text
 * @param start
 * @param end
 * @return {Promise<BackendRequest>}
 */
Selections.store = async ({ projectId, sourceId, code, text, start, end }) => {
    const codeId = code.id
    const textId = randomUUID();
    const payload = {
        textId: textId,
        text: text,
        start_position: start,
        end_position: end,
    };

    const { response, error } = await request({
        url: `/projects/${projectId}/sources/${sourceId}/codes/${codeId}`,
        type: 'post',
        body: payload,
    });
    let selection
    if (!error && response.status < 400) {
        const s = response.data.selection
        const start = Number(s.start_position)
        const end = Number(s.end_position)
        const length = end - start
        selection = {
            code,
            id: s.id,
            text: s.text,
            start,
            end,
            length,
        }
        Selections.by(projectId).add(selection)

        if (!code.text) { code.text = [] }
        code.text.push(selection)
    }

    return { response, error, selection }
}

Selections.delete = async ({ projectId, sourceId, code, selection }) => {
    const codeId = code.id
    const selectionId = selection.id
    const { response, error } = await request({
        url: `/projects/${projectId}/sources/${sourceId}/codes/${codeId}/selections/${selectionId}`,
        type: 'delete',
    })
    if (!error && response.status < 400) {
        Selections.by(projectId).remove(selectionId)
        const index = code.text.findIndex((i) => i.id === selectionId)
        code.text.splice(index, 1)
    }
    // else flash message?

    return { response, error }
}
