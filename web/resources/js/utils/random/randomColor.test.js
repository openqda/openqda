import { describe, expect } from 'vitest';
import { randomColor } from './randomColor';

const rgbPattern = /^rgba\(\d{1,3}, \d{1,3}, \d{1,3}, (1|0\.[0-9]+)\)$/;
const hexPattern = /^#[0-9a-f]{8}$/;

describe(randomColor.name, () => {
  it('returns a random rgba color', () => {
    for (let i = 0; i < 100; i++) {
      const col = randomColor({ type: 'rgba', opacity: Math.random() });
      expect(rgbPattern.test(col), col).toBe(true);
    }
  });
  it('returns a random hex color', () => {
    for (let i = 0; i < 100; i++) {
      const col = randomColor({ type: 'hex', opacity: Math.random() });
      expect(hexPattern.test(col), col).toBe(true);
    }
  });
});
