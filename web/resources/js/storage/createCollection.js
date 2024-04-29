import { ThinStorage } from '@thin-storage/core'
import { WebStorageHandler } from "./WebStorageHandler.js";
import { LoggingHandler } from "./LoggingHandler.js";
import { Collections } from "./Collections.js";

const internal = {
    handler: null
}
const getHandler = () => {
    if (!internal.handler) {
        internal.handler = [
            new WebStorageHandler(window.localStorage),
            new LoggingHandler()
        ]
    }
    return internal.handler
}

export const createCollection = ({ name, primary, set }) => {
    const handler = getHandler()
    const collection = new ThinStorage({ name, primary, set, handler })
    Collections.add(name, collection)
    return collection
}
