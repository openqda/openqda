/** @module **/

/**
 * Failsafe way to stringify any object
 * of arbitrary structure.
 *
 * @function
 * @param obj {object}
 * @param space {number}
 * @return {string}
 */
export const safeStringify = (obj = {}, space = 0) => {
  const map = new WeakMap();
  let count = 0;
  const replacer = (key, value) => {
    const type = typeof value;
    if (type === 'function') {
      return '$$function';
    }
    if (type !== 'object') {
      return value;
    }
    if (map.has(value)) {
      return `$$ref-${map.get(value)}`;
    }
    map.set(value, count++);
    return value;
  };
  return JSON.stringify(obj, replacer, space);
};
