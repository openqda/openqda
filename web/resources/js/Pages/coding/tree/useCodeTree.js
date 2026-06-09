import { reactive, toRefs } from 'vue';

const state = reactive({
  dragging: false,
  collapsed: {},
  sorting: null,
  query: null,
});

export const useCodeTree = () => {
  const { query, dragging, collapsed, sorting } = toRefs(state);

  return {
    dragging,
    sorting,
    query,
    collapsed,
    setQuery: (q) => {
      state.query = q.toLocaleLowerCase();
      return q;
    },
    setSorting: (codebookId) => {
      state.query = null;
      state.sorting = codebookId;
      return codebookId;
    },
    setDragging: (value) => {
      state.dragging = value;
      state.query = null;
      return value;
    },
    collapse: (id, value) => {
      state.collapsed[id] = value;
      return value;
    },
    toggleCollapse: (id) => {
      state.collapsed[id] = !state.collapsed[id];
      return state.collapsed[id];
    },
  };
};
