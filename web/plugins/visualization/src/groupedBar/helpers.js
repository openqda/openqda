/**
 * Helper function to insert line breaks in labels
 * @param label
 * @param maxLength
 * @param maxLines
 * @return {string}
 */
export const insertLineBreaks = (label, maxLength, maxLines) => {
  // Check for null or undefined and provide defaults
  if (typeof label !== 'string' || !label) return '';
  if (typeof maxLength !== 'number' || maxLength <= 0) maxLength = 20;
  if (typeof maxLines !== 'number' || maxLines <= 0) maxLines = 3;

  let result = '';
  let lineCount = 0;
  for (
    let i = 0;
    i < label.length && lineCount < maxLines;
    i += maxLength, lineCount++
  ) {
    if (i > 0) result += '<br>';
    // If this is the last allowed line and there is more text, add ellipsis
    if (lineCount === maxLines - 1 && i + maxLength < label.length) {
      result += label.substring(i, i + maxLength - 1) + '…';
      break;
    } else {
      result += label.substring(i, i + maxLength);
    }
  }
  return result;
};

/**
 * Get sorter function based on type
 * @param type
 * @param direction
 * @param isMulti
 * @return {function}
 */
export const getSorter = (type, direction, isMulti) => {
  const descending = direction === 'descending';
  if (type === 'name') {
    return descending ? sortByNameAscending : sortByNameDescending;
  }
  if (type === 'count') {
    if (isMulti) return sortByCountMulti(descending);
    return descending ? sortByCountAscending : sortByCountDescending;
  }
  throw new Error(`Unknown sort type: ${type}`);
};

const sortByNameAscending = (a, b) => a.name.localeCompare(b.name);
const sortByNameDescending = (a, b) => b.name.localeCompare(a.name);
const sortByCountAscending = (a, b) => a.counts - b.counts;
const sortByCountDescending = (a, b) => b.counts - a.counts;
const toSum = (sum, val) => sum + val;
const sortByCountMulti = (descending) => {
  return (a, b) => {
    const countsA = Object.values(a.counts).reduce(toSum, 0);
    const countsB = Object.values(b.counts).reduce(toSum, 0);
    return descending ? countsA - countsB : countsB - countsA;
  };
};
