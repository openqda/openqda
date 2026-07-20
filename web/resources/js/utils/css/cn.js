import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * @module
 */

/**
 * Common helper to easily merge tailwind classes and support conditional classes.
 * @function
 * @param inputs
 * @return {string}
 */
export const cn = (...inputs) => {
  const clsxResult = clsx(inputs);
  const uniqueClasses = new Set(clsxResult.split(' '));
  return twMerge([...uniqueClasses].join(' '));
};
