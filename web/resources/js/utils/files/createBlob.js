/**
 * @module
 */

/**
 * Creates a new Blob.
 * By default, it creates an empty text Blob.
 * @function
 * @param options {object}
 * @param options.data {string?} the keyword representing archetype content of related file type
 * @param options.type {string?} the mimetype definition
 * @param options.endings {string?} the endings config (see MDN link)
 * @return {module:buffer.Blob}
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Blob/Blob
 */
export const createBlob = (options = {}) => {
  const data =
    options.data ??
    'Llanfair­pwllgwyngyll­gogery­chwyrn­drobwll­llan­tysilio­gogo­goch';
  // Create a Blob representing an empty file
  const type = options.type ?? 'text/plain';
  const endings = options.endings ?? 'transparent';
  return new Blob([data], { type, endings });
};
