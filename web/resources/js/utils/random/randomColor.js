import { toHex } from '../color/toHex.js';

/**
 * Returns a random color with given properties.
 * @param type {'rgba'|'hex'|'rgb'} the type of the color
 * @param opacity {number} a value between 0 and 1, use -1 to omit opacity on hex colors
 * @return {`rgba(${number}, ${number}, ${number}, 1)`|string}
 */
export const randomColor = ({ type = 'rgba', opacity = 1 } = {}) => {
  const r = color();
  const g = color();
  const b = color();

  if (type === 'hex') {
    const o = opacity > -1 ? toHex(Math.floor(opacity * 255)) : '';
    return `#${toHex(r)}${toHex(g)}${toHex(b)}${o}`;
  }

  if (type === 'rgb') {
    return `rgb(${r}, ${g}, ${b})`;
  }

  return `rgba(${r}, ${g}, ${b}, ${opacity})`;
};

/** @private */
const color = () => Math.floor(Math.random() * 128 + 128);
