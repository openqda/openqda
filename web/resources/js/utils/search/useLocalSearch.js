import { ref, watch } from 'vue';
import { debounce } from '../dom/debounce.js';

/**
 * Provides template-level-scoped (= local) search.
 * @param fields {object} key-value dictionary
 * @param recursive {object=} optional definitions for recursive search
 * @param delay {number=} optional delay for search, default is 300, increase if performance heavy
 */
export const useLocalSearch = ({ fields, recursive, delay = 300 }) => {
  const searchTerm = ref('');
  const searchFilter = ref(() => true);

  watch(
    searchTerm,
    debounce((value) => {
      if (value.trim() === '') {
        searchFilter.value = () => true;
      }
      if (value.length > 2) {
        searchFilter.value = createFilter({
          query: searchTerm.value,
          fields,
          recursive,
        });
      }
    }, delay ?? 300)
  );

  return { searchTerm, searchFilter };
};

const compare = (a = '', b = '') => a && a.trim().toLowerCase().includes(b);
const createFilter = ({ query, fields, recursive }) => {
  const terms = query.trim().toLowerCase().split('|').filter(Boolean);
  const keys = Object.keys(fields);
  const match = (target) => {
    const idQuery = `id:${target.id}`;
    const found = terms.some((term) => {
      return keys.some((field) => {
        if (!Object.hasOwn(target, field)) {
          return false;
        }
        if (field === 'id') {
          return term === idQuery;
        }

        return compare(target[field], term);
      });
    });
    if (found) return true;

    const hasChildren = recursive && Object.hasOwn(target, recursive.field);
    if (!hasChildren) return false;

    const children = target[recursive.field];
    return children.some((child) => match(child));
  };

  return (src) => {
    if (!src) return false;
    return match(src);
  };
};
