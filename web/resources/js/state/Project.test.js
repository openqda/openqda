import { afterEach, describe, expect, vi } from 'vitest';
import { Project } from './Project.js';

describe('Project', () => {
  describe(Project.isValidId.name, () => {
    it('returns true for valid ids', () => {
      [
        1,
        2,
        3,
        100,
        10000,
        Number.MAX_SAFE_INTEGER,
        '1',
        '100',
        '9999999',
      ].forEach((id) => {
        expect(Project.isValidId(id)).toBe(true);
      });
    });
    it('returns false for invalid ids', () => {
      [0, false, undefined, null, '', 'undefined', 'null'].forEach((id) => {
        expect(Project.isValidId(id)).toBe(false);
      });
    });
  });
  describe(Project.getId.name, () => {
    afterEach(() => {
      vi.unstubAllGlobals();
    });

    it('returns the projectId from URL path', () => {
      vi.stubGlobal('location', {
        pathname: '/projects/42',
      });
      expect(Project.getId()).toBe('42');
    });
    it('returns the projectId from session storage', () => {
      vi.stubGlobal('sessionStorage', {
        getItem: () => '123',
      });
      expect(Project.getId()).toBe('123');
    });
    it('returns the projectId from URL query', () => {
      vi.stubGlobal('location', {
        pathname: '/',
        search: '?projectId=777',
      });
      expect(Project.getId()).toBe('777');
    });
    it('throws if there is no project id', () => {
      expect(() => Project.getId()).toThrow(
        'Could not retrieve a valid project id.'
      );
    });
    it('throws if there is no valid project id', () => {
      vi.stubGlobal('location', {
        pathname: '/projects/0',
        search: '?projectId=null',
      });
      vi.stubGlobal('sessionStorage', {
        getItem: () => {},
      });
      expect(() => Project.getId()).toThrow(
        'Could not retrieve a valid project id.'
      );
    });
  });
});
