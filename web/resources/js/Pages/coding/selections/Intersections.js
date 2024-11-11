export const Intersections = {}

Intersections.isOverlapping = (a, b, c, d) => (b - c >= 0 && d - a >= 0)

Intersections.getIntersection = (a, b, c, d) => [Math.max(a, c), Math.min(b, d)]
