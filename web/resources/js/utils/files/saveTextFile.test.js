import { afterEach, describe, expect, vi } from 'vitest';
import { saveTextFile } from './saveTextFile.js';
import FileSaver from 'file-saver';

describe(saveTextFile.name, () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  test('it saves a file by given text and name', () => {
    const spy = vi.spyOn(FileSaver, 'saveAs');
    saveTextFile({
      name: 'foo.bar',
      text: 'moo',
    });
    expect(spy).toHaveBeenCalledTimes(1);
  });
});
