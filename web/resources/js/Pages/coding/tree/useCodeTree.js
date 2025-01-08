import { reactive, toRefs } from 'vue';

const state = reactive({
  dragging: false,
  collapsed: {},
  sorting: null,
});

export const useCodeTree = () => {
  const { dragging, collapsed, sorting } = toRefs(state);

  return {
    dragging,
    sorting,
    collapsed,
    setSorting: (codebookId) => {
      state.sorting = codebookId;
      return codebookId;
    },
    setDragging: (value) => {
      state.dragging = value;
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
