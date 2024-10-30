import { toRefs, reactive } from 'vue';

const state = reactive({
  openWith: null,
});

export const useContextMenu = () => {
  const { openWith } = toRefs(state);

  const open = (codeId) => {
    openWith.value = codeId;
  };

  return {
    openWith,
    open,
  };
};
