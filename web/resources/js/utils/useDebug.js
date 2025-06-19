import { noop } from './function/noop.js';

/** @module **/

/** @private */
const DEBUG = import.meta.env.VITE_DEBUG_CLIENT ?? import.meta.env.DEBUG_CLIENT;

/**
 * composable to enable extended debugging, with optional scopes
 * and override mode. Automatically activates, if the .env file
 * contains `VITE_DEBUG_CLIENT` with a value, that is a supported
 * `console` method, such as 'log', 'info', 'debug', 'warn', 'error' etc.
 * @function
 * @param mode
 * @param scope
 * @return {(function(any):any)}
 */
export const useDebug = ({ mode, scope } = {}) => {
  const type = mode ?? DEBUG;
  const prefix = scope ? `[${type}][${scope}]:` : `[${type}]:`;
  // eslint-disable-next-line no-console
  const handler = console[type];
  return handler ? (...args) => handler.call(console, prefix, ...args) : noop;
};

window.useDebug = useDebug;
