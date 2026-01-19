import { expect, test } from 'vitest';
import { createCSVBuilder, className } from './createCSVBuilder.js';

test('it creates a new CSVBuilder', () => {
  const builder = createCSVBuilder();
  expect(builder.constructor.name).toBe(className);
});

test('it generates a csv by given rows for given header', () => {
  const builder = createCSVBuilder({
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
  const builder = createCSVBuilder({
    header: ['foo', 'bar'],
  });

  [[], [1], [1, 2, 3]].forEach((input) => {
    expect(() => builder.addRow(input)).toThrow(
      `Expected rows with 2, got ${input.length}`
    );
  });
});

it('escapes fields with special characters', () => {
  const builder = createCSVBuilder({
    header: [
      'normal',
      'with,comma',
      'with"quote',
      'with\nnewline',
      'with`backticks`',
    ],
  });
  builder.addRow([
    'simple',
    'value,with,commas',
    'value "with" quotes',
    'line1\nline2',
    `value with \`backticks\``,
  ]);
  const expected = `normal;"with,comma";"with""quote";with newline;with\`backticks\`\nsimple;"value,with,commas";"value ""with"" quotes";line1 line2;value with \`backticks\`\n`;
  const actual = builder.build();
  expect(actual).toBe(expected);
});
