import { describe, it, expect } from 'vitest';
import { safeStringify } from './safeStringify.js';

describe('safeStringify', () => {
  it('stringifies a simple object', () => {
    const result = safeStringify({ a: 1, b: 'hello' });
    expect(result).toBe('{"a":1,"b":"hello"}');
  });

  it('returns empty object string for no arguments', () => {
    const result = safeStringify();
    expect(result).toBe('{}');
  });

  it('returns empty object string for empty object', () => {
    const result = safeStringify({});
    expect(result).toBe('{}');
  });

  it('handles nested objects', () => {
    const result = safeStringify({ a: { b: { c: 1 } } });
    expect(JSON.parse(result)).toEqual({ a: { b: { c: 1 } } });
  });

  it('handles arrays', () => {
    const result = safeStringify({ items: [1, 2, 3] });
    expect(JSON.parse(result)).toEqual({ items: [1, 2, 3] });
  });

  it('replaces functions with $$function', () => {
    const result = safeStringify({ fn: () => {} });
    expect(JSON.parse(result)).toEqual({ fn: '$$function' });
  });

  it('handles circular references', () => {
    const obj = { a: 1 };
    obj.self = obj;
    const result = safeStringify(obj);
    expect(result).toBeTypeOf('string');
    const parsed = JSON.parse(result);
    expect(parsed.a).toBe(1);
    expect(parsed.self).toBe('$$ref-0');
  });

  it('handles deeply nested circular references', () => {
    const a = { name: 'a' };
    a.child = { name: 'b', parent: a };
    const result = safeStringify(a);
    expect(result).toBeTypeOf('string');
    // Should not throw
    const parsed = JSON.parse(result);
    expect(parsed.name).toBe('a');
    expect(parsed.child.name).toBe('b');
  });

  it('supports spacing parameter', () => {
    const result = safeStringify({ a: 1 }, 2);
    expect(result).toContain('\n');
    expect(result).toContain('  ');
  });

  it('handles null values', () => {
    const result = safeStringify({ a: null });
    expect(JSON.parse(result)).toEqual({ a: null });
  });

  it('handles undefined values', () => {
    const result = safeStringify({ a: 42, b: undefined });
    expect(JSON.parse(result)).toEqual({ a: 42 });
  });

  it('handles boolean values', () => {
    const result = safeStringify({ t: true, f: false });
    expect(JSON.parse(result)).toEqual({ t: true, f: false });
  });

  it('handles numeric values', () => {
    const result = safeStringify({ n: 42, pi: 3.14 });
    expect(JSON.parse(result)).toEqual({ n: 42, pi: 3.14 });
  });

  it('handles string values', () => {
    const result = safeStringify({ s: 'hello world' });
    expect(JSON.parse(result)).toEqual({ s: 'hello world' });
  });

  it('handles bigint values', () => {
    const result = safeStringify({ n: BigInt('42'), pi: 3.14 });
    expect(JSON.parse(result)).toEqual({ n: 42, pi: 3.14 });
  });

  it('handles multiple circular references correctly', () => {
    const shared = { value: 42 };
    const obj = { a: shared, b: shared };
    const result = safeStringify(obj);
    const parsed = JSON.parse(result);
    expect(parsed.a).toEqual({ value: 42 });
    // Second reference to same object gets a $$ref
    expect(parsed.b).toBe('$$ref-1');
  });
});
