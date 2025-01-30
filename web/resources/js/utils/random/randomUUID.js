/**
 * @module
 */

/**
 * Generates a random UUID, prefers Web Crypto API
 * and falls back to Math.random
 * @see https://stackoverflow.com/questions/65861596/explanation-of-syntax-on-guid-uuid-function-in-javascript
 * @function
 * @return {`${string}-${string}-${string}-${string}-${string}`|string}
 */
export const randomUUID = () => {
  if (typeof window?.crypto?.randomUUID === 'function') {
    return crypto.randomUUID();
  }

  // fallback for older browsers
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
    var r = (Math.random() * 16) | 0,
      v = c == 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
};
