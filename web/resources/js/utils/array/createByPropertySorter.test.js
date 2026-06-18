import { describe, expect } from 'vitest';
import { createByPropertySorter } from './createByPropertySorter.js';

const data = [{ id: 'foo' }, { id: 'bar' }, { id: 'baz' }, { id: 'moo' }];

describe(createByPropertySorter.name, () => {
  it('creates a sorter to sort objects by given prop', () => {
    const byId = createByPropertySorter('id');
    const sorted = data.toSorted(byId);
    expect(sorted).toEqual([
      { id: 'bar' },
      { id: 'baz' },
      { id: 'foo' },
      { id: 'moo' },
    ]);
  });
  it('keeps structure on undefined props', () => {
    const byUndef = createByPropertySorter('foo');
    const sorted = data.toSorted(byUndef);
    expect(sorted).toEqual(data);
  });
});
