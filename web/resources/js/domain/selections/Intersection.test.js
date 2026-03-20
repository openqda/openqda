import { describe, expect } from 'vitest';
import { Intersections } from './Intersections.js';
import { performance } from 'node:perf_hooks';

const run = ({ selections, expected, debug }) => {
  const actual = Intersections.from(selections, debug);
  expect(actual).toEqual(expected);
};
const set = (...args) => args;
function getRandomIntInclusive(min, max) {
  const minCeiled = Math.ceil(min);
  const maxFloored = Math.floor(max);
  return Math.floor(Math.random() * (maxFloored - minCeiled + 1) + minCeiled); // The maximum is inclusive and the minimum is inclusive
}

describe('Intersection', () => {
  describe('basic', () => {
    it('should not segment selections that will not overlap', () => {
      run({
        selections: [
          // order should be irrelevant
          { x: 25, y: 30, c: 1 },
          { x: 5, y: 10, c: 1 },
          { x: 11, y: 20, c: 2 },
          { x: 1, y: 4, c: 2 },
        ],
        expected: [
          { x: 1, y: 4, c: set(2) },
          { x: 5, y: 10, c: set(1) },
          { x: 11, y: 20, c: set(2) },
          { x: 25, y: 30, c: set(1) },
        ],
      });
    });
    it('should cover overlap from end', () => {
      run({
        selections: [
          { x: 5, y: 10, c: 1 },
          { x: 11, y: 20, c: 2 },
          { x: 18, y: 30, c: 3 },
          { x: 35, y: 40, c: 4 },
        ],
        expected: [
          { x: 5, y: 10, c: set(1) },
          { x: 11, y: 18, c: set(2) },
          { x: 18, y: 20, c: set(2, 3) },
          { x: 20, y: 30, c: set(3) },
          { x: 35, y: 40, c: set(4) },
        ],
      });
    });
    it('should cover overlap from start', () => {
      run({
        selections: [
          { x: 5, y: 10, c: 1 },
          { x: 11, y: 20, c: 2 },
          { x: 25, y: 30, c: 3 },
          { x: 35, y: 40, c: 4 },
          { x: 1, y: 6, c: 5 },
        ],
        expected: [
          { x: 1, y: 5, c: set(5) },
          { x: 5, y: 6, c: set(5, 1) },
          { x: 6, y: 10, c: set(1) },
          { x: 11, y: 20, c: set(2) },
          { x: 25, y: 30, c: set(3) },
          { x: 35, y: 40, c: set(4) },
        ],
      });
    });
    it('should cover inclusions overlap', () => {
      run({
        selections: [
          { x: 5, y: 10, c: 1 },
          { x: 15, y: 30, c: 2 },
          { x: 20, y: 25, c: 3 },
          { x: 35, y: 40, c: 4 },
        ],
        expected: [
          { x: 5, y: 10, c: set(1) },
          { x: 15, y: 20, c: set(2) },
          { x: 20, y: 25, c: set(2, 3) },
          { x: 25, y: 30, c: set(2) },
          { x: 35, y: 40, c: set(4) },
        ],
      });
    });
    it('should cover overlap, starting/ending at the same index', () => {
      run({
        selections: [
          { x: 5, y: 10, c: 1 },
          { x: 5, y: 40, c: 2 },
          { x: 20, y: 25, c: 3 },
          { x: 30, y: 40, c: 4 },
        ],
        expected: [
          { x: 5, y: 10, c: set(1, 2) },
          { x: 10, y: 20, c: set(2) },
          { x: 20, y: 25, c: set(2, 3) },
          { x: 25, y: 30, c: set(2) },
          { x: 30, y: 40, c: set(2, 4) },
        ],
      });
    });
    it('should cover multiple mixed overlaps', () => {
      run({
        selections: [
          { x: 1, y: 35, c: 1 },
          { x: 5, y: 30, c: 2 },
          { x: 10, y: 20, c: 3 },
          { x: 15, y: 30, c: 4 },
          { x: 25, y: 40, c: 5 },
        ],
        expected: [
          { x: 1, y: 5, c: set(1) },
          { x: 5, y: 10, c: set(1, 2) },
          { x: 10, y: 15, c: set(1, 2, 3) },
          { x: 15, y: 20, c: set(1, 2, 3, 4) },
          { x: 20, y: 25, c: set(1, 2, 4) },
          { x: 25, y: 30, c: set(1, 2, 4, 5) },
          { x: 30, y: 35, c: set(1, 5) },
          { x: 35, y: 40, c: set(5) },
        ],
      });
    });
  });
  describe('Performance', () => {
    const inflate = (length = 100, max = 1000) => {
      const selections = [];
      selections.length = length;
      for (let i = 0; i < length; i++) {
        const x = getRandomIntInclusive(1, max - max / 10);
        const y = x + getRandomIntInclusive(1, max / 10);
        const c = getRandomIntInclusive(1, length / 2);
        selections[i] = { x, y, c };
      }
      return selections;
    };
    it('is fairly time performant', async () => {
      // Baseline: 100 entries
      let selections = inflate(100, 100);
      let start = performance.now();
      Intersections.from(selections);
      let end = performance.now();
      const time100 = end - start;

      // 10x more entries: 1000 entries
      selections = inflate(1000, 100);
      start = performance.now();
      Intersections.from(selections);
      end = performance.now();
      const time1000 = end - start;

      // 100x more entries: 10000 entries
      selections = inflate(10000, 100);
      start = performance.now();
      Intersections.from(selections);
      end = performance.now();
      const time10000 = end - start;

      // Calculate scaling factors
      const scalingFactor10x = time1000 / time100;
      const scalingFactor100x = time10000 / time100;

      // For an O(n log n) algorithm with overhead:
      // Theoretical: 10x input → ~13.3x time (10 × log₂(1000)/log₂(100) = 10 × 1.33)
      // Theoretical: 100x input → ~200x time (100 × log₂(10000)/log₂(100) = 100 × 2.0)
      //
      // Empirical measurements show ~278-300x for 100x input, suggesting O(n log n)
      // with significant constant factors or additional overhead.
      //
      // Allow generous margins while still catching truly inefficient implementations:
      // - 10x input: allow up to 25x time (vs theoretical ~13x)
      // - 100x input: allow up to 400x time (vs theoretical ~200x, observed ~278-300x)
      //
      // This will catch O(n²) implementations:
      // - O(n²): 10x input → 100x time, 100x input → 10,000x time (fails dramatically)

      expect(scalingFactor10x).toBeLessThan(25);
      expect(scalingFactor100x).toBeLessThan(400);

      // Also verify all operations actually completed (no crashes/hangs)
      expect(time100).toBeGreaterThan(0);
      expect(time1000).toBeGreaterThan(0);
      expect(time10000).toBeGreaterThan(0);
    });
  });
});
