import { afterEach, describe, expect, vi } from 'vitest';
import { randomUUID } from './randomUUID.js';

const pattern =
  /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i;
const stubbed = '00000000-0000-4000-0000-000000000000';

describe(randomUUID.name, () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('creates a random uuid with native support', () => {
    vi.stubGlobal('crypto', {
      randomUUID: () => stubbed,
    });

    const actual = randomUUID();
    expect(actual).toBe(stubbed);
    expect(pattern.test(actual)).toBe(true);
  });
  it('creates a random uuid with polyfill', () => {
    const actual = randomUUID();
    expect(actual).not.toBe(stubbed);
    expect(pattern.test(actual)).toBe(true);
  });
});
