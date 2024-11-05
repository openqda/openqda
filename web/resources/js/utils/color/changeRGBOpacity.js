export const changeRGBOpacity = (rgba, opacity) => {
    const rgbaValues = rgba.match(/[\d.]+/g);
    const resolved = opacity ?? rgbaValues[3] ?? 1;
    if (rgbaValues && rgbaValues.length >= 3) {
        return `rgba(${rgbaValues[0]}, ${rgbaValues[1]}, ${rgbaValues[2]}, ${resolved})`;
    }
    return rgba;
};
