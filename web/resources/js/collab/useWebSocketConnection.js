import { reactive, toRefs } from 'vue';
import { useEcho } from './useEcho.js';
import { safeStringify } from '../utils/safeStringify.js';

const state = reactive({
  initialized: false,
  connected: false,
  connecting: false,
  failed: false,
  unavailable: false,
  info: null,
  status: 'uninitialized',
});

const log = [];
const debug = (...args) => log.push(args.join(' '));

window.debugSocket = () => {
  return { state, log };
};

/**
 * Exposes the state of the current websocket connection
 * that is a requirement for collaborative features.
 * @return {ToRefs<UnwrapNestedRefs<{connected: boolean, reason: null, unavailable: boolean, failed: boolean,
 *     connecting: boolean}> & {}>}
 */
export const useWebSocketConnection = () => {
  const { connected, connecting, unavailable, failed, status, initialized } =
    toRefs(state);

  const initWebSocket = () => {
    debug('initWebSocket');
    if (initialized.value) {
      debug('websocket already initialized');
      return;
    }

    debug('get echo');
    const echo = useEcho().init();
    debug(`echo state: ${echo.connector.pusher.connection.state}`);
    debug('add connecting listener');

    echo.connector.pusher.connection.bind('connecting', (payload) => {
      /**
       * All dependencies have been loaded and Channels is trying to connect.
       * The connection will also enter this state when it is trying to reconnect after a connection failure.
       */
      state.connecting = true;
      state.status = 'Connecting';
      debug('Echo connecting...', safeStringify(payload));
    });

    debug('add connected listener');
    echo.connector.pusher.connection.bind('connected', (payload) => {
      /**
       * The connection to Channels is open and authenticated with your app.
       */
      state.connected = true;
      state.connecting = false;
      state.status = 'Connected';
      debug('Echo connected!', safeStringify(payload));
    });

    debug('add unavailable listener');
    echo.connector.pusher.connection.bind('unavailable', (payload) => {
      /**
       *  The connection is temporarily unavailable. In most cases this means that there is no internet connection.
       *  It could also mean that Channels is down, or some intermediary is blocking the connection. In this state,
       *  pusher-js will automatically retry the connection every 15 seconds.
       */
      state.unavailable = true;
      state.connecting = false;
      state.status = 'Unavailable or unreachable';
      debug('Echo unavailable or unreachable.', JSON.stringify(payload));
    });

    debug('add failed listener');
    echo.connector.pusher.connection.bind('failed', (payload) => {
      /**
       * Channels is not supported by the browser.
       * This implies that WebSockets are not natively available and an HTTP-based transport could not be found.
       */
      state.failed = true;
      state.connecting = false;
      state.status = `Failed: ${payload}`;
      debug('Echo failed →', safeStringify(payload));
    });

    debug('add disconnected listener');
    echo.connector.pusher.connection.bind('disconnected', (payload) => {
      /**
       * The Channels connection was previously connected and has now intentionally been closed
       */
      state.connected = false;
      state.status = 'disconnected';
      state.info = payload;
      debug('Echo disconnected...', safeStringify(payload));
    });

    debug('add error listener');
    echo.connector.pusher.connection.bind('error', (payload) => {
      /**
       * The Channels connection was previously connected and has now intentionally been closed
       */
      state.connected = false;
      state.status = 'error';
      state.info = payload;
      debug('Echo disconnected...', safeStringify(payload));
    });

    debug('complete init');
    state.initialized = true;
  };
  return {
    connected,
    connecting,
    unavailable,
    failed,
    status,
    initWebSocket,
    initialized,
  };
};
