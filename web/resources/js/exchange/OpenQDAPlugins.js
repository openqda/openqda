/** @private **/
const plugins = new Map();

export const OpenQDAPlugins = {
  register: (plugin) => {
    const { type, key } = plugin;
    if (!plugins.has(type)) {
      plugins.set(type, new Map());
    }
    plugins.get(type).set(key, plugin);
  },
  all: ({ type }) => {
    const map = plugins.get(type);
    return map ? [...map.values()] : [];
  },
  get: ({ type, key }) => {
    const map = plugins.get(type);
    return map ? map.get(key) : null;
  },
};
