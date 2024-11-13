/**
 * Create a new function that limits calls to func to once every given timeframe.
 * @param func {function} the function to call in throttled mode
 * @param timeFrame {number} timeframe in ms
 * @return {function} throttled version of the function
 * @see https://github.com/you-dont-need/You-Dont-Need-Lodash-Underscore?tab=readme-ov-file#_throttle
 */
export const throttle = (func, timeFrame) => {
    let lastTime = 0;
    return function (...args) {
        let now = new Date();
        if (now - lastTime >= timeFrame) {
            func(...args);
            lastTime = now;
        }
    };
}
