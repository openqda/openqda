import { expect } from 'vitest';
import { createStoreRepository } from './StoreRepository.js';
import { AbstractStore } from './AbstractStore.js';

describe(createStoreRepository.name, () => {
  it('creates a new store by given namespace', () => {
    const key = 'foo';
    const repo = createStoreRepository({
      key,
      factory: (options) => new AbstractStore(options),
    });

    const byProject1 = repo.by(1);
    const byProject2 = repo.by(2);
    expect(byProject1).toBeInstanceOf(AbstractStore);
    expect(byProject2).toBeInstanceOf(AbstractStore);
    expect(byProject1).not.toBe(byProject2);
  });
});
