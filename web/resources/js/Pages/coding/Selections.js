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
 * @param codeId
 * @param text
 * @param start
 * @param end
 * @return {Promise<BackendRequest>}
 */
Selections.store = ({ projectId, sourceId, codeId, text, start, end }) => {
    const textId = randomUUID();
    const payload = {
        textId: textId,
        text: text,
        start_position: start,
        end_position: end,
    };

    return request({
        url: `/projects/${projectId}/sources/${sourceId}/codes/${codeId}`,
        type: 'post',
        body: payload,
    });
}
