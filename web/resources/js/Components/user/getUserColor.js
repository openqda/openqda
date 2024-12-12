import { memoize } from '../../utils/memoize.js';

/**
 * Generates a color code, specifically related
 * to this user's email.
 * Same inputs generate the same color.
 * @type {(function(...[*]): unknown)|*}
 */
export const getUserColor = memoize(
  (email) =>
    (email ?? '').split('').reduce((a, b) => a + b.charCodeAt(0), 0) % 256
);
