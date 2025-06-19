import { describe, it, expect, vi } from 'vitest';
import { Theme } from './Theme.js';

describe('Theme', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
  });
  describe(Theme.init.name, () => {
    it('prefers to load from storage if given and returns a value', async () => {
      const strategy = await Theme.init({
        storage: {
          isDefined: () => true,
          value: () => 'foobar',
          update: expect.fail,
          remove: expect.fail,
        },
        useStorage: true,
        usePreferred: true,
      });
      expect(strategy).toEqual({
        from: 'storage',
        name: 'foobar',
      });
      expect(Theme.is('foobar')).toBe(true);
      expect(Theme.current()).toBe('foobar');
    });
    it('uses the window preferred entry, if storage is not defined or no value is given', async () => {
      vi.stubGlobal('matchMedia', (pattern) => {
        const value = { matches: false };
        if (pattern === '(prefers-color-scheme: dark)') {
          value.matches = true;
        }
        return value;
      });

      const storages = [
        null,
        undefined,
        {
          isDefined: () => false,
          update: expect.fail,
          remove: expect.fail,
        },
        {
          isDefined: () => true,
          value: () => null,
          update: expect.fail,
          remove: expect.fail,
        },
      ];

      for (const storage of storages) {
        const strategy = await Theme.init({
          storage,
          useStorage: true,
          usePreferred: true,
        });
        expect(strategy).toEqual({
          from: 'preferred',
          name: Theme.DARK,
        });
        expect(Theme.is(Theme.DARK)).toBe(true);
        expect(Theme.current()).toBe(Theme.DARK);
      }
    });
    it('falls back to light theme if no preference is given', async () => {
      vi.stubGlobal('matchMedia', () => ({ matches: false }));
      const storages = [
        null,
        undefined,
        {
          isDefined: () => false,
          update: expect.fail,
          remove: expect.fail,
        },
        {
          isDefined: () => true,
          value: () => null,
          update: expect.fail,
          remove: expect.fail,
        },
      ];

      for (const storage of storages) {
        const strategy = await Theme.init({
          storage,
          useStorage: true,
          usePreferred: true,
        });
        expect(strategy).toEqual({
          from: 'fallback',
          name: Theme.LIGHT,
        });
        expect(Theme.is(Theme.LIGHT)).toBe(true);
        expect(Theme.current()).toBe(Theme.LIGHT);
      }
    });
  });
  describe(Theme.update.name, () => {
    it('updates the DOM', async () => {
      await Theme.init({ storage: null, useStorage: true, usePreferred: true });
      const addSpy = vi.spyOn(document.documentElement.classList, 'add');
      await Theme.update('moo');
      expect(addSpy).toHaveBeenCalledTimes(1);
      expect(addSpy).toHaveBeenCalledWith('moo');
    });
    it('updates the storage', async () => {
      const storage = {
        isDefined: () => true,
        value: () => null,
        update: () => true,
        remove: expect.fail,
      };
      await Theme.init({ storage, useStorage: true, usePreferred: true });
      const addSpy = vi.spyOn(storage, 'update');
      await Theme.update('moo');
      expect(addSpy).toHaveBeenCalledTimes(1);
      expect(addSpy).toHaveBeenCalledWith('moo');
    });
  });
  describe(Theme.remove.name, () => {
    it('removes from the DOM', async () => {
      await Theme.init({ storage: null, useStorage: true, usePreferred: true });
      await Theme.update('moo');
      const removeSpy = vi.spyOn(document.documentElement.classList, 'remove');
      await Theme.remove();
      expect(removeSpy).toHaveBeenCalledTimes(1);
      expect(removeSpy).toHaveBeenCalledWith('moo');
    });
    it('removes from the storage', async () => {
      const storage = {
        isDefined: () => true,
        value: () => null,
        update: () => true,
        remove: () => true,
      };
      await Theme.init({ storage, useStorage: true, usePreferred: true });
      await Theme.update('moo');
      const removeSpy = vi.spyOn(storage, 'remove');
      await Theme.remove();
      expect(removeSpy).toHaveBeenCalledTimes(1);
      expect(removeSpy).toHaveBeenCalledWith();
    });
  });
});
