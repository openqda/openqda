import { reactive, toRefs } from 'vue';
import { noop } from '../utils/function/noop.js';

const state = reactive({
  schema: null,
  id: null,
  onCreated: null,
});

export const useCreateDialog = () => {
  const { schema, id, onCreated } = toRefs(state);
  const open = ({ id, schema, onCreated = noop }) => {
    state.schema = schema;
    state.id = id;
    state.onCreated = onCreated;
  };

  return { id, schema, open, onCreated };
};
