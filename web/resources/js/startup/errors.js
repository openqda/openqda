/*
 * Global error handling setup to forward any unhandled error
 * to the flash message component.
 */

import { flashMessage } from '../Components/notification/flashMessage.js';

window.onerror = function (messageOrEvent) {
  const message =
    typeof messageOrEvent === 'object'
      ? messageOrEvent.message
      : messageOrEvent;
  flashMessage(message, { type: 'error' });
};
window.addEventListener('unhandledrejection', (event) => {
  flashMessage(event.reason, { type: 'error' });
});
