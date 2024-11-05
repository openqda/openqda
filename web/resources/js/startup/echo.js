import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { reactive, toRefs } from 'vue';

window.Pusher = Pusher;

const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';
const enabledTransports = [forceTLS ? 'wss' : 'ws'];

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
  wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
  forceTLS,
  enabledTransports,
  withoutInterceptors: true,
});

const state = reactive({
  connected: false,
  connecting: false,
  failed: false,
  unavailable: false,
  status: 'disconnected',
});

/**
 * Exposes the state of the current websocket connection
 * that is a requirement for collaborative features.
 * @return {ToRefs<UnwrapNestedRefs<{connected: boolean, reason: null, unavailable: boolean, failed: boolean,
 *     connecting: boolean}> & {}>}
 */
export const useWebSocketConnection = () => {
  const { connected, connecting, unavailable, failed, status } = toRefs(state);
  return { connected, connecting, unavailable, failed, status };
};

window.Echo.connector.pusher.connection.bind('connecting', (payload) => {
  /**
   * All dependencies have been loaded and Channels is trying to connect.
   * The connection will also enter this state when it is trying to reconnect after a connection failure.
   */
  console.debug('connecting', payload);
  state.connecting = true;
  state.status = 'Connecting';
});

window.Echo.connector.pusher.connection.bind('connected', (payload) => {
  /**
   * The connection to Channels is open and authenticated with your app.
   */
  console.debug('connected', payload);
  state.connected = true;
  state.connecting = false;
  state.status = 'Connected';
});

window.Echo.connector.pusher.connection.bind('unavailable', (payload) => {
  /**
   *  The connection is temporarily unavailable. In most cases this means that there is no internet connection.
   *  It could also mean that Channels is down, or some intermediary is blocking the connection. In this state,
   *  pusher-js will automatically retry the connection every 15 seconds.
   */
  console.log('unavailable', payload);
  state.unavailable = true;
  state.connecting = false;
  state.status = 'Unavailable or unreachable';
});

window.Echo.connector.pusher.connection.bind('failed', (payload) => {
  /**
   * Channels is not supported by the browser.
   * This implies that WebSockets are not natively available and an HTTP-based transport could not be found.
   */
  console.log('failed', payload);
  state.failed = true;
  state.connecting = false;
  state.status = `Failed: ${payload}`;
});

window.Echo.connector.pusher.connection.bind('disconnected', (payload) => {
  /**
   * The Channels connection was previously connected and has now intentionally been closed
   */
  console.log('disconnected', payload);
  state.connected = false;
  state.status = 'disconnected';
});
