import {rgbaToValues} from './rgbaToValues.js'

export const toHex = (c) => {
    const hex = c.toString(16);
    return hex.length === 1 ? '0' + hex : hex;
};

export const rgbToHex = (rgb = '') => {
    if (rgb.startsWith('#')) return rgb
    const [r, g, b] = rgbaToValues(rgb);
    return '#' + toHex(r) + toHex(g) + toHex(b);
}
