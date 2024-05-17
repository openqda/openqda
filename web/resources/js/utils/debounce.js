/**
 * Debounces a function that is called often to run computation
 * only given x miliseconds.
 * Credits: https://github.com/you-dont-need/You-Dont-Need-Lodash-Underscore#_debounce
 * @param func {function} the expensive computation fn
 * @param wait {number} timeout in ms
 * @param immediate {boolean=} run immediate
 * @returns {(function(): void)|*} the debounced function
 */
export const debounce = function debounce(func, wait, immediate) {
  let timeout;
  return function () {
    let context = this,
      args = arguments;
    clearTimeout(timeout);
    if (immediate && !timeout) func.apply(context, args);
    timeout = setTimeout(function () {
      timeout = null;
      if (!immediate) func.apply(context, args);
    }, wait);
  };
};
