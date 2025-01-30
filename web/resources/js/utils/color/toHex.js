import { rgbaToValues } from './rgbaToValues.js';

/**
 * @module
 */

/**
 * Converts a given color value (0..255) to hex value.
 * @function
 * @param val {number|string}
 * @return {string} zero-padded hex value
 */
export const toHex = (val) => {
  const num = ['number', 'string'].includes(typeof val)
    ? Number(val)
    : Number.NaN;

  if (Number.isNaN(num) || num < 0 || num > 255) {
    throw new TypeError(`Invalid value to convert, ${val}`);
  }

  const hex = num.toString(16);
  return hex.length === 1 ? '0' + hex : hex;
};

/**
 * Transforms a given rgb() or rgba() string to hex
 * @function
 * @param rgb
 * @return {string}
 */
export const rgbToHex = (rgb = '') => {
  if (rgb.startsWith('#')) return rgb;

  const [r, g, b, a] = rgbaToValues(rgb);
  let values = '#' + toHex(r) + toHex(g) + toHex(b);

  return typeof a !== 'undefined' ? `${values}${toHex(a)}` : values;
};
