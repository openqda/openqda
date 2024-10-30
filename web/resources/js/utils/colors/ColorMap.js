/**
 * @deprecated
 */
export class ColorMap {
  constructor(mapping) {
    this.mapping = { ...mapping };
  }

  color(query) {
    let classNames = '';

    Object.entries(query).forEach(([key, type = 'default']) => {
      const str = extract(this.mapping, key, type) ?? '';
      if (str.length) {
        classNames = `${classNames} ${str}`;
      }
    });

    return classNames;
  }
}

const extract = (map, key, type) => {
  let target;
  if (Object.hasOwn(map, key)) {
    target = map[key];
  }
  if (!target) {
    return '';
  } else if (Object.hasOwn(target, type)) {
    return target[type] ?? target.default;
  } else {
    return target.default;
  }
};
