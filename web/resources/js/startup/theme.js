import { Theme } from '../theme/Theme.js';
import { ThemeBrowserStorage } from '../theme/ThemeBrowserStorage.js';
import axios from 'axios';

/**
 * Theme initialization - defaults to light mode
 * Fetches theme from database when user is authenticated
 * https://tailwindcss.com/docs/dark-mode#toggling-dark-mode-manually
 * On page load or when changing themes, best to add inline in `head` to avoid FOUC
 */
Theme.init({
  storage: ThemeBrowserStorage,
  usePreferred: false, // Don't use OS preference
  useStorage: true, // Use database storage
}).catch(console.error);

/**
 * Sync theme across tabs when page regains focus
 * Fetches theme from database (DB-only, no client-side caching)
 */
document.addEventListener('visibilitychange', async () => {
  if (document.visibilityState === 'visible') {
    try {
      // 3s timeout to avoid long delays
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 3000);

      const response = await axios.get('/preferences', { signal: controller.signal });
      clearTimeout(timeoutId);

      const currentTheme = response.data.theme || 'light';
      const htmlElement = document.documentElement;
      const activeTheme = htmlElement.getAttribute('data-theme') || 'light';

      if (currentTheme !== activeTheme) {
        htmlElement.setAttribute('data-theme', currentTheme);
        if (currentTheme === 'dark') {
          htmlElement.classList.add('dark');
        } else {
          htmlElement.classList.remove('dark');
        }
        console.log('Theme synced from DB:', currentTheme);
      }
    } catch (error) {
      console.debug('Theme sync skipped:', error.message);
    }
  }
});
