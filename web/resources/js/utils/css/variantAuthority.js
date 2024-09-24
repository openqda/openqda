import { cva } from 'class-variance-authority'

/**
 * Creates a resolver tailwind classes for a component with a given set of variances.
 * @param defaultClasses
 * @param variants
 * @param defaultVariants
 * @return {(props?: (Props<unknown> | undefined)) => string}
 */
export const variantAuthority = ({ class: defaultClasses, variants, defaultVariants }) => {
    if (!variants || !defaultVariants) {
        return () => defaultClasses
    }
    return cva(defaultClasses, { variants, defaultVariants })
}
