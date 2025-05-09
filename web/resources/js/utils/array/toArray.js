export const toArray = (arr) => {
  if (typeof arr === 'undefined') return [];
  return Array.isArray(arr) ? arr : [arr];
};
