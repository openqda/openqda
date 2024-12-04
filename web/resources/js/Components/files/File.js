import { AbstractStore } from '../../state/AbstractStore.js';
import { createStoreRepository } from '../../state/StoreRepository.js';

class FileStore extends AbstractStore {}

export const Files = createStoreRepository({
  key: 'files',
  factory: (options) => new FileStore(options),
});
