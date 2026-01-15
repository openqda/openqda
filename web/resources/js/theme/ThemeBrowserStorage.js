import BackendRequest from '../utils/http/BackendRequest.js';

/**
 * @module
 */

/**
 * @typedef ThemeStorage
 * @property {function():Promise<Boolean>} isDefined checks if there is an entry in storage
 * @property {function():Promise<String|null>} value returns the current value from strorage
 * @property {function(name:string):Promise<Boolean>} update updates the value in storage
 * @property {function():Promise<Boolean>} remove removes the value from storage
 */

/**
 * Storage implementation to store theme decisions in the database only.
 * No localStorage is used. Defaults to 'light' theme when user is not authenticated.
 * Uses simple polling to sync across tabs.
 * @type {ThemeStorage}
 */
export const ThemeBrowserStorage = {};

// Cache the fetch request to prevent multiple concurrent requests
let fetchPromise = null;

const fetchThemePreference = async () => {
  // Reuse in-flight request if one exists
  if (fetchPromise) {
    return fetchPromise;
  }

  fetchPromise = (async () => {
    const timestamp = new Date().getTime();
    const request = new BackendRequest({
      url: `/preferences?_=${timestamp}`,
      type: 'get',
      extraOptions: {
        headers: {
          'Cache-Control': 'no-cache, no-store, must-revalidate',
          Pragma: 'no-cache',
          Expires: '0',
        },
      },
    });

    try {
      await request.send();
      return request.response?.data?.theme ?? null;
    } catch {
      return null;
    } finally {
      // Clear cache after request completes
      fetchPromise = null;
    }
  })();

  return fetchPromise;
};

ThemeBrowserStorage.isDefined = async () => {
  // For unauthenticated users, theme is null but we want to use default 'light'
  // So always return true to ensure Theme.init uses our value() method
  return true;
};

ThemeBrowserStorage.value = async () => {
  const theme = await fetchThemePreference();
  if (theme) {
    console.warn('Loaded theme from database:', theme);
    return theme;
  }

  console.warn(
    'Not authenticated or error fetching preferences, using default light theme'
  );
  return 'light';
};

ThemeBrowserStorage.update = async (name) => {
  console.warn('ThemeBrowserStorage.update called with:', name);

  const request = new BackendRequest({
    url: '/preferences',
    type: 'put',
    body: { theme: name },
    extraOptions: {
      headers: {
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        Pragma: 'no-cache',
        Expires: '0',
      },
    },
  });
  await request.send();

  if (request.error) {
    console.error('⚠️ Failed to save theme to database:', request.error);
    return false;
  }

  if (!request.response || request.response.status !== 200) {
    console.error(
      '⚠️ Unexpected response:',
      request.response?.status,
      request.response?.data
    );
    return false;
  }

  console.warn('✅ Theme saved to database:', request.response.data);
  return true;
};

ThemeBrowserStorage.remove = async () => {
  try {
    // Reset to default light theme in database
    const request = new BackendRequest({
      url: '/preferences',
      type: 'put',
      body: { theme: 'light' },
    });
    await request.send();
    console.warn('✅ Theme reset to light in database');
    return true;
  } catch (error) {
    console.warn('⚠️ Could not reset theme in database:', error.message);
    return false;
  }
};
