/**
 * Creates a sort callback for {Array.prototype.sort} that
 * allows to sort objects by the given property name.
 * @param prop
 * @param descending
 * @return {function(*, *): number}
 */
export const createByPropertySorter =
  (prop, descending = false) =>
  (a, b) => {
    const propA = a?.[prop] ?? ''
    const propB = b?.[prop] ?? ''
    return descending ? propB.localeCompare(propA) : propA.localeCompare(propB)
  }
