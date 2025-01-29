import { noop } from './function/noop.js';

const DEBUG = (import.meta.env.DEBUG_CLIENT);

export const useDebug = () => {
  // eslint-disable-next-line no-console
  const handler = console[DEBUG];
  return handler
    ? (...args) => handler.call(console, ...args)
    : noop;
};
