/**
 * @module
 */

import { whitespace } from '../regex.js';

/**
 * Creates a new CSV builder that allows to construct a new csv
 * string by given options and added rows.
 *
 * @function
 * @param {object} options
 * @param {string[]} options.header a list of strings to define the headers
 * @param {string} options.separator character for the separator
 * @param {string} options.newline character for linebreaks
 * @return {CSVBuilder}
 */
export const createCSVBuilder = (options) => new CSVBuilder(options);

class CSVBuilder {
  rows = [];
  header = [];
  separator = ';';
  newline = '\n';

  constructor({ header, separator, newline } = {}) {
    this.header = header || this.header;
    this.separator = separator || this.separator;
    this.newline = newline || this.newline;
    return this;
  }

  addRow(entries) {
    if (entries.length !== this.header.length) {
      throw new Error(
        `Expected rows with ${this.header.length}, got ${entries.length}`
      );
    }
    this.rows.push(entries);
    return this;
  }

  build() {
    let out = toLine(this.header, this);
    this.rows.forEach((row) => {
      out += toLine(row, this);
    });
    return out;
  }
}

const toLine = (row, { separator, newline }) => {
  let line = '';
  for (let field of row) {
    if (line !== '') {
      line += separator;
    }
    if (field === null || field === undefined) {
      field = '';
    }
    let s = String(field).replace(whitespace, ' ');
    if (/[,"\r\n]/.test(s)) {
      s = '"' + s.replace(/"/g, '""') + '"';
    }
    line += s;
  }
  return line + newline;
};

export const className = CSVBuilder.name;
