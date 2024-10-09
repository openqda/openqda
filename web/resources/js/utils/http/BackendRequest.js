/**
 * @typedef {object} BackendRequestOptions
 * @property url {string} the url, use {query} to add url query parameters
 * @property type {string} the request type (get, post etc.)
 * @property body {object?} a dictionary object for body parameters
 * @property query {object?} a dictionary object of query parameters
 * @property headers {object?} a dictionary object of additional request headers
 */

/**
 * @class
 */
class BackendRequest {
  /**
   * @constructor
   * @param options {BackendRequestOptions}
   */
  constructor(options) {
    const { url, type, body, query, headers } = options;
    this.url = url;
    this.type = type.toLowerCase();
    this.body = body;
    this.query = query;
    this.response = null;
    this.error = null;
    this.headers = headers;
  }

  async send() {
    try {
      const fn = axios[this.type];
      const args = [this.url];
      if (this.query) args.push({ params: this.query });
      if (this.body) args.push(this.body);
      if (this.headers) args.push({ headers: this.headers });
      this.response = await fn.apply(axios, args);
    } catch (error) {
      this.response = error.response ?? null;
      this.error = error;
    }
    return this;
  }
}

/**
 * Send a HTTP request to the backend.
 * @param options {BackendRequestOptions}
 * @return {Promise<BackendRequest>}
 */
export const request = (options) => new BackendRequest(options).send();
