import { beforeEach, describe, expect } from 'vitest';
import { AbstractStore } from './AbstractStore';
import { isReactive, isRef } from 'vue';

const int = () => Math.floor(Math.random() * 100);

describe(AbstractStore.constructor.name, () => {
  let projectId;
  let id;
  let store;
  const key = 'foo/bar';

  beforeEach(() => {
    projectId = int();
    id = int();
    store = new AbstractStore({ projectId, key });
  });

  describe('instances', () => {
    it('holds reactive data', () => {
      expect(isReactive(store.entries)).toBe(true);
      expect(isRef(store.size)).toBe(true);
    });
  });

  describe('entry', () => {
    it('throws if no entry is found', () => {
      [undefined, null, '', 'moo'].forEach((value) => {
        expect(() => store.entry(value)).toThrowError(
          `Entry not found by id ${value} in ${projectId}/${key}`
        );
      });
    });
    it('returns the entry by id', () => {
      const doc = { id, name: 'bar' };
      store.add(doc);
      expect(store.entry(id)).toEqual(doc);
    });
  });
  describe('add', () => {
    it('adds new docs to the store but ignores existing docs', () => {
      const doc = { id, name: 'bar' };
      store.add(doc);
      expect(store.size.value).toBe(1);

      const docs = [
        { id: 101, name: 'foo' },
        doc,
        { id: 102, name: 'moo' },
        { id: 103, name: 'baz' },
      ];

      const added = store.add(...docs);
      expect(store.size.value).toBe(4);
      expect(added).toEqual([
        { id: 101, name: 'foo' },
        { id: 102, name: 'moo' },
        { id: 103, name: 'baz' },
      ]);
    });
  });
  describe('update', () => {
    it('updates store by value', () => {
      const doc = { id, name: 'bar' };
      store.add(doc);
      expect(store.entry(id).name).toBe('bar');
      store.update(doc.id, { name: 'foo' });
      expect(store.entry(id).name).toBe('foo');
    });
    it('updates store by function', () => {
      const doc = { id, name: 'bar' };
      store.add(doc);
      expect(store.entry(id).name).toBe('bar');
      store.update((docs) => {
        const toUpdate = docs.find((d) => d.id === id);
        toUpdate.name = 'foo';
        return [toUpdate];
      });
      expect(store.entry(id).name).toBe('foo');
    });
    it('can change a doc id when flag is given', () => {
      const doc = { id, name: 'bar' };
      store.add(doc);
      store.update(doc.id, { id: 101 }, { updateId: true });
      expect(() => store.entry(id)).toThrowError(
        `Entry not found by id ${id} in ${projectId}/${key}`
      );
      expect(store.entry(101)).toEqual({
        id: 101,
        name: 'bar',
      });
    });
  });
  describe('remove', () => {
    it('throws if an id is not in entries', () => {
      expect(() => store.remove(1)).toThrowError(
        `Entry not found by id ${1} in ${projectId}/${key}`
      );
    });
    it('removes all given docs by ids', () => {
      const doc = { id, name: 'bar' };
      store.add(doc);
      expect(store.size.value).toBe(1);
      store.remove(id);
      expect(store.size.value).toBe(0);
      expect(() => store.remove(id)).toThrowError(
        `Entry not found by id ${id} in ${projectId}/${key}`
      );
    });
  });
  describe('all', () => {
    it('returns all documents', () => {
      store.add({ id: 0, name: 'foo ' });
      store.add({ id: 1, name: 'bar ' });
      const docs = store.all();
      expect(docs.length).toBe(2);
      expect(isReactive(docs[0])).toBe(true);
    });
  });

  describe('observe', () => {
    let disposeObservers;
    afterEach(() => {
      if (disposeObservers) disposeObservers();
    });

    it('observes added docs', () =>
      new Promise((done) => {
        const doc = { id, name: 'bar' };
        disposeObservers = store.observe({
          added: (docs) => {
            expect(docs).toEqual([doc]);
            done();
          },
        });
        store.add(doc);
      }));

    it('observes updated docs (update by value)', () =>
      new Promise((done) => {
        const doc = { id, name: 'bar' };
        disposeObservers = store.observe({
          updated: ([update]) => {
            expect(update.id).toEqual(doc.id);
            expect(update.name).toEqual('foo');
            const entry = store.entry(doc.id);
            expect(entry.name).toEqual('foo');
            done();
          },
        });
        store.add(doc);
        store.update(doc.id, { name: 'foo' });
      }));
    it('observes updated docs (update by function)', () =>
      new Promise((done) => {
        const doc = { id, name: 'bar' };
        disposeObservers = store.observe({
          updated: ([update]) => {
            expect(update.id).toEqual(doc.id);
            expect(update.name).toEqual('foo');
            const entry = store.entry(doc.id);
            expect(entry.name).toEqual('foo');
            done();
          },
        });
        store.add(doc);
        store.update((docs) => {
          const update = docs[0];
          update.name = 'foo';
          return [update];
        });
      }));
    it('observes updated docs (update id)', () =>
      new Promise((done) => {
        const doc = { id, name: 'bar' };
        disposeObservers = store.observe({
          updated: ([update]) => {
            expect(update.id).toEqual(101);
            expect(update.name).toEqual(doc.name);
            const entry = store.entry(101);
            expect(entry.name).toEqual(doc.name);
            expect(() => store.entry(id)).toThrowError(
              `Entry not found by id ${id} in ${projectId}/${key}`
            );
            done();
          },
        });
        store.add(doc);
        store.update(doc.id, { id: 101 }, { updateId: true });
      }));
    it('observes removed docs', () =>
      new Promise((done) => {
        const doc = { id, name: 'bar' };
        disposeObservers = store.observe({
          removed: (docs) => {
            expect(docs).toEqual([doc]);
            expect(() => store.entry(id)).toThrowError(
              `Entry not found by id ${id} in ${projectId}/${key}`
            );
            done();
          },
        });
        store.add(doc);
        store.remove(doc.id);
      }));
  });
});
