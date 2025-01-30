/**
 * @module
 */

/**
 * Detects, whether a given element is currently within the visible
 * area of the browser screen.
 * @function
 * @see https://stackoverflow.com/questions/123999/how-can-i-tell-if-a-dom-element-is-visible-in-the-current-viewport/7557433#7557433
 * @param el {HTMLElement}
 * @return {boolean}
 */
export const isInViewport = (el) => {
  const { top, left, right, bottom } = el.getBoundingClientRect();
  const { innerHeight, innerWidth } = window;
  const { clientHeight, clientWidth } = document.documentElement;

  const height = innerHeight ?? clientHeight;
  const width = innerWidth ?? clientWidth;

  // do not compute on incomplete values
  if ([top, left, right, bottom, width, height].includes(undefined))
    return false;

  return top >= 0 && left >= 0 && bottom <= height && right <= width;
};
