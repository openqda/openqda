import { describe, expect, it } from 'vitest';
import { changeOpacity } from './changeOpacity.js';

describe('changeOpacity', () => {
  describe('input validation', () => {
    it('throws when color is undefined', () => {
      expect(() => changeOpacity(undefined)).toThrow(
        'Expected color, got undefined'
      );
    });
    it('throws when color is null', () => {
      expect(() => changeOpacity(null)).toThrow('Expected color, got null');
    });
    it('throws on unsupported color formats', () => {
      expect(() => changeOpacity('hsl(0, 100%, 50%)')).toThrow(
        'Unsupported color value'
      );
      expect(() => changeOpacity('blue')).toThrow('Unsupported color value');
      expect(() => changeOpacity('')).toThrow('Unsupported color value');
    });
  });

  describe('hex colors', () => {
    it('changes opacity of a 6-char hex color', () => {
      expect(changeOpacity('#ff0000', 1)).toBe('#ff0000ff');
      expect(changeOpacity('#00ff00', 0.5)).toBe('#00ff007f');
      expect(changeOpacity('#0000ff', 0)).toBe('#0000ff00');
    });
    it('changes opacity of an 8-char hex color (replaces existing alpha)', () => {
      expect(changeOpacity('#ff000080', 1)).toBe('#ff0000ff');
      expect(changeOpacity('#00ff00ff', 0)).toBe('#00ff0000');
    });
    it('defaults opacity to 1 when not provided', () => {
      expect(changeOpacity('#aabbcc')).toBe('#aabbccff');
    });
    it('handles black and white', () => {
      expect(changeOpacity('#000000', 0.5)).toBe('#0000007f');
      expect(changeOpacity('#ffffff', 0.5)).toBe('#ffffff7f');
    });
  });

  describe('rgb colors', () => {
    it('throws on invalid rgb', () => {
      expect(() => changeOpacity('rgba(255, 0)')).toThrow(
        'Invalid rgba rgba(255, 0)'
      );
    });
    it('converts rgb to rgba with given opacity', () => {
      expect(changeOpacity('rgb(255, 0, 0)', 0.5)).toBe('rgba(255, 0, 0, 0.5)');
    });
    it('converts rgb to rgba with default opacity of 1', () => {
      expect(changeOpacity('rgb(0, 128, 255)')).toBe('rgba(0, 128, 255, 1)');
    });
    it('converts rgb to rgba with opacity 0', () => {
      expect(changeOpacity('rgb(10, 20, 30)', 0)).toBe('rgba(10, 20, 30, 0)');
    });
  });

  describe('rgba colors', () => {
    it('changes opacity of an rgba color', () => {
      expect(changeOpacity('rgba(255, 0, 0, 1)', 0.5)).toBe(
        'rgba(255, 0, 0, 0.5)'
      );
    });
    it('changes opacity to 0', () => {
      expect(changeOpacity('rgba(100, 200, 50, 0.8)', 0)).toBe(
        'rgba(100, 200, 50, 0)'
      );
    });
    it('changes opacity to 1', () => {
      expect(changeOpacity('rgba(0, 0, 0, 0)', 1)).toBe('rgba(0, 0, 0, 1)');
    });
    it('defaults opacity to 1 when not provided', () => {
      expect(changeOpacity('rgba(10, 20, 30, 0.3)')).toBe(
        'rgba(10, 20, 30, 1)'
      );
    });
    it('preserves the rgb channel values', () => {
      const result = changeOpacity('rgba(42, 84, 126, 0.1)', 0.9);
      expect(result).toBe('rgba(42, 84, 126, 0.9)');
    });
  });

  describe('edge cases', () => {
    it('handles fractional opacity values', () => {
      expect(changeOpacity('#ff0000', 0.75)).toBe('#ff0000bf');
      expect(changeOpacity('#ff0000', 0.25)).toBe('#ff00003f');
    });
    it('handles opacity of exactly 0 and 1 for hex', () => {
      expect(changeOpacity('#abcdef', 0)).toBe('#abcdef00');
      expect(changeOpacity('#abcdef', 1)).toBe('#abcdefff');
    });
  });
});
