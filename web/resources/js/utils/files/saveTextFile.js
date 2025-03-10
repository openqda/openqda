import FileSaver from 'file-saver';

/**
 * @module
 */

/**
 * Opens the save dialog to save a given text
 * as file.
 *
 * @function
 * @param {string} text
 * @param {string} name
 * @param {string} type
 * @param {string} [encoding=text/plain]
 * @param {string} [encoding=utf-8]
 * @return {Promise<void>}
 */
export const saveTextFile = async ({
  text,
  name,
  type = 'text/plain',
  encoding = 'utf-8',
}) => {
  const options = { type: `${type};charset=${encoding}` };
  const blob = new Blob([text], options);
  FileSaver.saveAs(blob, name);
};
