/**
 * @module
 */

/**
 * Debounces a function that is called often to run computation
 * only after given x milliseconds.
 *
 * @function
 * @see https://github.com/you-dont-need/You-Dont-Need-Lodash-Underscore#_debounce
 * @param func {function} the expensive computation fn
 * @param wait {number} timeout in ms
 * @param immediate {boolean=} run immediate
 * @returns {(function(): void)|*} the debounced function
 */
export const debounce = function debounce(func, wait, immediate) {
  let timeout;
  return function (...args) {
    let context = this;
    clearTimeout(timeout);
    if (immediate && !timeout) func.apply(context, args);
    timeout = setTimeout(function () {
      timeout = null;
      if (!immediate) func.apply(context, args);
    }, wait);
  };
};
