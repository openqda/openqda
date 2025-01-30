import { reactive, toRefs } from 'vue';
import { Codebooks } from './Codebooks.js';
import { debounce } from '../../utils/dom/debounce.js';
import { usePage } from '@inertiajs/vue3';

const state = reactive({
  changed: {},
});

export const useCodebookOrder = ({ updateDelay = 2000 } = {}) => {
  const { changed } = toRefs(state);
  const { projectId } = usePage().props;

  const codebookOrderChanged = (codebookId) => {
    state.changed[codebookId] = true;
  };
  const updateSortOrder = async ({ target, codebook }) => {
    const order = parseOrder(target);
    const before = [].concat(codebook.code_order ?? []);
    codebook.code_order = order;

    const { response, error } = await Codebooks.order({
      projectId,
      codebookId: codebook.id,
      order,
    });

    // unmark changed
    delete state.changed[codebook.id];

    if (error || response.status >= 400) {
      codebook.code_order = before;
    }

    return { response, error };
  };

  return {
    codebookOrderChanged,
    getSortOrderBy,
    updateSortOrder: debounce(updateSortOrder, updateDelay),
    changed,
  };
};

const getSortOrderBy = (codebook) => {
  const order = codebook.code_order ?? [];
  if (!order.length) {
    return () => 0;
  }
  // transform to a read-optimized version of the order
  const map = {};
  const parseOrder = (list) => {
    list.forEach((item, i) => {
      map[item.id] = i;
      if (item.children?.length) {
        parseOrder(item.children);
      }
    });
  };

  parseOrder(order);
  return (a, b) => {
    const indexA = map[a.id] ?? 0;
    const indexB = map[b.id] ?? 0;
    return indexA - indexB;
  };
};

const parseOrder = (list) => {
  const entries = [];
  list.forEach((code) => {
    const entry = { id: code.id };
    // add children
    if (code.children?.length) {
      entry.children = parseOrder(code.children);
    }
    entries.push(entry);
  });
  return entries;
};
