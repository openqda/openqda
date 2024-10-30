export const changeRGBOpacity = (rgba, opacity) => {
    const rgbaValues = rgba.match(/[\d.]+/g);
    if (rgbaValues && rgbaValues.length >= 3) {
        return `rgba(${rgbaValues[0]}, ${rgbaValues[1]}, ${rgbaValues[2]}, ${opacity})`;
    }
    return rgba;
};
