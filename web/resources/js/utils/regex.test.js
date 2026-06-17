import { whitespace } from './regex.js';

describe('regex', () => {
  describe('whitespace', () => {
    it('should match one or more whitespace characters', () => {
      const testString = 'This is a test string.';
      const matches = testString.match(whitespace);
      expect(matches).toEqual([' ', ' ', ' ', ' ']);
    });

    it('should not match non-whitespace characters', () => {
      const testString = 'NoWhitespaceHere';
      const matches = testString.match(whitespace);
      expect(matches).toBeNull();
    });

    it('should match tabs and newlines as well', () => {
      const testString = 'Line 1\nLine 2\tTabbed\r';
      const matches = testString.match(whitespace);
      expect(matches).toEqual([' ', '\n', ' ', '\t', '\r']);
    });
  });
});
