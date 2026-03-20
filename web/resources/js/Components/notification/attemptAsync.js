import { flashMessage } from './flashMessage.js';

/**
 * Wraps any async call into a try/catch directive
 * and fires a flash message when an error occurs
 * @param fn {function(any):Promise<any>}
 * @param successMessage {string=} optional message to be flashed on success
 * @return {Promise<any>}
 */
export const attemptAsync = async (fn, successMessage) => {
  try {
    const value = await fn();
    if (successMessage) {
      flashMessage(successMessage, { type: 'success' });
    }

    // special case for backend request
    if (value?.error) {
      throw value.error;
    }
    if (value?.response?.status >= 400) {
      throw new Error(
        value.response.data?.message ??
          'An error occurred while processing your request!'
      );
    }

    return value;
  } catch (e) {
    flashMessage(e.message, { type: 'error' });
  }
};
