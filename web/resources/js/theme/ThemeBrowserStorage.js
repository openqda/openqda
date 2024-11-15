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
const storage = localStorage

ThemeBrowserStorage.isDefined = async () =>
    storage && storageKey in storage;

ThemeBrowserStorage.value = async () => {
  const value = storage.getItem(storageKey);
  return value ?? null;
};

ThemeBrowserStorage.update = async (name) => {
    storage.setItem(storageKey, name);
  return true;
};

ThemeBrowserStorage.remove = async () => {
    storage.removeItem(storageKey);
  return true;
};
