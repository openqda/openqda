import { ThemeEmptyStorage } from './ThemeEmptyStorage.js';

/**
 * @module
 */

/** @private **/
let storage = null;
/**
 * @private
 * @type {string}
 */
const dark = 'dark';

/**
 *  @private
 * @type {string}
 */
const light = 'light';

/**
 * Auto-detects stored decision for the theme and
 * falls back to system level preference.
 * Otherwise, uses light theme.
 * @function
 * @param options {object}
 * @param options.storage {ThemeStorage}
 * @param options.useStorage {boolean}
 * @param options.usePreferred {boolean}
 * @return {Promise<{from: string, name: string}>} the name of the decision used for the theme.
 */
const init = async (options) => {
  storage = options.storage ? options.storage : new ThemeEmptyStorage();

  // phase 1: fetch the preferred theme from storage
  if (options.useStorage) {
    let storedTheme = null;
    if (
      storage &&
      (await storage.isDefined()) &&
      (storedTheme = await storage.value()) !== null
    ) {
      DOM.add(storedTheme);
      return { from: 'storage', name: storedTheme };
    }
  }

  // phase 2: use the preferred theme from OS-level preferences
  // but to not store the decision as it's not an active user decision
  if (options.usePreferred !== false) {
    let osPreferred = DOM.getPreferred([dark, light]);
    if (osPreferred) {
      DOM.add(osPreferred);
      return { from: 'preferred', name: osPreferred };
    }
  }

  // phase 2: fall back to light theme as default
  // but to not store the decision as it's not an active user decision
  DOM.add(light);
  return { from: 'fallback', name: light };
};

/**
 * Check if current applied theme (in DOM)
 * is the theme of the given name
 * @function
 * @param name {string}
 * @return {boolean}
 */
const is = (name) => name === DOM.current;

/**
 * Get the name of the current applied theme in DOM
 * @function
 * @return {null|string}
 */
const current = () => DOM.current;

/**
 * Updates the current theme by given value,
 * if supported (light, dark)
 * @function
 * @param name {string}
 * @return {void} - updates immediately without waiting
 */
const update = (name) => {
  // Update DOM immediately (synchronous, no await)
  DOM.add(name);
  
  // Fire backend sync in background without blocking
  Promise.resolve().then(() => {
    storage.update(name).catch(() => {});
  });
};

/**
 * Removes the current theme from DOM
 * @function
 * @async
 * @return {Promise<*>}
 */
const remove = async () => {
  DOM.remove(DOM.current);
  return storage.remove();
};

/**
 * Internal handler to update the DOM manually,
 * pimarily on the html root
 * @private
 */
const DOM = {};
DOM.current = null;
DOM.getPreferred = (names) => names.find((name) => DOM.isPreferred(name));
DOM.isPreferred = (name) => {
  const pattern = `(prefers-color-scheme: ${name})`;
  return window.matchMedia && window.matchMedia(pattern).matches;
};
DOM.add = (name) => {
  if (DOM.current && name !== DOM.current) {
    DOM.remove(DOM.current);
  }
  DOM.current = name;
  document.documentElement.classList.add(name);
};
DOM.remove = (name) => {
  DOM.current = null;
  document.documentElement.classList.remove(name);
};

/**
 * A facade to the underlying theming system that
 *  supports different storage implementations.
 * @constant
 * @default
 */
export const Theme = {
  DARK: dark,
  LIGHT: light,
  init,
  is,
  current,
  update,
  remove,
};
