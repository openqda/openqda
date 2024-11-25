export const hexToRgbValues = (hex) => {
  // Expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
  let shorthandRegex = /^#?([a-f\d])([a-f\d])([a-f\d])$/i;
  hex = hex.replace(shorthandRegex, function (m, r, g, b) {
    return r + r + g + g + b + b;
  });

  let result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})([a-f\d]{0,2})$/i.exec(
    hex
  );
  return result
    ? [
        parseInt(result[1], 16),
        parseInt(result[2], 16),
        parseInt(result[3], 16),
        parseInt(result[4], 16),
      ]
    : [];
};

export const hexToRgb = (hex) => {
  const [r, g, b] = hexToRgbValues(hex);
  return `rgb(${r}, ${g}, ${b})`;
};

export const hexToRgba = (hex) => {
  const [r, g, b, a] = hexToRgbValues(hex);
  const alpha = a > 0 ? Math.floor(a / 255) : a;
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};
