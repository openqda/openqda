import { reactive, toRefs } from 'vue';

const state = reactive({
  codebook: null,
});

export const useCodebookPreview = () => {
  const { codebook } = toRefs(state);

  const open = ({ codebook }) => {
    state.codebook = codebook;
  };
  const close = () => {
    state.codebook = null;
  };

  return {
    codebook,
    open,
    close,
  };
};
