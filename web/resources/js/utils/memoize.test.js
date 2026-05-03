import { describe, it, expect, vi } from 'vitest';
import { memoize } from './memoize.js';

describe('memoize', () => {
  it('returns a function', () => {
    const fn = memoize(() => {});
    expect(fn).toBeTypeOf('function');
  });

  it('calls the original function on first invocation', () => {
    const spy = vi.fn((x) => x * 2);
    const memoized = memoize(spy);
    const result = memoized(5);
    expect(result).toBe(10);
    expect(spy).toHaveBeenCalledTimes(1);
  });

  it('returns correct result for repeated same arguments', () => {
    const spy = vi.fn((x) => x * 2);
    const memoized = memoize(spy);
    const result1 = memoized(5);
    memoized(6);
    const result2 = memoized(5);
    expect(result1).toBe(10);
    expect(result2).toBe(10);
    expect(spy).toHaveBeenCalledTimes(2); // Should only call original function once for same args
  });

  it('calls the function for different arguments', () => {
    const spy = vi.fn((x) => x * 2);
    const memoized = memoize(spy);
    expect(memoized(5)).toBe(10);
    expect(memoized(3)).toBe(6);
  });

  it('handles multiple arguments', () => {
    const spy = vi.fn((a, b) => a + b);
    const memoized = memoize(spy);
    expect(memoized(1, 2)).toBe(3);
    expect(memoized(3, 4)).toBe(7);
  });

  it('handles no arguments', () => {
    const spy = vi.fn(() => 42);
    const memoized = memoize(spy);
    expect(memoized()).toBe(42);
  });

  it('handles function arguments by stringifying them', () => {
    const spy = vi.fn((fn) => fn());
    const memoized = memoize(spy);
    const callback = () => 'hello';
    const result = memoized(callback);
    expect(result).toBe('hello');
  });

  it('handles object arguments', () => {
    const spy = vi.fn((obj) => obj.a + obj.b);
    const memoized = memoize(spy);
    expect(memoized({ a: 1, b: 2 })).toBe(3);
  });

  it('preserves this context', () => {
    const obj = {
      value: 10,
      compute: memoize(function (x) {
        return this.value + x;
      }),
    };
    expect(obj.compute(5)).toBe(15);
  });

  it('handles string arguments', () => {
    const spy = vi.fn((s) => s.toUpperCase());
    const memoized = memoize(spy);
    expect(memoized('hello')).toBe('HELLO');
    expect(memoized('hello')).toBe('HELLO');
  });
});
