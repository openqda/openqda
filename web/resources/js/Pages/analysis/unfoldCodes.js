/**
 * Flattens a given list of nested codes by shallow-copying items to a new array.
 * Does not alter the original array.
 * @param codeList {object[]}
 * @param destination {object[]=} the new list of codes
 * @param cache {Set=} prevents duplicates
 * @return {object[]}
 */
export const unfoldCodes = (
  codeList,
  { destination = [], cache = new Set() } = {}
) => {
  if (!codeList || !codeList.length) {
    return destination;
  }

  for (const code of codeList) {
    if (!cache.has(code.id)) {
      destination.push(code);
      cache.add(code.id);
    }
    // we will always unfold children, independent of cache status
    // to make sure we 100% capture every subcode
    if (code.children && code.children.length > 0) {
      unfoldCodes(code.children, { destination, cache });
    }
  }

  return destination;
};
