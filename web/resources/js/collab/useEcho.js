/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { isDev } from '../utils/isDev.js';

window.Pusher = Pusher;

const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';
if (!isDev && !forceTLS) {
  throw new Error(
    'Unencrypted Websocket transport is not supported in production mode'
  );
}

const enabledTransports = isDev ? [forceTLS ? 'wss' : 'ws'] : ['ws', 'wss'];

/**
 * Helper to initialize and retrieve the Websocket
 * client (Echo).
 *
 * @return {{init: (function(): Echo), echo: (function(): Echo)}}
 */
export const useEcho = () => {
  const options = {
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS,
    encrypted: isDev ? forceTLS : true,
    enabledTransports,
  };
  return {
    init: () => {
      if (typeof window.Echo === 'undefined') {
        window.Echo = new Echo(options);
      }
      return window.Echo;
    },
    echo: () => window.Echo,
    options: () => ({ ...options }),
  };
};
