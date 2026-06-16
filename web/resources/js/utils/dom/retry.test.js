import { vi } from 'vitest';
import { retry } from './retry.js';
import { asyncTimeout } from '../asyncTimeout.js';

describe(retry.name, () => {
  it('should call the function until it returns a truthy value', async () => {
    let count = 0;
    const fn = vi.fn(() => {
      count++;
      return count === 3;
    });
    const clear = retry(fn, -1, 10);
    await asyncTimeout(100);
    expect(fn).toHaveBeenCalledTimes(3);
    clear();
  });
  it('should stop after given number of cycles', async () => {
    let count = 0;
    const fn = vi.fn(() => {
      count++;
      return false;
    });
    const clear = retry(fn, 5, 10);
    await asyncTimeout(100);
    expect(fn).toHaveBeenCalledTimes(6);
    expect(count).toBe(6);
    clear();
  });
});
