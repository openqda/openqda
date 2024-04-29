export const Collections = {}

const map = new Map()

Collections.add = (name, collection) => map.set(name, collection)

Collections.get = name => map.get(name)
