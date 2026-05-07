import { describe, expect, it } from 'vitest';
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
    it('returns the input unchanged if it already starts with #', () => {
      expect(rgbToHex('#aabbcc')).toBe('#aabbcc');
      expect(rgbToHex('#00ff00ff')).toBe('#00ff00ff');
      expect(rgbToHex('#123')).toBe('#123');
    });
    it('converts known rgb values to the correct hex', () => {
      expect(rgbToHex('rgb(0, 0, 0)')).toBe('#000000');
      expect(rgbToHex('rgb(255, 255, 255)')).toBe('#ffffff');
      expect(rgbToHex('rgb(255, 0, 0)')).toBe('#ff0000');
      expect(rgbToHex('rgb(0, 255, 0)')).toBe('#00ff00');
      expect(rgbToHex('rgb(0, 0, 255)')).toBe('#0000ff');
      expect(rgbToHex('rgb(16, 32, 48)')).toBe('#102030');
    });
    it('converts known rgba values to the correct hex', () => {
      expect(rgbToHex('rgba(0, 0, 0, 0)')).toBe('#00000000');
      expect(rgbToHex('rgba(255, 255, 255, 255)')).toBe('#ffffffff');
      expect(rgbToHex('rgba(255, 0, 0, 128)')).toBe('#ff000080');
    });
    it('throws on invalid input', () => {
      expect(() => rgbToHex('')).toThrow();
      expect(() => rgbToHex('not-a-color')).toThrow();
      expect(() => rgbToHex('hsl(0, 100%, 50%)')).toThrow();
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
    it('throws on null and undefined', () => {
      expect(() => toHex(null)).toThrow();
      expect(() => toHex(undefined)).toThrow();
    });
    it('throws on NaN', () => {
      expect(() => toHex(NaN)).toThrow();
      expect(() => toHex('abc')).toThrow();
    });
    it('it converts a value to hex value', () => {
      for (let i = 0; i < 100; i++) {
        const col = color(max);
        const hex = toHex(Math.random() > 0.5 ? col.toString() : col);
        expect(hexPattern.test(hex), hex).toBe(true);
      }
    });
    it('converts boundary value 0 to "00"', () => {
      expect(toHex(0)).toBe('00');
      expect(toHex('0')).toBe('00');
    });
    it('converts boundary value 255 to "ff"', () => {
      expect(toHex(255)).toBe('ff');
      expect(toHex('255')).toBe('ff');
    });
    it('zero-pads single-digit hex values (0-15)', () => {
      expect(toHex(0)).toBe('00');
      expect(toHex(1)).toBe('01');
      expect(toHex(9)).toBe('09');
      expect(toHex(10)).toBe('0a');
      expect(toHex(15)).toBe('0f');
    });
    it('does not pad two-digit hex values (16-255)', () => {
      expect(toHex(16)).toBe('10');
      expect(toHex(128)).toBe('80');
      expect(toHex(200)).toBe('c8');
      expect(toHex(255)).toBe('ff');
    });
    it('converts known values correctly', () => {
      expect(toHex(0)).toBe('00');
      expect(toHex(1)).toBe('01');
      expect(toHex(16)).toBe('10');
      expect(toHex(32)).toBe('20');
      expect(toHex(48)).toBe('30');
      expect(toHex(64)).toBe('40');
      expect(toHex(127)).toBe('7f');
      expect(toHex(128)).toBe('80');
      expect(toHex(192)).toBe('c0');
      expect(toHex(255)).toBe('ff');
    });
    it('accepts string representations of valid numbers', () => {
      expect(toHex('0')).toBe('00');
      expect(toHex('15')).toBe('0f');
      expect(toHex('16')).toBe('10');
      expect(toHex('128')).toBe('80');
      expect(toHex('255')).toBe('ff');
    });
  });
});
