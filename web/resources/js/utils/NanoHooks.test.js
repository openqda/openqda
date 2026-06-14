import { describe, it, expect } from 'vitest';
import { Observable } from './NanoHooks.js';

describe('Observable', () => {
  it('creates a new instance with an empty map', () => {
    const obs = new Observable();
    expect(obs.src).toBeInstanceOf(Map);
    expect(obs.src.size).toBe(0);
  });

  describe('on', () => {
    it('registers a listener for a given name', () => {
      const obs = new Observable();
      const fn = () => {};
      obs.on('test', fn);
      expect(obs.src.has('test')).toBe(true);
      expect(obs.src.get('test')).toContain(fn);
    });

    it('returns an unregister function', () => {
      const obs = new Observable();
      const fn = () => {};
      const off = obs.on('test', fn);
      expect(off).toBeTypeOf('function');
    });

    it('allows multiple listeners for the same name', () => {
      const obs = new Observable();
      const fn1 = () => {};
      const fn2 = () => {};
      obs.on('test', fn1);
      obs.on('test', fn2);
      expect(obs.src.get('test')).toHaveLength(2);
    });

    it('allows listeners for different names', () => {
      const obs = new Observable();
      obs.on('a', () => {});
      obs.on('b', () => {});
      expect(obs.src.has('a')).toBe(true);
      expect(obs.src.has('b')).toBe(true);
    });
  });

  describe('off', () => {
    it('removes a specific listener by reference', () => {
      const obs = new Observable();
      const fn = () => {};
      obs.on('test', fn);
      obs.off('test', fn);
      expect(obs.src.get('test')).not.toContain(fn);
    });

    it('does not remove other listeners', () => {
      const obs = new Observable();
      const fn1 = () => {};
      const fn2 = () => {};
      obs.on('test', fn1);
      obs.on('test', fn2);
      obs.off('test', fn1);
      expect(obs.src.get('test')).toContain(fn2);
      expect(obs.src.get('test')).not.toContain(fn1);
    });

    it('can unregister via the returned function from on()', () => {
      const obs = new Observable();
      const fn = () => {};
      const unregister = obs.on('test', fn);
      unregister();
      expect(obs.src.get('test')).not.toContain(fn);
    });
  });

  describe('run', () => {
    it('calls all registered listeners for a name', () => {
      const obs = new Observable();
      let called1 = false;
      let called2 = false;
      obs.on('test', () => {
        called1 = true;
      });
      obs.on('test', () => {
        called2 = true;
      });
      obs.run('test');
      expect(called1).toBe(true);
      expect(called2).toBe(true);
    });

    it('passes arguments to listeners', () => {
      const obs = new Observable();
      let receivedArgs = null;
      obs.on('test', (...args) => {
        receivedArgs = args;
      });
      obs.run('test', 'a', 'b', 'c');
      expect(receivedArgs).toEqual(['a', 'b', 'c']);
    });

    it('does not throw when no listeners registered for a name', () => {
      const obs = new Observable();
      expect(() => obs.run('nonexistent')).not.toThrow();
    });

    it('runs listeners in registration order', () => {
      const obs = new Observable();
      const order = [];
      obs.on('test', () => order.push(1));
      obs.on('test', () => order.push(2));
      obs.on('test', () => order.push(3));
      obs.run('test');
      expect(order).toEqual([1, 2, 3]);
    });

    it('does not run listeners of other names', () => {
      const obs = new Observable();
      let called = false;
      obs.on('other', () => {
        called = true;
      });
      obs.run('test');
      expect(called).toBe(false);
    });

    it('does not run unregistered listeners', () => {
      const obs = new Observable();
      let called = false;
      const fn = () => {
        called = true;
      };
      const off = obs.on('test', fn);
      off();
      obs.run('test');
      expect(called).toBe(false);
    });
  });
});
