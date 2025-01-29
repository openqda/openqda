import { noop } from './function/noop.js';

const DEBUG = import.meta.env.VITE_DEBUG_CLIENT ?? import.meta.env.DEBUG_CLIENT;

export const useDebug = ({ mode, scope } = {}) => {
  const type = mode ?? DEBUG;
  const prefix = scope ? `[${type}][${scope}]:` : `[${type}]:`;
  // eslint-disable-next-line no-console
  const handler = console[type];
  return handler ? (...args) => handler.call(console, prefix, ...args) : noop;
};

window.useDebug = useDebug;
