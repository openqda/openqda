import { randomUUID } from '../../../utils/random/randomUUID.js';
import { request } from '../../../utils/http/BackendRequest.js';
import { createStoreRepository } from '../../../state/StoreRepository.js';
import { AbstractStore } from '../../../state/AbstractStore.js';
import { Intersections } from './Intersections.js';

const { getIntersection, isOverlapping } = Intersections;
class SelectionsStore extends AbstractStore {
  getIntersections(selections) {
    const intersections = [];
    this.all().forEach((s2) => {
      selections.forEach((s1) => {
        if (s1 !== s2 && isOverlapping(s1.start, s1.end, s2.start, s2.end)) {
          intersections.push(
            getIntersection(s1.start, s1.end, s2.start, s2.end)
          );
        }
      });
    });
    return intersections;
  }

  getIntersecting(selections) {
    const intersecting = [];

    this.all().forEach((s2) => {
      selections.forEach((s1) => {
        if (s1 !== s2 && isOverlapping(s1.start, s1.end, s2.start, s2.end)) {
          intersecting.push(s2);
        }
      });
    });

    return intersecting;
  }

  update(docIdOrFn, value = undefined, { updateId = false } = {}) {
    let updated; // array
    const allDocs = this.all();

    if (typeof docIdOrFn === 'function') {
      // nested changes are applied directly by
      // consumer and this reflected in a function
      // that returns all ids of updated docs
      updated = docIdOrFn(allDocs);
    } else {
      const entry = this.entries[docIdOrFn];
      const { id, ...values } = value;
      if (updateId) {
        values.id = id;
      }
      Object.assign(entry, values);
      updated = [entry];
    }

    if (updated) {
      const relatedDocs = this.getIntersecting(updated);
      if (relatedDocs.length) updated.push(...relatedDocs);
      this.observable.run('updated', updated, allDocs);
      this.observable.run('changed', { type: 'updated', docs: updated });
    }
  }

  init(selections, getCode) {
    if (this.size.value === 0 && selections.length !== 0) {
      selections.forEach((selection) => {
        const code = getCode(selection.code_id);
        selection.start = Number(selection.start_position);
        selection.end = Number(selection.end_position);
        selection.length = selection.end - selection.start;
        selection.code = code;
      });
      this.add(...selections);
    }
  }
}

export const Selections = createStoreRepository({
  key: 'store/selections',
  factory: (options) => new SelectionsStore(options),
});

Selections.sort = {};

Selections.sort.byRange = (a, b) => {
  const length = b.length - a.length;
  const start = a.start - b.start;
  return length !== 0 ? length : start;
};

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
  const key = `${projectId}-${source.id}`;
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
