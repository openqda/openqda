import { trunc } from './trunc.ts';
import { describe, expect } from 'vitest';

describe(trunc.name, () => {
  it('truncates string by given length', () => {
    const str = 'foo bar baz moo';
    expect(trunc(str, 7)).toEqual('foo ...');
  });
  it("returns the string if it's less than max - 3", () => {
    const str = 'foo bar baz moo';
    expect(trunc(str, 18)).toEqual(str);
  });
});
