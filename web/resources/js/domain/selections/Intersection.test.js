import { describe, expect } from 'vitest';
import { Intersections } from './Intersections.js';

const run = ({ selections, expected, debug }) => {
  const actual = Intersections.from(selections, debug);
  expect(actual).toEqual(expected);
};
const set = (...args) => args;

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
});
