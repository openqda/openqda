import { rgbaToValues } from './rgbaToValues.js';
import { toHex } from './toHex.js';

/**
 * @module
 */

/**
 * Changes a color's alpha (opacity) value to the given value.
 * @function
 * @param color {string}
 * @param opacity {number}
 * @return {string}
 */
export const changeOpacity = (color, opacity = 1) => {
  if (typeof color === 'undefined' || color === null) {
    throw new Error(`Expected color, got ${color}`);
  }
  if (color.startsWith('rgb')) {
    return changeRGBAOpacity(color, opacity);
  }
  if (color.startsWith('#')) {
    return changeHexOpacity(color, opacity);
  }
  throw new Error(`Unsupported color value ${color}`);
};

/**
 * @private
 * @param hex
 * @param opacity
 * @return {`${string}${string}`}
 */
const changeHexOpacity = (hex, opacity) => {
  const alpha = toHex(Math.floor(opacity * 255));
  return `${hex.substring(0, 7)}${alpha}`;
};

/**
 * @private
 * @param rgba
 * @param opacity
 * @return {*|string}
 */
const changeRGBAOpacity = (rgba, opacity) => {
  const rgbaValues = rgbaToValues(rgba);
  const resolved = opacity ?? rgbaValues[3] ?? 1;
  if (rgbaValues && rgbaValues.length >= 3) {
    return `rgba(${rgbaValues[0]}, ${rgbaValues[1]}, ${rgbaValues[2]}, ${resolved})`;
  }
  return rgba;
};
