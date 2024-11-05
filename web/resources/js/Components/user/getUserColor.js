import { memoize } from '../../utils/memoize.js';

export const getUserColor = memoize(
  (email) =>
    (email ?? '').split('').reduce((a, b) => a + b.charCodeAt(0), 0) % 256
);
