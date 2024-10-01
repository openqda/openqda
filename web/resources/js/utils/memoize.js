export const memoize = (fn) => {
    const map = new Map()
    return function memoized (...args) {
        const self = this
        const str = JSON.stringify(args, replacer, 0)
        return map.has(str)
            ? map.get(str)
            : fn.apply(self, args)
    }
}

const replacer = (key, value) => {
    if (typeof value === 'function') {
        return value.toString()
    }
    return value
}
