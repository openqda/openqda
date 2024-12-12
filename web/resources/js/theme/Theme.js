import { ThemeEmptyStorage } from './ThemeEmptyStorage.js';

/**
 * A facade to the underlying theming system that
 * supports different storage implementations.
 */
export const Theme = {};

let storage = null;
const dark = 'dark';
const light = 'light';

Theme.DARK = dark;
Theme.LIGHT = light;

/**
 * Auto-detects stored decision for the theme and
 * falls back to system level preference.
 * Otherwise, uses light theme.
 * @param options {object}
 * @param options.storage {ThemeStorage}
 * @param options.useStorage {boolean}
 * @param options.usePreferred {boolean}
 * @return {Promise<{from: string, name: string}>} the name of the decision used for the theme.
 */
Theme.init = async (options) => {
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

Theme.is = (name) => name === DOM.current;

Theme.current = () => DOM.current;

Theme.update = async (name) => {
  DOM.add(name);
  return storage.update(name);
};

Theme.remove = async () => {
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
