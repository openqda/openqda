import { Theme } from '../theme/Theme.js';
import { ThemeBrowserStorage } from '../theme/ThemeBrowserStorage.js';
import BackendRequest from '../utils/http/BackendRequest.js';

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
      const request = new BackendRequest({
        url: '/preferences',
        type: 'get',
        extraOptions: { timeout: 3000 },
      });
      await request.send();

      const currentTheme = request.response?.data?.theme || 'light';
      const htmlElement = document.documentElement;
      const activeTheme = htmlElement.getAttribute('data-theme') || 'light';

      if (currentTheme !== activeTheme) {
        htmlElement.setAttribute('data-theme', currentTheme);
        if (currentTheme === 'dark') {
          htmlElement.classList.add('dark');
        } else {
          htmlElement.classList.remove('dark');
        }
        console.warn('Theme synced from DB:', currentTheme);
      }
    } catch (error) {
      console.warn('Theme sync skipped:', error.message);
    }
  }
});
