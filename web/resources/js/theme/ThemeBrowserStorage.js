/**
 * @typedef ThemeStorage
 * @property {function():Promise<Boolean>} isDefined
 * @property {function():Promise<String|null>} value
 * @property {function(name:string):Promise<Boolean>} update
 * @property {function():Promise<Boolean>} remove
 */

/**
 * Storage implementation to store theme decisions in the browser's
 * localStorage.
 * @type {ThemeStorage}
 */
export const ThemeBrowserStorage = {};

const storageKey = 'theme';

ThemeBrowserStorage.isDefined = async () =>
  localStorage && storageKey in localStorage;

ThemeBrowserStorage.value = async () => {
  const value = localStorage.getItem(storageKey);
  return value ?? null;
};

ThemeBrowserStorage.update = async (name) => {
  localStorage.setItem(storageKey, name);
  return true;
};

ThemeBrowserStorage.remove = async () => {
  localStorage.removeItem(storageKey);
  return true;
};
