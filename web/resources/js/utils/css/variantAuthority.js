import { cva } from 'class-variance-authority';

/**
 * @module
 */

/**
 * Creates a resolver tailwind classes for a component with a given set of variances.
 * @function
 * @param class {string} the default classes that are always added
 * @param variants {object} dictionary of variants and their related classes
 * @param defaultVariants {object} dictionary with names of the defaults for the variant categories
 * @return {function(object):string} the mapper of properties to the final classes
 */
export const variantAuthority = ({
  class: defaultClasses,
  variants,
  defaultVariants,
}) => {
  if (!variants || !defaultVariants) {
    return () => defaultClasses;
  }
  return cva(defaultClasses, { variants, defaultVariants });
};
