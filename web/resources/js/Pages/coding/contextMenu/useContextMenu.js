import { toRefs, reactive } from 'vue';

const state = reactive({
  openWith: null,
  isOpen: false,
});

export const useContextMenu = () => {
  const { openWith, isOpen } = toRefs(state);

  const open = (codeId) => {
    state.openWith = codeId;
    state.isOpen = true;
    return true;
  };

  const close = () => {
    state.isOpen = false;
    return true;
  };

  return {
    openWith,
    open,
    close,
    isOpen,
  };
};
