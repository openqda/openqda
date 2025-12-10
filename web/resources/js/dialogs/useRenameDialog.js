import { reactive, toRefs } from 'vue';

const state = reactive({
  schema: null,
  target: null,
  id: null,
});

export const useRenameDialog = () => {
  const { schema, target, id } = toRefs(state);
  const open = ({ id, schema, target }) => {
    state.schema = schema;
    state.target = target;
    state.id = id;
  };
  const close = () => {
    state.schema = null;
    state.target = null;
    state.id = null;
  };
  return { id, schema, target, open, close };
};
