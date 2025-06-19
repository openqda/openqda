import { describe, expect } from 'vitest';
import { rgbaToValues } from './rgbaToValues';

const randomColor = (max) => Math.floor(Math.random() * max);

describe(rgbaToValues.name, () => {
  it('throws if given value is not a valid rgba string', () => {
    [undefined, null, '', '#ffffff', '0, 1, 2, 2'].forEach((value) => {
      expect(() => rgbaToValues(value)).toThrowError(`Invalid rgba ${value}`);
    });
  });

  it('returns the several rgba values', () => {
    const max = 255;
    for (let i = 0; i < 100; i++) {
      const r = randomColor(max);
      const g = randomColor(max);
      const b = randomColor(max);
      const a = randomColor(1);
      const col = rgbaToValues(`rgba(${r}, ${g}, ${b}, ${a})`);
      expect(col[0] == r).toBe(true);
      expect(col[1] == g).toBe(true);
      expect(col[2] == b).toBe(true);
      expect(col[3] == a).toBe(true);
    }
  });
});
