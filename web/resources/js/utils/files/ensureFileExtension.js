/**
 * @module
 */

/**
 * Ensures a filename ends with the given extension.
 * @function
 * @param name {string} given filename
 * @param ending {string} the file extension WITHOUT dot
 * @return {string}
 */
export const ensureFileExtension = (name, ending) => {
  return name.endsWith(`.${ending}`) ? name : `${name}.${ending}`;
};
