/**
 * @module
 */

/**
 * Generates a random string of given length.
 * Prefers web-crypto API (if available) and falls
 * back to Math.random
 *
 * @function
 * @param length {number}
 * @param provider {string}
 * @return {string|string}
 */
export const randomString = (length = 16, provider = 'crypto') => {
  return provider === 'crypto' && 'crypto' in window
    ? randCrypto(length)
    : randRandom(length);
};

/**
 * @function
 * @private
 * @param length
 * @return {string}
 */
const randCrypto = (length) => {
  const array = new Uint32Array(length);
  crypto.getRandomValues(array);
  const utf8decoder = new TextDecoder();
  return utf8decoder.decode(array);
};

/**
 * @function
 * @private
 * @type {string}
 */
const characters =
  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

/**
 * @function
 * @private
 * @param length
 * @return {string}
 */
const randRandom = (length) => {
  let out = '';
  for (let i = 0; i < length; i++) {
    out += characters.charAt(Math.random() * characters.length);
  }
  return out;
};
