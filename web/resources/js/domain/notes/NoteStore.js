import { AbstractStore } from '../../state/AbstractStore.js';
import { createStoreRepository } from '../../state/StoreRepository.js';

class NoteStore extends AbstractStore {
  /**
   * add notes to the store if the store is empty
   * @param docs {object[]}
   * @param users {object}
   * @return {{added, clean: *[]}}
   */
  init(docs, users = {}) {
    if (this.size.value === 0 && docs.length !== 0) {
      const mapped = docs.map((doc) => {
        doc.user = users[doc.creating_user_id];
        return doc;
      });
      this.add(...mapped);
    }

    return { added: docs, clean: [] };
  }
}

export const Notes = createStoreRepository({
  key: 'store/notes',
  factory: (options) => new NoteStore(options),
});
