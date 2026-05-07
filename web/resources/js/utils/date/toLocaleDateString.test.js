import { describe, expect, it, vi } from 'vitest';
import { toLocaleDateString } from './toLocaleDateString.js';

describe('toLocaleDateString', () => {
  describe('invalid / falsy values return empty string', () => {
    it('returns empty string for null', () => {
      expect(toLocaleDateString(null)).toBe('');
    });

    it('returns empty string for undefined', () => {
      expect(toLocaleDateString(undefined)).toBe('');
    });

    it('returns empty string for 0', () => {
      expect(toLocaleDateString(0)).toBe('');
    });

    it('returns empty string for empty string', () => {
      expect(toLocaleDateString('')).toBe('');
    });

    it('returns empty string for false', () => {
      expect(toLocaleDateString(false)).toBe('');
    });

    it('returns empty string for negative numbers', () => {
      expect(toLocaleDateString(-1)).toBe('');
      expect(toLocaleDateString(-1000)).toBe('');
    });
  });

  describe('default options (date and time)', () => {
    it('formats a Date-parseable string using toLocaleString', () => {
      const input = '2024-06-15T10:30:00Z';
      const expected = new Date(input).toLocaleString();
      expect(toLocaleDateString(input)).toBe(expected);
    });

    it('formats a unix timestamp in milliseconds using toLocaleString', () => {
      const ts = 1718444400000;
      const expected = new Date(ts).toLocaleString();
      expect(toLocaleDateString(ts)).toBe(expected);
    });
  });

  describe('date only (time = false)', () => {
    it('returns only the date part', () => {
      const input = '2024-06-15T10:30:00Z';
      const expected = new Date(input).toLocaleDateString();
      expect(toLocaleDateString(input, { date: true, time: false })).toBe(
        expected
      );
    });

    it('uses toLocaleDateString when time is explicitly false', () => {
      const spy = vi.spyOn(Date.prototype, 'toLocaleDateString');
      toLocaleDateString('2024-01-01', { time: false });
      expect(spy).toHaveBeenCalled();
      spy.mockRestore();
    });
  });

  describe('time only (date = false)', () => {
    it('returns only the time part', () => {
      const input = '2024-06-15T10:30:00Z';
      const expected = new Date(input).toLocaleTimeString();
      expect(toLocaleDateString(input, { date: false, time: true })).toBe(
        expected
      );
    });

    it('uses toLocaleTimeString when date is explicitly false', () => {
      const spy = vi.spyOn(Date.prototype, 'toLocaleTimeString');
      toLocaleDateString('2024-01-01', { date: false });
      expect(spy).toHaveBeenCalled();
      spy.mockRestore();
    });
  });

  describe('both date and time (default)', () => {
    it('returns full locale string when both are true', () => {
      const input = '2024-06-15T10:30:00Z';
      const expected = new Date(input).toLocaleString();
      expect(toLocaleDateString(input, { date: true, time: true })).toBe(
        expected
      );
    });
  });

  describe('both date and time false', () => {
    it('returns toLocaleString when both are false', () => {
      const input = '2024-06-15T10:30:00Z';
      const expected = new Date(input).toLocaleString();
      expect(toLocaleDateString(input, { date: false, time: false })).toBe(
        expected
      );
    });
  });

  describe('options defaults', () => {
    it('works without passing options at all', () => {
      const input = '2024-06-15T10:30:00Z';
      const expected = new Date(input).toLocaleString();
      expect(toLocaleDateString(input)).toBe(expected);
    });

    it('defaults date to true when only time is passed', () => {
      const input = '2024-06-15T10:30:00Z';
      const expected = new Date(input).toLocaleDateString();
      expect(toLocaleDateString(input, { time: false })).toBe(expected);
    });

    it('defaults time to true when only date is passed', () => {
      const input = '2024-06-15T10:30:00Z';
      const expected = new Date(input).toLocaleTimeString();
      expect(toLocaleDateString(input, { date: false })).toBe(expected);
    });
  });

  describe('various valid input types', () => {
    it('handles ISO date strings', () => {
      const input = '2024-12-25';
      const result = toLocaleDateString(input);
      expect(result).toBe(new Date(input).toLocaleString());
    });

    it('handles positive integer timestamps', () => {
      const ts = 1000000;
      expect(toLocaleDateString(ts)).toBe(new Date(ts).toLocaleString());
    });

    it('handles date-time strings with timezone', () => {
      const input = '2024-06-15T10:30:00+02:00';
      expect(toLocaleDateString(input)).toBe(new Date(input).toLocaleString());
    });
  });
});
