/**
 * @module
 */

/**
 * Repeat a function in an interval (cycles) of given time until it returns a truthy value.
 * Returns a cleanup function
 * @function
 * @param fn {function} the function to call
 * @param cycles {number} the number of cycles to retry the function call, set to -1 for infinite cycles
 * @param timeout {number} timeout between each call in ms
 * @return {function(): void} a cleanup function to force-stop the interval (for example on unmount)
 */
export const retry = (fn, cycles = 1, timeout = 300) => {
  let count = 0;
  const timer = setInterval(() => {
    if (fn() || count++ === cycles) {
      clear();
    }
  }, timeout);
  const clear = () => clearInterval(timer);
  return clear;
};
