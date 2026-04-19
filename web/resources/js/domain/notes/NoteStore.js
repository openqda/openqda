import { AbstractStore } from '../../state/AbstractStore.js';
import { createStoreRepository } from '../../state/StoreRepository.js';

class NoteStore extends AbstractStore {
  constructor(options) {
    super(options);
    this.codes = {};
    this.selections = {};
    this.project = {};
  }

  init(docs, users) {
    const usersById = {};
    users.forEach((user) => {
      usersById[user.id] = user;
    });
    if (this.size.value === 0 && docs.size !== 0) {
      docs.forEach((note) => {
        note.user = usersById[note.creating_user_id];
      });
      this.add(...docs);
    }

    return { added: docs, clean: [] };
  }

  byCodes(codeIds = []) {
    if (codeIds.length === 0) {
      return Object.values(this.codes);
    }
  }
}

export const Notes = createStoreRepository({
  key: 'store/notes',
  factory: (options) => new NoteStore(options),
});
