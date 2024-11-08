/* Default to dark mode
 * https://tailwindcss.com/docs/dark-mode#toggling-dark-mode-manually
 * On page load or when changing themes, best to add inline in `head` to avoid FOUC
 */
import { Theme } from '../theme/Theme.js';
import { ThemeBrowserStorage } from '../theme/ThemeBrowserStorage.js';


Theme.init({ storage: ThemeBrowserStorage })
  .then(console.debug)
  .catch(console.error);


// FIXME: remove once we have a theme switcher implemented
window.Theme = Theme;
