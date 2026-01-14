import axios from 'axios';

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
    const response = await axios.get(`/preferences?_=${timestamp}`, {
      headers: { 
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache',
        'Expires': '0'
      }
    });
    return response.data && response.data.theme !== null;
  } catch {
    // User not authenticated, default to light theme
    return false;
  }
};

ThemeBrowserStorage.value = async () => {
  try {
    const timestamp = new Date().getTime();
    const response = await axios.get(`/preferences?_=${timestamp}`, {
      headers: { 
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache',
        'Expires': '0'
      }
    });
    if (response.data && response.data.theme) {
      console.warn('Loaded theme from database:', response.data.theme);
      return response.data.theme;
    }
  } catch (error) {
    console.warn('Not authenticated or error fetching preferences, using default light theme');
  }
  
  // Return default light theme when not authenticated or error
  return 'light';
};

ThemeBrowserStorage.update = async (name) => {
  console.warn('ThemeBrowserStorage.update called with:', name);
  
  try {
    // Save to database only
    const response = await axios.put('/preferences', { theme: name }, {
      headers: { 
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache',
        'Expires': '0'
      }
    });
    console.warn('✅ Theme saved to database:', response.data);
    return true;
  } catch (error) {
    console.warn('⚠️ Failed to save theme to database:', error.message);
    return false;
  }
};

ThemeBrowserStorage.remove = async () => {
  try {
    // Reset to default light theme in database
    await axios.put('/preferences', { theme: 'light' });
    console.warn('✅ Theme reset to light in database');
    return true;
  } catch (error) {
    console.warn('⚠️ Could not reset theme in database:', error.message);
    return false;
  }
};
