import { noop } from './function/noop.js';

const DEBUG = (import.meta.env.VITE_DEBUG_CLIENT ?? import.meta.env.DEBUG_CLIENT);

export const useDebug = (mode) => {
  const type = mode ?? DEBUG;
  // eslint-disable-next-line no-console
  const handler = console[type];
  return handler
    ? (...args) => handler.call(console, `[${type}]:`, ...args)
    : noop;
};

window.useDebug = useDebug
