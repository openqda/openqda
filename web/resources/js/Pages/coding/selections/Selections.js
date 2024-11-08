import { randomUUID } from '../../../utils/randomUUID.js';
import { request } from '../../../utils/http/BackendRequest.js';
import { createStoreRepository } from '../../../state/StoreRepository.js';
import { AbstractStore } from '../../../state/AbstractStore.js';

class SelectionsStore extends AbstractStore {
    getIntersections (selections) {
        const intersections = []
    }

    init(selections, getCode) {
        if (this.size.value === 0 && selections.length !== 0) {
            selections.forEach(selection => {
                const code = getCode(selection.code_id)
                selection.start = Number(selection.start_position)
                selection.end = Number(selection.end_position)
                selection.length = selection.end - selection.start
                selection.code = code
            })
            this.add(...selections)
        }
    }
}

export const Selections = createStoreRepository({
  key: 'store/selections',
  factory: (options) => new SelectionsStore(options),
});

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
  const codeId = code.id;
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

  return { response, error };
};

Selections.reassign = async ({ projectId, source, code, selection }) => {
  const oldCode = selection.code;
  const newCode = code;
  const key = `${projectId}-${source.id}`
  const selectionId = selection.id;
  const { response, error } = await request({
    url: `/projects/${projectId}/sources/${source.id}/codes/${newCode.id}/selections/${selectionId}/change-code`,
    type: 'post',
    body: {
      oldCodeId: oldCode.id,
      newCodeId: newCode.id,
    },
  });
  if (!error && response.status < 400) {
    Selections.by(key).update(selectionId, { code: newCode });

    // remove from old code
    const index = oldCode.text.findIndex((i) => i.id === selectionId);
    oldCode.text.splice(index, 1);

    // add to new code
    if (!newCode.text?.length) {
      newCode.text = [];
    }
    newCode.text.push(selection);
  }
  return { response, error };
};

Selections.delete = async ({ projectId, sourceId, code, selection }) => {
  const codeId = code.id;
  const selectionId = selection.id;
  const { response, error } = await request({
    url: `/projects/${projectId}/sources/${sourceId}/codes/${codeId}/selections/${selectionId}`,
    type: 'delete',
  });
  if (!error && response.status < 400) {
    Selections.by(`${projectId}-${sourceId}`).remove(selectionId);
    const index = code.text.findIndex((i) => i.id === selectionId);
    code.text.splice(index, 1);
  }
  // else flash message?

  return { response, error };
};
