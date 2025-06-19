import { describe, expect } from 'vitest';
import { hexToRgba, hexToRgb } from './hexToRgba.js';
import { toHex } from './toHex.js';

const max = 255;
const hexPattern6 = /^#[0-9a-f]{6}$/;
const hexPattern8 = /^#[0-9a-f]{8}$/;
const rgbaPattern = /^rgba\(\d{1,3}, \d{1,3}, \d{1,3}, (0|1|0\.[0-9]+)\)$/;
const rgbPattern = /^rgb\(\d{1,3}, \d{1,3}, \d{1,3}\)$/;
const random = (max) => Math.floor(Math.random() * max);
describe('hexToRgba', () => {
  describe(hexToRgb.name, () => {
    it('transforms a given rgb hex to rgb() color', () => {
      for (let i = 0; i < 100; i++) {
        const hex = `#${toHex(random(max))}${toHex(random(max))}${toHex(random(max))}`;
        expect(hexPattern6.test(hex), hex).toBe(true);
        const rgb = hexToRgb(hex);
        expect(rgbPattern.test(rgb), rgb).toBe(true);
      }
    });
  });
  describe(hexToRgba.name, () => {
    it('transforms a given rgb hex to rgba() color', () => {
      for (let i = 0; i < 100; i++) {
        const vals = [
          toHex(random(max)),
          toHex(random(max)),
          toHex(random(max)),
          toHex(random(max)),
        ];
        const hex = `#${vals.join('')}`;
        expect(hexPattern8.test(hex), hex).toBe(true);
        const rgba = hexToRgba(hex);
        expect(rgbaPattern.test(rgba), rgba).toBe(true);
      }
    });
  });
});
