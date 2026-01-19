/**
 * Converts a value (string, unix timestamp) to a localized date string.
 * If the value is invalid, an empty string is returned.

 * @param value {*}
 * @param date {boolean} whether to include the date part
 * @param time {boolean} whether to include the time part
 * @return {string} the localized date string
 */
export const toLocaleDateString = (
  value,
  { date = true, time = true } = {}
) => {
  if (!value || value < 0) {
    return '';
  }

  const d = new Date(value);

  if (time && !date) {
    return d.toLocaleTimeString();
  }
  if (date && !time) {
    return d.toLocaleDateString();
  }
  return d.toLocaleString();
};
