import { AbstractStore } from '../../state/AbstractStore.js';
import { createStoreRepository } from '../../state/StoreRepository.js';

class NoteStore extends AbstractStore {
  init(docs) {
    if (this.size.value === 0 && docs.size !== 0) {
      this.add(...docs);
    }

    return { added: docs, clean: [] };
  }
}

export const Notes = createStoreRepository({
  key: 'store/notes',
  factory: (options) => new NoteStore(options),
});
