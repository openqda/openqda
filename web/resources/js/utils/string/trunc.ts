/**
 * @module
 */

/**
 * Truncates a string by given max length but adds
 * ... at the end. Does not add dots if its length is smaller than max - 3
 * @function
 * @param str {string}
 * @param max {number}
 * @return {string}
 */
export const trunc = (str: string, max: number = 20): string => {
  if (!str || str.length <= max - 3) return str;
  return `${str.substring(0, max - 3)}...`;
};
