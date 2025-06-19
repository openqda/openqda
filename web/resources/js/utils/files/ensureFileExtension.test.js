import { expect, describe } from 'vitest';
import { ensureFileExtension } from './ensureFileExtension';

describe(ensureFileExtension.name, () => {
  it('returns the extension if none is present', () => {
    expect(ensureFileExtension('foo', 'bar')).toBe('foo.bar');
    expect(ensureFileExtension('foo.baz', 'bar')).toBe('foo.baz.bar');
  });
  it('returns the extension if present', () => {
    expect(ensureFileExtension('foo.bar', 'bar')).toBe('foo.bar');
  });
});
