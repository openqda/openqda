import { describe, expect, it, test } from 'vitest';
import { createCSVBuilder, className } from './createCSVBuilder.js';

/**
 * RFC 4180 – Common Format and MIME Type for Comma-Separated Values (CSV) Files
 * https://www.rfc-editor.org/rfc/rfc4180
 *
 * Key rules tested below:
 * 1. Each record is located on a separate line, delimited by a line break (CRLF).
 * 2. The last record in the file may or may not have an ending line break.
 * 3. There may be an optional header line appearing as the first line.
 * 4. Within the header and each record, there may be one or more fields, separated by commas.
 * 5. Each field may or may not be enclosed in double quotes.
 * 6. Fields containing line breaks (CRLF), double quotes, and commas should be enclosed in double quotes.
 * 7. A double-quote appearing inside a field must be escaped by preceding it with another double quote.
 */

// Helper: RFC 4180 compliant builder (comma separator, CRLF newline)
const rfc4180Builder = (header) =>
  createCSVBuilder({ header, separator: ',', newline: '\r\n' });

test('it creates a new CSVBuilder', () => {
  const builder = createCSVBuilder();
  expect(builder.constructor.name).toBe(className);
});

// ─── RFC 4180 §2 Rule 1 & 2: Records separated by line breaks ───────────────

describe('RFC 4180 §2 Rule 1 & 2 – record line breaks', () => {
  it('separates each record with CRLF when configured', () => {
    const builder = rfc4180Builder(['a', 'b']);
    builder.addRow(['1', '2']);
    builder.addRow(['3', '4']);
    const csv = builder.build();
    const lines = csv.split('\r\n');
    // header + 2 data rows + trailing empty string from final CRLF
    expect(lines).toEqual(['a,b', '1,2', '3,4', '']);
  });

  it('ends the last record with a line break', () => {
    const builder = rfc4180Builder(['x']);
    builder.addRow(['1']);
    const csv = builder.build();
    expect(csv.endsWith('\r\n')).toBe(true);
  });

  it('supports LF-only newlines via configuration', () => {
    const builder = createCSVBuilder({
      header: ['a'],
      separator: ',',
      newline: '\n',
    });
    builder.addRow(['1']);
    expect(builder.build()).toBe('a\n1\n');
  });
});

// ─── RFC 4180 §2 Rule 3: Optional header line ───────────────────────────────

describe('RFC 4180 §2 Rule 3 – optional header line', () => {
  it('outputs the header as the first line', () => {
    const builder = rfc4180Builder(['name', 'age', 'city']);
    builder.addRow(['Alice', '30', 'Berlin']);
    const csv = builder.build();
    const firstLine = csv.split('\r\n')[0];
    expect(firstLine).toBe('name,age,city');
  });

  it('produces output with only a header when no rows are added', () => {
    const builder = rfc4180Builder(['col1', 'col2']);
    expect(builder.build()).toBe('col1,col2\r\n');
  });
});

// ─── RFC 4180 §2 Rule 4: Comma-separated fields ─────────────────────────────

describe('RFC 4180 §2 Rule 4 – fields separated by commas', () => {
  it('separates fields with the comma separator', () => {
    const builder = rfc4180Builder(['a', 'b', 'c']);
    builder.addRow(['1', '2', '3']);
    const csv = builder.build();
    expect(csv).toBe('a,b,c\r\n1,2,3\r\n');
  });

  it('uses a custom separator when configured', () => {
    const builder = createCSVBuilder({
      header: ['a', 'b'],
      separator: ';',
      newline: '\r\n',
    });
    builder.addRow(['1', '2']);
    expect(builder.build()).toBe('a;b\r\n1;2\r\n');
  });

  it('handles a single-field record (no separators needed)', () => {
    const builder = rfc4180Builder(['only']);
    builder.addRow(['value']);
    expect(builder.build()).toBe('only\r\nvalue\r\n');
  });
});

// ─── RFC 4180 §2 Rule 5: Fields may be enclosed in double quotes ─────────────

describe('RFC 4180 §2 Rule 5 – plain fields are not quoted', () => {
  it('does not quote simple alphanumeric fields', () => {
    const builder = rfc4180Builder(['name']);
    builder.addRow(['Alice']);
    const csv = builder.build();
    expect(csv).toBe('name\r\nAlice\r\n');
    expect(csv).not.toContain('"');
  });

  it('does not quote fields with spaces only', () => {
    const builder = rfc4180Builder(['a']);
    builder.addRow(['hello world']);
    const csv = builder.build();
    expect(csv).toBe('a\r\nhello world\r\n');
  });
});

// ─── RFC 4180 §2 Rule 6: Fields with special chars must be quoted ────────────

describe('RFC 4180 §2 Rule 6 – fields with special characters are enclosed in double quotes', () => {
  it('quotes fields containing the separator character (comma)', () => {
    const builder = rfc4180Builder(['value']);
    builder.addRow(['one,two']);
    const csv = builder.build();
    expect(csv).toBe('value\r\n"one,two"\r\n');
  });

  it('quotes fields containing a semicolon when semicolon is the separator', () => {
    const builder = createCSVBuilder({
      header: ['value'],
      separator: ';',
      newline: '\r\n',
    });
    builder.addRow(['one;two']);
    const csv = builder.build();
    expect(csv).toBe('value\r\n"one;two"\r\n');
  });

  it('quotes fields containing double quotes', () => {
    const builder = rfc4180Builder(['value']);
    builder.addRow(['say "hello"']);
    const csv = builder.build();
    expect(csv).toBe('value\r\n"say ""hello"""\r\n');
  });

  it('quotes fields containing LF line breaks', () => {
    const builder = rfc4180Builder(['value']);
    builder.addRow(['line1\nline2']);
    const csv = builder.build();
    expect(csv).toBe('value\r\n"line1\nline2"\r\n');
  });

  it('quotes fields containing CR line breaks', () => {
    const builder = rfc4180Builder(['value']);
    builder.addRow(['line1\rline2']);
    const csv = builder.build();
    expect(csv).toBe('value\r\n"line1\rline2"\r\n');
  });

  it('quotes fields containing CRLF line breaks', () => {
    const builder = rfc4180Builder(['value']);
    builder.addRow(['line1\r\nline2']);
    const csv = builder.build();
    expect(csv).toBe('value\r\n"line1\r\nline2"\r\n');
  });

  it('quotes header fields that contain special characters', () => {
    const builder = rfc4180Builder(['normal', 'has,comma', 'has"quote']);
    builder.addRow(['a', 'b', 'c']);
    const csv = builder.build();
    const headerLine = csv.split('\r\n')[0];
    expect(headerLine).toBe('normal,"has,comma","has""quote"');
  });

  it('quotes fields containing multiple special characters at once', () => {
    const builder = rfc4180Builder(['value']);
    builder.addRow(['He said, "hi"\nand left']);
    const csv = builder.build();
    expect(csv).toBe('value\r\n"He said, ""hi""\nand left"\r\n');
  });
});

// ─── RFC 4180 §2 Rule 7: Escaping double quotes ─────────────────────────────

describe('RFC 4180 §2 Rule 7 – double quotes escaped by doubling', () => {
  it('escapes a single double quote inside a field', () => {
    const builder = rfc4180Builder(['val']);
    builder.addRow(['"']);
    const csv = builder.build();
    // The field `"` must become `""""` (quote-open, escaped quote, quote-close)
    expect(csv).toBe('val\r\n""""\r\n');
  });

  it('escapes multiple double quotes inside a field', () => {
    const builder = rfc4180Builder(['val']);
    builder.addRow(['a""b']);
    const csv = builder.build();
    expect(csv).toBe('val\r\n"a""""b"\r\n');
  });

  it('escapes a field that is entirely double quotes', () => {
    const builder = rfc4180Builder(['val']);
    builder.addRow(['""']);
    const csv = builder.build();
    expect(csv).toBe('val\r\n""""""\r\n');
  });
});

// ─── Row validation ──────────────────────────────────────────────────────────

describe('row length validation', () => {
  it('throws when row has fewer fields than the header', () => {
    const builder = rfc4180Builder(['a', 'b', 'c']);
    expect(() => builder.addRow(['1'])).toThrow('Expected rows with 3, got 1');
  });

  it('throws when row has more fields than the header', () => {
    const builder = rfc4180Builder(['a']);
    expect(() => builder.addRow(['1', '2'])).toThrow(
      'Expected rows with 1, got 2'
    );
  });

  it('throws when row is empty', () => {
    const builder = rfc4180Builder(['a']);
    expect(() => builder.addRow([])).toThrow('Expected rows with 1, got 0');
  });

  it.each([
    { input: [], expected: 0 },
    { input: ['x'], expected: 1 },
    { input: ['x', 'y', 'z'], expected: 3 },
  ])(
    'throws with correct count for $expected extra/missing fields',
    ({ input, expected }) => {
      const builder = rfc4180Builder(['a', 'b']);
      expect(() => builder.addRow(input)).toThrow(
        `Expected rows with 2, got ${expected}`
      );
    }
  );
});

// ─── Edge cases & type coercion ──────────────────────────────────────────────

describe('edge cases and type coercion', () => {
  it('converts null fields to empty strings', () => {
    const builder = rfc4180Builder(['a', 'b']);
    builder.addRow([null, 'ok']);
    const csv = builder.build();
    expect(csv).toBe('a,b\r\n,ok\r\n');
  });

  it('converts undefined fields to empty strings', () => {
    const builder = rfc4180Builder(['a', 'b']);
    builder.addRow([undefined, 'ok']);
    const csv = builder.build();
    expect(csv).toBe('a,b\r\n,ok\r\n');
  });

  it('converts numeric fields to strings', () => {
    const builder = rfc4180Builder(['int', 'float', 'neg']);
    builder.addRow([42, 3.14, -1]);
    const csv = builder.build();
    expect(csv).toBe('int,float,neg\r\n42,3.14,-1\r\n');
  });

  it('converts boolean fields to strings', () => {
    const builder = rfc4180Builder(['a', 'b']);
    builder.addRow([true, false]);
    const csv = builder.build();
    expect(csv).toBe('a,b\r\ntrue,false\r\n');
  });

  it('handles empty string fields', () => {
    const builder = rfc4180Builder(['a', 'b', 'c']);
    builder.addRow(['', 'middle', '']);
    const csv = builder.build();
    expect(csv).toBe('a,b,c\r\n,middle,\r\n');
  });

  it('supports chaining addRow calls', () => {
    const csv = rfc4180Builder(['a'])
      .addRow(['1'])
      .addRow(['2'])
      .addRow(['3'])
      .build();
    expect(csv).toBe('a\r\n1\r\n2\r\n3\r\n');
  });

  it('handles a large number of columns', () => {
    const headers = Array.from({ length: 100 }, (_, i) => `col${i}`);
    const values = Array.from({ length: 100 }, (_, i) => String(i));
    const builder = rfc4180Builder(headers);
    builder.addRow(values);
    const csv = builder.build();
    const lines = csv.split('\r\n');
    expect(lines[0].split(',').length).toBe(100);
    expect(lines[1].split(',').length).toBe(100);
  });
});

// ─── Full RFC 4180 integration test ──────────────────────────────────────────

describe('RFC 4180 integration', () => {
  it('produces a fully RFC 4180 compliant CSV', () => {
    const builder = rfc4180Builder(['name', 'description', 'value', 'notes']);
    builder.addRow(['Alice', 'A "great" person', '1,000', 'line1\r\nline2']);
    builder.addRow(['Bob', 'Simple', '42', '']);
    builder.addRow([null, undefined, 0, false]);

    const csv = builder.build();

    // Verify structure
    const lines = csv.split('\r\n');

    // rebuild first line as it contains a CRLF in the notes field that would split it into 2 lines
    lines[1] = lines[1] + '\r\n' + lines[2];
    lines.splice(2, 1); // remove the now redundant line

    // 1 header + 3 data rows + 1 trailing empty from final CRLF = 6
    expect(lines.length, lines).toBe(5);
    expect(lines[4]).toBe('');

    // Verify header
    expect(lines[0]).toBe('name,description,value,notes');

    // Verify quoting and escaping in first data row
    expect(lines[1]).toBe(
      'Alice,"A ""great"" person","1,000","line1\r\nline2"'
    );

    // Verify simple row
    expect(lines[2]).toBe('Bob,Simple,42,');

    // Verify null/undefined/falsy coercion
    expect(lines[3]).toBe(',,0,false');
  });
});
