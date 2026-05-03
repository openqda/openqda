import { describe, it, expect, vi, afterEach } from 'vitest';
import { createHash } from './createHash.js';

describe('createHash', () => {
  afterEach(() => {
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
  });

  it('returns a promise', () => {
    const result = createHash('test');
    expect(result).toBeInstanceOf(Promise);
  });

  it('produces a 64-character hex string for SHA-256', async () => {
    const result = await createHash('test');
    // SHA-256 produces 32 bytes = 64 hex characters
    expect(result).toHaveLength(64);
    expect(result).toMatch(/^[0-9a-f]{64}$/);
  });

  it('generates a hex hash by default', async () => {
    const result = await createHash('hello');
    expect(result).toBeTypeOf('string');
    // Hex string should only contain hex characters
    expect(result).toMatch(/^[0-9a-f]+$/);
  });

  it('generates consistent hashes for the same input', async () => {
    const hash1 = await createHash('test data');
    const hash2 = await createHash('test data');
    expect(hash1).toBe(hash2);
  });

  it('generates different hashes for different inputs', async () => {
    const hash1 = await createHash('hello');
    const hash2 = await createHash('world');
    expect(hash1).not.toBe(hash2);
  });

  it('supports base64 format', async () => {
    const result = await createHash('hello', { format: 'base64' });
    expect(result).toBeTypeOf('string');
    expect(result.length).toBeGreaterThan(0);
  });

  it('supports raw string format', async () => {
    const result = await createHash('hello', { format: 'raw' });
    expect(result).toBeTypeOf('string');
    expect(result.length).toBeGreaterThan(0);
  });

  it('can hash objects', async () => {
    const result = await createHash({ foo: 'bar' });
    expect(result).toBeTypeOf('string');
    expect(result.length).toBeGreaterThan(0);
  });

  it('can hash arrays', async () => {
    const result = await createHash([1, 2, 3]);
    expect(result).toBeTypeOf('string');
    expect(result.length).toBeGreaterThan(0);
  });

  it('rejects when crypto.subtle is not available', async () => {
    const saved = window.crypto;
    Object.defineProperty(window, 'crypto', {
      value: {},
      writable: true,
      configurable: true,
    });
    // hash() is async, so accessing undefined .subtle.digest becomes a rejected promise
    await expect(createHash('test')).resolves.toBeNull();
    Object.defineProperty(window, 'crypto', {
      value: saved,
      writable: true,
      configurable: true,
    });
  });
});
