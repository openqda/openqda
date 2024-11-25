import { describe, expect } from 'vitest';
import { toHex, rgbToHex } from './toHex.js';
import { randomColor } from '../random/randomColor.js';

const color = (max) => Math.floor(Math.random() * max);
const hexPattern = /^[0-9a-f]{1,2}$/;
const fullHexPattern6 = /^#[0-9a-f]{6}$/;
const fullHexPattern8 = /^#[0-9a-f]{8}$/;
const max = 255;

describe('colorToHex', () => {
  describe(rgbToHex.name, () => {
    it('converts given rgb to hex', () => {
      for (let i = 0; i < 100; i++) {
        const color = randomColor({ type: 'rgb' });
        const hex = rgbToHex(color);
        expect(fullHexPattern6.test(hex), hex).toBe(true);
      }
    });
    it('converts given rgba to hex', () => {
      for (let i = 0; i < 100; i++) {
        const color = randomColor({ type: 'rgba' });
        const hex = rgbToHex(color);
        expect(fullHexPattern8.test(hex), hex).toBe(true);
      }
    });
  });
  describe(toHex.name, () => {
    it('throws on incompatible values', () => {
      [-1, '-1', false, true, {}, 256, '256'].forEach((value) => {
        expect(() => toHex(value)).toThrow(
          `Invalid value to convert, ${value}`
        );
      });
    });
    it('it converts a value to hex value', () => {
      for (let i = 0; i < 100; i++) {
        const col = color(max);
        const hex = toHex(Math.random() > 0.5 ? col.toString() : col);
        expect(hexPattern.test(hex), hex).toBe(true);
      }
    });
  });
});
