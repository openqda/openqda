/**
 * @module
 */

/**
 * Creates a sort callback for [Array.prototype.sort](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/sort)
 * that allows to sort objects by the values found under the given property name.
 *
 * @function
 * @param prop {string} the name of the property for which to sort
 * @param descending {boolean=} optional flag to sort descending if true
 * @return {function(object, object): number}
 */
export const createByPropertySorter =
  (prop, descending = false) =>
  (a, b) => {
    const propA = a?.[prop] ?? '';
    const propB = b?.[prop] ?? '';
    return descending ? propB.localeCompare(propA) : propA.localeCompare(propB);
  };
