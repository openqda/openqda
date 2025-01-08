import { flashMessage } from './flashMessage.js';

/**
 * Wraps any async call into a try/catch directive
 * and fires a flash message when an error occurs
 * @param fn {function:Promise<*>}
 * @param successMessage {string=} optional message to be flashed on success
 * @return {Promise<*>}
 */
export const attemptAsync = async (fn, successMessage) => {
  try {
    const value = await fn();
    if (successMessage) {
      flashMessage(successMessage, { type: 'success' });
    }
    return value;
  } catch (e) {
    flashMessage(e.message, { type: 'error' });
  }
};
