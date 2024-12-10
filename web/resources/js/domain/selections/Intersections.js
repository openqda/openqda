export const Intersections = {};

Intersections.from = (selections) => {
  return segmentize(selections);
};

Intersections.sort = (list) => list.sort((a, b) => a[0] - b[0]);

/**
 * @typedef SelectionEntry
 * @type object
 * @property x {number} start index of this selection
 * @property y {number} end index of this selection
 * @property c {any} the linked code; the actual type is not relevant for operation
 */

/**
 * @typedef Segment
 * @type object
 * @property x {number} start index of this segment
 * @property y {number} end index of this segment
 * @property c {Set<any>} the linked codes as deduplicated Set
 */

/**
 * Transform selections into segments with one or more
 * related code, depending on the overlap (intersection)
 * of selections.
 *
 * Inspired by Klee's 1D Measure Problem algorithm (union of intervals),
 * the result is a list of segments with related codes, representing
 * overlapping of selections of arbitrary depth.
 *
 * The input is a list of selections, where each
 * entry contains a start (x), an end (y) and
 * a related code (c).
 *
 * The time complexity is influenced by two factors
 * - the size of the input N
 * - the sorting of the 2N-sized point vector
 * - the size of the potential range of indexes
 *
 * Choosing a different sorting (e.g. integer based)
 * one could improve on the overall complexity.
 *
 * The space complexity
 *
 * @author Jan Küster
 * @param seg {SelectionEntry[]}
 * @return {Segment[]}
 */
export const segmentize = (seg) => {
  // --------------------------------------------------------
  // GENERIC PART
  // --------------------------------------------------------
  // the initial part of the algorithm follows
  // Klee's 1D Measurement Problem:
  // - the input  gets mapped into a 2N vector
  // - additionally we add the linked code to each entry
  // - the vector gets sorted
  let n = seg.length;
  let m = n * 2;

  // Create a vector to store starting and ending
  // points
  let points = new Array(m);

  for (let i = 0; i < n; i++) {
    points[i * 2] = [seg[i].x, false, seg[i].c];
    points[i * 2 + 1] = [seg[i].y, true, seg[i].c];
  }

  // Sorting all points by point value
  // This is the major computation effort
  // lading to complexity of O(n log n) when
  // using the default.
  // However, this implementation enables to
  // use different sorting algorithms to maximize the
  // performance and scalability.
  // Intersections.sort(points)
  points.sort((a, b) => a[0] - b[0]);

  const result = [];
  let codes = new Set();
  let current = {};
  let value = null;
  let code = null;
  let prev = null;

  // tp keep track of, whether we are in a state
  // of open-ness, we need to check if there is
  // opened > closed
  let opened = 0;
  let closed = 0;

  // --------------------------------------------------------
  // DOMAIN-SPECIFIC PART
  // --------------------------------------------------------
  // Traverse through all points.
  //
  // In contrast to Klee's measure problem
  // there is no union to be computed but.
  for (let i = 0; i < m; i++) {
    value = points[i][0];
    code = points[i][2];
    prev = result[result.length - 1];

    // --------------------------------------------------------
    // CLOSING
    // --------------------------------------------------------
    if (points[i][1]) {
      // usually closing a segment involves
      // an already opened segment, so we
      // simply add the y value and end here
      if (current.x) {
        current.y = value;
        current.c = [...codes];
        result.push(current);
      }
      // however, there might be closing
      // following by closings, in which
      // we have to the y value from
      // the previous segment as starting
      // and closing with this one
      else {
        // edge case: the previous segment closed
        // at the same as this one then only add the code
        if (prev && prev.y !== value) {
          current.x = prev.y;
          current.y = value;
          current.c = [...codes];
          result.push(current);
        }
      }

      // start new segment immediately
      current = {};

      // always remove code from the set
      codes.delete(code);
      closed++;
    }

    // --------------------------------------------------------
    // OPENING
    // --------------------------------------------------------
    else {
      // segment already has a starting position
      if (current.x) {
        // Edge case: another new selection
        // starts at the exact same point
        // then we only add the code.
        // Otherwise, the current segment ends,
        // and we start a new segment with this
        // starting point, ending with x - 1
        if (current.x !== value) {
          current.y = value;
          current.c = [...codes];
          result.push(current);
        }
      }

      // new segment start
      else {
        // if we start a new segment
        // but there is an open segment left
        // then we have to cover the "void" first
        // by adding a closing-segment between the prev
        // and this one
        if (prev && opened > closed) {
          current.x = prev.y;
          current.y = value;
          current.c = [...codes];
          result.push(current);
        }
      }

      // start new segment
      // and assign new x
      current = {};
      current.x = value;

      // always add code to the set
      codes.add(code);
      opened++;
    }
  }

  return result;
};
