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

ThemeBrowserStorage.isDefined = async () => {
  try {
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
    await request.send();
    return request.response?.data && request.response.data.theme !== null;
  } catch {
    // User not authenticated, default to light theme
    return false;
  }
};

ThemeBrowserStorage.value = async () => {
  try {
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
    await request.send();
    if (request.response?.data && request.response.data.theme) {
      console.warn('Loaded theme from database:', request.response.data.theme);
      return request.response.data.theme;
    }
  } catch {
    console.warn(
      'Not authenticated or error fetching preferences, using default light theme'
    );
  }

  // Return default light theme when not authenticated or error
  return 'light';
};

ThemeBrowserStorage.update = async (name) => {
  console.warn('ThemeBrowserStorage.update called with:', name);

  try {
    // Save to database only
    const request = new BackendRequest({
      url: '/preferences',
      type: 'put',
      data: { theme: name },
      extraOptions: {
        headers: {
          'Cache-Control': 'no-cache, no-store, must-revalidate',
          Pragma: 'no-cache',
          Expires: '0',
        },
      },
    });
    await request.send();
    console.warn('✅ Theme saved to database:', request.response?.data);
    return true;
  } catch (error) {
    console.warn('⚠️ Failed to save theme to database:', error.message);
    return false;
  }
};

ThemeBrowserStorage.remove = async () => {
  try {
    // Reset to default light theme in database
    const request = new BackendRequest({
      url: '/preferences',
      type: 'put',
      data: { theme: 'light' },
    });
    await request.send();
    console.warn('✅ Theme reset to light in database');
    return true;
  } catch (error) {
    console.warn('⚠️ Could not reset theme in database:', error.message);
    return false;
  }
};
