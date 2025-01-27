import { isOverlapping } from './overlapping.js';
import { describe, it, expect } from 'vitest';

describe(isOverlapping.name, () => {
  it('returns true if one code includes the other', () => {
    [
      [0, 10, 0, 9],
      [0, 10, 1, 10],
      [0, 10, 1, 9],
      [0, 10, 5, 5],
      [0, 10, 0, 10],
    ].forEach(([a, b, c, d]) => {
      expect(isOverlapping(a, b, c, d)).toBe(true);
      expect(isOverlapping(c, d, a, b)).toBe(true);
    });
  });
  it('returns true if one code overlaps to the left', () => {
    [
      [5, 10, 4, 9],
      [5, 10, 4, 6],
      [5, 10, 4, 10],
    ].forEach(([a, b, c, d]) => {
      expect(isOverlapping(a, b, c, d)).toBe(true);
      expect(isOverlapping(c, d, a, b)).toBe(true);
    });
  });
  it('returns true if one code overlaps to the right', () => {
    [
      [5, 10, 5, 11],
      [5, 10, 6, 11],
      [5, 10, 9, 11],
    ].forEach(([a, b, c, d]) => {
      expect(isOverlapping(a, b, c, d)).toBe(true);
      expect(isOverlapping(c, d, a, b)).toBe(true);
    });
  });
  it('returns false if one code is a direct neighbor', () => {
    [
      [5, 10, 0, 5],
      [5, 10, 10, 11],
    ].forEach(([a, b, c, d]) => {
      expect(isOverlapping(a, b, c, d)).toBe(false);
      expect(isOverlapping(c, d, a, b)).toBe(false);
    });
  });
  it('returns false if there are no overlaps', () => {
    [
      [5, 10, 0, 4],
      [5, 10, 11, 12],
    ].forEach(([a, b, c, d]) => {
      expect(isOverlapping(a, b, c, d)).toBe(false);
      expect(isOverlapping(c, d, a, b)).toBe(false);
    });
  });
});
