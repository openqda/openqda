import { ThemeBrowserStorage } from './ThemeBrowserStorage.js';

describe('ThemeBrowserStorage', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  describe('isDefined', () => {
    it('should return false if there is no entry in storage', async () => {
      const result = await ThemeBrowserStorage.isDefined();
      expect(result).toBe(false);
    });

    it('should return true if there is an entry in storage', async () => {
      localStorage.setItem('theme', 'dark');
      const result = await ThemeBrowserStorage.isDefined();
      expect(result).toBe(true);
    });
  });

  describe('value', () => {
    it('should return null if there is no entry in storage', async () => {
      const result = await ThemeBrowserStorage.value();
      expect(result).toBeNull();
    });

    it('should return the value from storage', async () => {
      localStorage.setItem('theme', 'dark');
      const result = await ThemeBrowserStorage.value();
      expect(result).toBe('dark');
    });
  });

  describe('update', () => {
    it('should update the value in storage', async () => {
      const result = await ThemeBrowserStorage.update('light');
      expect(result).toBe(true);
      expect(localStorage.getItem('theme')).toBe('light');
    });
  });

  describe('remove', () => {
    it('should remove the value from storage', async () => {
      localStorage.setItem('theme', 'dark');
      const result = await ThemeBrowserStorage.remove();
      expect(result).toBe(true);
      expect(localStorage.getItem('theme')).toBeNull();
    });
  });
});
