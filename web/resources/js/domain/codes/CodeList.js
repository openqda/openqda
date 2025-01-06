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
 * code's child.
 * @param from {Code}
 * @param to {Code}
 * @return {boolean}
 */
CodeList.dropAllowed = (from, to) => {
  // trivial deny-cases are the following:
  // 1. no current or no target
  // 2. dropped on itself
  // 3. droppped on immediate parent
  if (!from || !to || from.id === to.id || to.id === from.parent?.id) {
    return false;
  }

  // otherwise, we allow any drop,
  // if the target is NOT within the hierarchy
  // of the dropped item.
  // This mainly prevents parents to be dropped
  // onto one of its children, causing
  // infinite recursions.
  return !CodeList.isInChildren(from, to);
};

/**
 * Determines, whether a code is within the hierarchy
 * of the given root.
 *
 * @param root {Code} the root code from where to start searching
 * @param search {Code} the code to search for
 * @return {boolean}
 */
CodeList.isInChildren = (root, search) => {
  // we can only determine hierarchy if codes
  // exist and define an id property
  // TODO: discuss, whether we should rather throw
  if (!root?.id || !search?.id) {
    return false
  }

  // return true only on an exact match
  if (root.id === search.id) {
    return true;
  }

  // at this point we need no further investigation
  // if there are no children in our tree
  if (!root.children?.length) {
    return false;
  }

  // otherwise we need to recursively check
  // if at least one of the children (or their children)
  // matches our search
  for (const child of root.children) {
    if (CodeList.isInChildren(child, search)) {
      return true;
    }
  }

  // otherwise, no child is found to match
  return false;
};
