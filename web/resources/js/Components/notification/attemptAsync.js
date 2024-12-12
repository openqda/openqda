import { flashMessage } from './flashMessage.js';

/**
 * Wraps any async call into a try/catch directive
 * and fires a flash message when an error occurs
 * @param fn {function:Promise<*>}
 * @return {Promise<*>}
 */
export const attemptAsync = async (fn) => {
  try {
    return await fn();
  } catch (e) {
    flashMessage(e.message, { type: 'error' });
  }
};
