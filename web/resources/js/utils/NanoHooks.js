/**
 * Minimal hooks to register and run for any given names.
 * As opposed to Events (i.e. nanoevents) these will run
 * in sync mode and thus in registered order.
 * If you need async functionality then you might
 * consider an event emitter like nanoevents instead.
 */
export class NanoHooks {
  /**
   * The underlying data structure is a map
   * of function arrays. It's a public
   * member, because you may need to manually
   * access it for whatever reason.
   * @type {Map<string, function[]>}
   */
  src = new Map();

  /**
   * Register a new hook by name.
   * Async functions are possible but discouraged as
   * there will be race conditions.
   * @param name {string}
   * @param fn {function}
   * @return {function} call to unregister hook
   */
  on(name, fn) {
    let n = this;
    if (!n.src.has(name)) {
      n.src.set(name, []);
    }
    n.src.get(name).push(fn);
    return () => n.off(name, fn);
  }

    /**
     * Unregister a hook by given name and function.
     * Note that reference check is strict equal,
     * so it needs to be the exact same function.
     * If you create dynamic functions a lot, you
     * may rather use the returned function from `on`
     * to unregister the given functions.
     * @param name {string}
     * @param fn {function}
     */
  off(name, fn) {
    let a = this.src.get(name);
    let i = a.findIndex((f) => f === fn);
    if (i >= -1) {
      a.splice(i, 1);
    }
  }

  /**
   * Run all registered hooks for a given
   * name. Args are optional
   * @param name {string}
   * @param args {...[]=}
   */
  run(name, ...args) {
    (this.src.get(name) ?? []).forEach((fn) => fn(...args));
  }
}
