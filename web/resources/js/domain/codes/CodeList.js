/**
 * @typedef Code
 * @property id {string}
 * @property name {string}
 * @property color {string}
 * @property description {description}
 * @property children {Code[]=}
 * @property parent {Code=}
 */

/**
 * Domain context, representing code-list
 * relations
 * @type {object}
 */
export const CodeList = {};

/**
 * Determines, whether a code can become another
 * code's child
 * @param from {Code}
 * @param to {Code}
 * @return {boolean}
 */
CodeList.dropAllowed = (from, to) => {
  // trivial cases
  if (!from || !to || from.id === to.id || to.id === from.parent.id) {
    return false;
  }

  return !CodeList.isInChildren(from, to);
};

/**
 * Determines, whether a code is within the hierarchy
 * of the given root
 * @param root {Code} the root code from where to start searching
 * @param search {Code} the code to search for
 * @return {boolean}
 */
CodeList.isInChildren = (root, search) => {
  if (root.id === search.id) {
    return true;
  }
  if (!root.children?.length) {
    return false;
  }

  for (const child of root.children) {
    if (CodeList.isInChildren(child, search)) {
      return true;
    }
  }
  return false;
};
