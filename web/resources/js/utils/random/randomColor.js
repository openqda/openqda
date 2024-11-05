export const randomColor = ({ type = 'rgba', opacity = 1 } = {}) => {
  const r = color();
  const g = color();
  const b = color();

  if (type === 'hex') {
    return '#' + toHex(r) + toHex(g) + toHex(b);
  }

  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
};

const color = () => Math.floor(Math.random() * 128 + 128);
const toHex = (c) => {
  const hex = c.toString(16);
  return hex.length === 1 ? '0' + hex : hex;
};
