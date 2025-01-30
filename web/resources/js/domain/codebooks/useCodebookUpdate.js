import { reactive, toRefs } from 'vue';
import { Codebooks } from './Codebooks.js';

const state = reactive({
  codebook: null,
  schema: null,
});

export const useCodebookUpdate = () => {
  const { codebook, schema } = toRefs(state);
  const open = (cb) => {
    state.codebook = cb;
    state.schema = Codebooks.schemas.update(cb);
  };
  const close = () => {
    state.codebook = null;
  };

  return {
    codebook,
    schema,
    open,
    close,
  };
};
