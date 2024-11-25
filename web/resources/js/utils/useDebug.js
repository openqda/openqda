import { noop } from './function/noop.js';

export const useDebug = () => {
  return window.debugHandler ?? noop;
};
