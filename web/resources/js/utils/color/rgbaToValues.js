/**
 * @module
 */

/**
 * Extracts the values from a given css-compatible rgba() string.
 *
 * @function
 * @throws {TypeError} if given param is not a valid rgb() or rgba() string.
 * @param rgba {string} the css-compatible rgba(r, g, b, a) string
 * @return {string[]}
 *  Array with the extracted values.
 *  Note they are strings due to the regex match.
 *  You need to map them to numbers if necessary.
 */
export const rgbaToValues = (rgba) => {
  const rgbaValues = rgba && rgba.startsWith('rgb') && rgba.match(num);
  const length = rgbaValues?.length ?? 0;
  if (length < 3) {
    throw new TypeError(`Invalid rgba ${rgba}`);
  }
  return rgbaValues;
};

/**
 * @private
 * @type {RegExp}
 */
const num = /[\d.]+/g;
