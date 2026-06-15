/** @module **/

/**
 * Creates a simple memoization function, supported by a map
 * @function
 * @param fn {function}
 * @param debug {function=}
 * @return {function(...[*]): any}
 */
export const memoize = (fn, debug = () => {}) => {
  const map = new Map();
  return function memoized(...args) {
    const self = this;
    const str = JSON.stringify(args, replacer, 0);
    debug(args, str, map);
    if (!map.has(str)) {
      const value = fn.apply(self, args);
      map.set(str, value);
    }

    return map.get(str);
  };
};

/**
 * @private
 * @param key
 * @param value
 * @return {*|string}
 */
const replacer = (key, value) => {
  if (typeof value === 'function') {
    return value.toString();
  }
  return value;
};
