import axios from 'axios';

/**
 * @typedef {object} BackendRequestOptions
 * @property url {string} the url, use {query} to add url query parameters
 * @property type {string} the request type (get, post etc.)
 * @property body {object?} a dictionary object for body parameters
 * @property query {object?} a dictionary object of query parameters
 * @property headers {object?} a dictionary object of additional request headers
 */

/**
 * Wrapper for standardized requests to the backend that do not throw an error
 * but instead stores the response / error as member variables.
 * @class
 */
class BackendRequest {
  /**
   * @constructor
   * @param options {BackendRequestOptions}
   */
  constructor(options) {
    const { url, type, body, query, headers, ...rest } = options;
    this.url = url;
    this.type = type.toLowerCase();
    this.body = body;
    this.query = query;
    this.response = null;
    this.error = null;
    this.headers = headers;
    this.extraOptions = rest;
  }

  /**
   * Send the request to the server and store
   * the response / error as member variables.
   *
   * @return {Promise<BackendRequest>}
   */
  async send() {
    try {
      const fn = axios[this.type];
      const args = [this.url];
      if (this.query) args.push({ params: this.query });
      if (this.body) args.push(this.body);

      const extraOptions = { ...this.extraOptions };

      if (this.headers) {
        extraOptions.headers = this.headers;
      }

      args.push(extraOptions);
      this.response = await fn.apply(axios, args);
    } catch (error) {
      this.response = error.response ?? null;
      this.error = error;
    }
    return this;
  }
}

const globalHooks = new Set();

/**
 *
 * @param fn
 */
export const registerGlobalRequestHook = (fn) => {
  globalHooks.add(fn);
};

/**
 * Sends an HTTP request to the backend but does not throw on error
 * but instead returns an object that contains a response and error property.
 *
 * @param options {BackendRequestOptions}
 * @return {Promise<BackendRequest>}
 */
export const request = async (options) => {
  const req = await new BackendRequest(options).send();
  globalHooks.forEach((fn) => fn(req));
  return req;
};
