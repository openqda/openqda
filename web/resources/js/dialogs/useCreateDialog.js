import { reactive, toRefs } from 'vue';
import { noop } from '../utils/function/noop.js';

const state = reactive({
  schema: null,
  id: null,
  onCreated: null,
});

/**
 * @return {{schema, id, close: close, onCreated, open: open}}
 */
export const useCreateDialog = () => {
  const { schema, id, onCreated } = toRefs(state);
  const open = ({ id, schema, onCreated = noop }) => {
    state.schema = schema;
    state.id = id;
    state.onCreated = onCreated;
  };
  const close = () => {
    state.schema = null;
    state.id = null;
    state.onCreated = null;
  };
  return { id, schema, open, close, onCreated };
};
