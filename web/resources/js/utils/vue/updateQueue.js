const TIMEOUT = 100;
const refs = new WeakMap();

/**
 * A debounced queue where updates are applied to a given object
 * only after the timeout ended and no new updates came in during that time.
 * Useful to prevent expensive reactive computations, e.g. when single elements in a list change.
 *
 * @param obj {object}
 * @param update {object}
 * @param timeout {number=} optional timeout, applies only if new queue is created
 */
export const updateQueue = (obj, update, timeout) => {
  if (!refs.has(obj)) {
    refs.set(obj, createUpdateQueue(obj, timeout ?? TIMEOUT, refs));
  }
  refs.get(obj)(update);
};

const createUpdateQueue = (obj, timeout, store) => {
  const queue = [];
  let timer;
  return (update) => {
    queue.push(update);
    clearTimeout(timer);
    timer = setTimeout(() => {
      timer = null;
      store.delete(obj);

      queue.reduce((acc, cur) => Object.assign(acc, cur), obj);
    }, timeout);
  };
};
