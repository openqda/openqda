export const trunc = (str, max = 20) => {
  if (!str || str.length <= max - 3) return str;
  return `${str.substring(0, max - 3)}...`;
};
