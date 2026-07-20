import { isDefined } from '../isDefined.js';
/**
 * @module
 */

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

/**
 * A builder to create a csv string by given options and added rows.
 * @class
 */
class CSVBuilder {
  rows = [];
  header = [];

  /**
   * Creates a new CSV builder that allows to construct a new csv
   * string by given options and added rows.
   *
   * @param header {string[]} a list of strings to define the headers
   * @param separator {string} character for the separator, defaults to comma
   * @param newline {string} character(s) for linebreaks, defaults to CRLF
   * @return {CSVBuilder} the builder instance
   */
  constructor({ header, separator, newline } = {}) {
    this.header = header || this.header;
    this.separator = separator ?? RFC4180.separator;
    this.newline = newline ?? RFC4180.newline;
    return this;
  }

  /**
   * Adds a new row to the builder. The number of entries must match the number of headers.
   * Entries that are undefined or null will be treated as empty fields.
   * Fields that contain special characters will be quoted and quotes will be escaped by doubling them.
   * @param entries {string[]} the entries for the new row
   * @return {CSVBuilder}
   */
  addRow(entries) {
    if (entries.length !== this.header.length) {
      throw new Error(
        `Expected rows with ${this.header.length}, got ${entries.length}`
      );
    }
    this.rows.push(entries);
    return this;
  }

  /**
   * Builds the csv string by the given header and rows.
   * Fields that contain special characters (",;\r\n) will be quoted and quotes will be escaped by doubling them.
   * @return {string} the built csv string
   */
  build() {
    let out = toLine(this.header, this);
    this.rows.forEach((row) => {
      out += toLine(row, this);
    });
    return out;
  }
}

/**
 * The default values and helper functions for the RFC4180 standard.
 * @private
 * @type {object}
 */
const RFC4180 = {
  separator: ',',
  newline: '\r\n',
  empty: '',
  isEmpty: (x) => x === RFC4180.empty,
  needsQuotes: (s) => /[;,"\r\n]/.test(s),
  addQuotes: (s) => `"${s.replace(/"/g, '""')}"`,
};

/**
 * @private
 * @param row {string[]}
 * @param separator {string}
 * @param newline {string}
 * @return {string}
 */
const toLine = (row, { separator, newline }) => {
  let line = RFC4180.empty;

  for (let i = 0; i < row.length; i++) {
    let field = row[i];

    // add separator if not first field
    if (i > 0) {
      line = `${line}${separator}`;
    }

    // We also treat undefined and null as empty fields.
    if (!isDefined(field)) {
      field = '';
    }

    let s = String(field);

    // RFC4180 requires to quote fields that contain special characters and to escape quotes by doubling them.
    if (RFC4180.needsQuotes(s)) {
      s = RFC4180.addQuotes(s);
    }

    line = `${line}${s}`;
  }

  return line + newline;
};

/**
 * The name of the CSVBuilder class, useful for type checking and debugging.
 * @type {string}
 */
export const className = CSVBuilder.name;
