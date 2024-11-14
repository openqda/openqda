import { flashMessage } from './flashMessage.js';

export const attemptAsync = async (fn) => {
  try {
    return await fn();
  } catch (e) {
    flashMessage(e.message, { type: 'error' });
  }
};
