import { describe, it, expect, vi, afterEach } from 'vitest';
import { isInViewport } from './isInViewport.js';

const element = ({ top, right, left, bottom } = {}) => {
  return {
    getBoundingClientRect: () => ({ top, right, left, bottom }),
  };
};

describe(isInViewport.name, () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

  it('returns false, if an element has no computed rect', () => {
      const el = element()
      expect(isInViewport(el)).toBe(false);
  });
  it('returns false, if viewport is not computable', () => {
      vi.stubGlobal('window', {
          innerHeight: undefined,
          innerWidth: undefined,
      });
      vi.stubGlobal('document', {
          documentElement: {
              clientHeight: undefined,
              clientWidth: undefined
          }
      })
      const el = element({ top: 10, bottom: 10, left: 10, right: 10 })
      expect(isInViewport(el)).toBe(false);
  })
  it('detects, if a given element is within the viewport', () => {
      vi.stubGlobal('window', {
          innerHeight: 100,
          innerWidth: 100,
      });
      vi.stubGlobal('document', {
          documentElement: {
              clientHeight: undefined,
              clientWidth: undefined
          }
      })
      const el = element({ top: 10, bottom: 10, left: 10, right: 10 })
      expect(isInViewport(el)).toBe(true);
  });
    it('detects, if a given element is within the client viewport', () => {
        vi.stubGlobal('window', {
            innerHeight: undefined,
            innerWidth: undefined,
        });
        vi.stubGlobal('document', {
            documentElement: {
                clientHeight: 100,
                clientWidth: 100
            }
        })
        const el = element({ top: 10, bottom: 10, left: 10, right: 10 })
        expect(isInViewport(el)).toBe(true);
    });
    it('returns false, if a given element is below top or left', () => {
        vi.stubGlobal('window', {
            innerHeight: undefined,
            innerWidth: undefined,
        });
        vi.stubGlobal('document', {
            documentElement: {
                clientHeight: 100,
                clientWidth: 100
            }
        })

        expect(isInViewport(
            element({ top: -1, bottom: 10, left: 10, right: 10 })
        )).toBe(false);
        expect(isInViewport(
            element({ top: 10, bottom: 10, left: -1, right: 10 })
        )).toBe(false);
    });
    it('returns false, if a given element is beyond visible', () => {
        vi.stubGlobal('window', {
            innerHeight: 100,
            innerWidth: 100,
        });
        vi.stubGlobal('document', {
            documentElement: {
                clientHeight: 100,
                clientWidth: 100
            }
        })
        expect(isInViewport(
            element({ top: 10, bottom: 10, left: 10, right: 101 })
        )).toBe(false);
        expect(isInViewport(
            element({ top: 10, bottom: 101, left: 10, right: 10 })
        )).toBe(false);
    })
});
