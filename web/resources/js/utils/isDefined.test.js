import { describe, it, expect } from 'vitest';
import { isDefined } from './isDefined.js';

describe('isDefined', () => {
  it('returns false for undefined', () => {
    expect(isDefined(undefined)).toBe(false);
  });

  it('returns false for null', () => {
    expect(isDefined(null)).toBe(false);
  });

  it('returns true for 0', () => {
    expect(isDefined(0)).toBe(true);
  });

  it('returns true for empty string', () => {
    expect(isDefined('')).toBe(true);
  });

  it('returns true for false', () => {
    expect(isDefined(false)).toBe(true);
  });

  it('returns true for NaN', () => {
    expect(isDefined(NaN)).toBe(true);
  });

  it('returns true for an object', () => {
    expect(isDefined({})).toBe(true);
  });

  it('returns true for an array', () => {
    expect(isDefined([])).toBe(true);
  });

  it('returns true for a function', () => {
    expect(isDefined(() => {})).toBe(true);
  });

  it('returns true for a number', () => {
    expect(isDefined(42)).toBe(true);
  });

  it('returns true for a string', () => {
    expect(isDefined('hello')).toBe(true);
  });

  it('returns true for true', () => {
    expect(isDefined(true)).toBe(true);
  });

  it('returns false for void 0', () => {
    expect(isDefined(void 0)).toBe(false);
  });
});
