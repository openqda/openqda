import { describe, it, expect, vi, afterEach } from 'vitest';
import { randomString } from './randomString.js';

describe('randomString', () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('returns a string', () => {
    const result = randomString(16, 'random');
    expect(result).toBeTypeOf('string');
  });

  it('returns a string of the specified length using Math.random fallback', () => {
    const result = randomString(10, 'random');
    expect(result).toHaveLength(10);
  });

  it('defaults to length 16', () => {
    // Use random provider so length is exact character count
    const result = randomString(undefined, 'random');
    expect(result).toHaveLength(16);
  });

  it('returns an empty string for length 0 using random provider', () => {
    const result = randomString(0, 'random');
    expect(result).toBe('');
  });

  it('generates only alphanumeric characters using random provider', () => {
    const result = randomString(100, 'random');
    expect(result).toMatch(/^[A-Za-z0-9]+$/);
  });

  it('generates different strings on subsequent calls', () => {
    const results = new Set();
    for (let i = 0; i < 10; i++) {
      results.add(randomString(32, 'random'));
    }
    // With 32-char random strings, collisions should be virtually impossible
    expect(results.size).toBeGreaterThan(1);
  });

  it('uses crypto provider by default when crypto is available', () => {
    const spy = vi.spyOn(crypto, 'getRandomValues');
    randomString(8);
    expect(spy).toHaveBeenCalled();
  });

  it('returns an empty string for length 0 using crypto provider', () => {
    const result = randomString(0, 'crypto');
    expect(result).toBe('');
  });

  it('returns a string using crypto provider', () => {
    const result = randomString(8, 'crypto');
    expect(result).toBeTypeOf('string');
  });

  it('uses random provider explicitly', () => {
    const result = randomString(10, 'random');
    // Should use Math.random fallback
    expect(result).toBeTypeOf('string');
    expect(result).toHaveLength(10);
    expect(result).toMatch(/^[A-Za-z0-9]+$/);
  });

  it('handles length 1 with random provider', () => {
    const result = randomString(1, 'random');
    expect(result).toHaveLength(1);
    expect(result).toMatch(/^[A-Za-z0-9]$/);
  });

  it('handles large lengths with random provider', () => {
    const result = randomString(1000, 'random');
    expect(result).toHaveLength(1000);
  });
});
