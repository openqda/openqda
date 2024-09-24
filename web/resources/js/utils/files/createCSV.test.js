import { expect, test } from 'vitest';
import { createCSV, className } from './createCSV.js';

test('it creates a new CSVBuilder', () => {
  const builder = createCSV();
  expect(builder.constructor.name).toBe(className);
});

test('it generates a csv by given rows for given header', () => {
  const builder = createCSV({
    header: ['foo', 'bar'],
  });
  builder.addRow([1, 2]);
  builder.addRow([3, 4]);
  const expected = `foo;bar
1;2
3;4
`;
  expect(builder.build()).toBe(expected);
});

it('throws on incompatible row length', () => {
  const builder = createCSV({
    header: ['foo', 'bar'],
  });

  [[], [1], [1, 2, 3]].forEach((input) => {
    expect(() => builder.addRow(input)).toThrow(
      `Expected rows with 2, got ${input.length}`
    );
  });
});
