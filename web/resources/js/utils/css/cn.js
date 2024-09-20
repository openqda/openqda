import { clsx } from "clsx"
import { twMerge } from "tailwind-merge"

/**
 * Common helper to easily merge tailwind classes.
 * @param inputs
 * @return {string}
 */
export const cn = (...inputs) => {
    return twMerge(clsx(inputs))
}
