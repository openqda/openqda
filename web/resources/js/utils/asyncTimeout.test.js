import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { asyncTimeout } from './asyncTimeout.js';

describe('asyncTimeout', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('returns a promise', () => {
    const result = asyncTimeout(100);
    expect(result).toBeInstanceOf(Promise);
  });

  it('resolves after the specified delay', async () => {
    let resolved = false;
    asyncTimeout(500).then(() => {
      resolved = true;
    });

    expect(resolved).toBe(false);

    vi.advanceTimersByTime(499);
    await vi.runAllTimersAsync();
    // After 500ms it should be resolved
    expect(resolved).toBe(true);
  });

  it('resolves with undefined', async () => {
    const promise = asyncTimeout(10);
    vi.advanceTimersByTime(10);
    const result = await promise;
    expect(result).toBeUndefined();
  });

  it('works with 0ms delay', async () => {
    const promise = asyncTimeout(0);
    vi.advanceTimersByTime(0);
    await expect(promise).resolves.toBeUndefined();
  });

  it('can be used in async/await flow', async () => {
    const order = [];
    order.push('before');

    const promise = (async () => {
      await asyncTimeout(100);
      order.push('after');
    })();

    expect(order).toEqual(['before']);
    vi.advanceTimersByTime(100);
    await promise;
    expect(order).toEqual(['before', 'after']);
  });
});
