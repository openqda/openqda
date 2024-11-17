import { reactive, toRefs } from 'vue';
import { useEcho } from '../collab/useEcho.js'
import { useDebug } from '../utils/useDebug.js'

const state = reactive({
  initialized: false,
  connected: false,
  connecting: false,
  failed: false,
  unavailable: false,
  info: null,
  status: 'uninitialized',
});

window.debugSocket = () => {
    return state
}

/**
 * Exposes the state of the current websocket connection
 * that is a requirement for collaborative features.
 * @return {ToRefs<UnwrapNestedRefs<{connected: boolean, reason: null, unavailable: boolean, failed: boolean,
 *     connecting: boolean}> & {}>}
 */
export const useWebSocketConnection = () => {
  const debug = useDebug()
  const { connected, connecting, unavailable, failed, status, initialized } = toRefs(state);

  const initWebSocket = () => {
      if (initialized.value) { return }
      state.initialized = true
      const echo = useEcho().init()

      echo.connector.pusher.connection.bind('connecting', (payload) => {
          /**
           * All dependencies have been loaded and Channels is trying to connect.
           * The connection will also enter this state when it is trying to reconnect after a connection failure.
           */
          state.connecting = true;
          state.status = 'Connecting';
          debug('Echo connecting...', payload)
      });

      echo.connector.pusher.connection.bind('connected', (payload) => {
          /**
           * The connection to Channels is open and authenticated with your app.
           */
          state.connected = true;
          state.connecting = false;
          state.status = 'Connected';
          debug('Echo connected!', payload)
      });

      echo.connector.pusher.connection.bind('unavailable', (payload) => {
          /**
           *  The connection is temporarily unavailable. In most cases this means that there is no internet connection.
           *  It could also mean that Channels is down, or some intermediary is blocking the connection. In this state,
           *  pusher-js will automatically retry the connection every 15 seconds.
           */
          state.unavailable = true;
          state.connecting = false;
          state.status = 'Unavailable or unreachable';
          debug('Echo unavailable or unreachable.', payload)
      });

      echo.connector.pusher.connection.bind('failed', (payload) => {
          /**
           * Channels is not supported by the browser.
           * This implies that WebSockets are not natively available and an HTTP-based transport could not be found.
           */
          state.failed = true;
          state.connecting = false;
          state.status = `Failed: ${payload}`;
          debug('Echo failed →', payload)
      });

      echo.connector.pusher.connection.bind('disconnected', (payload) => {
          /**
           * The Channels connection was previously connected and has now intentionally been closed
           */
          state.connected = false;
          state.status = 'disconnected';
          state.info = payload
          debug('Echo disconnected...', payload)
      });
  }
  return { connected, connecting, unavailable, failed, status, initWebSocket, initialized };
};
