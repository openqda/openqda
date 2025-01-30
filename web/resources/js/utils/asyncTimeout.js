/**
 * @module
 */

/**
 * Provides a timeout within async/await structures.
 * @function
 * @param ms {number}
 * @return {Promise<void>}
 * @example
 * await someFunction()
 * await asyncTimeout(500) // sleep for 500ms
 * await someOtherFunction()
 */
export const asyncTimeout = (ms) =>
  new Promise((resolve) => setTimeout(resolve, ms));
