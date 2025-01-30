export const isOverlapping = (a, b, c, d) => b - c > 0 && d - a > 0;

export const getIntersection = (a, b, c, d) => [Math.max(a, c), Math.min(b, d)];
