import { toRefs, reactive } from 'vue';

const state = reactive({
  openWith: null,
  isOpen: false,
  left: 0,
  top: 0,
  width: 0,
  maxHeight: 0,
});

export const useContextMenu = () => {
  const { openWith, isOpen, left, top, width, maxHeight } = toRefs(state);

  const open = (codeId, options = {}) => {
    state.openWith = codeId;
    state.isOpen = true;
    state.left = options.left || 0;
    state.top = options.top || 0;
    state.width = options.width || 0;
    state.maxHeight = options.maxHeight || 0;
    return true;
  };

  const close = () => {
    state.isOpen = false;
    state.left = 0;
    state.top = 0;
    state.width = 0;
    state.maxHeight = 0;
    return true;
  };

  return {
    openWith,
    open,
    close,
    isOpen,
    left,
    top,
    width,
    maxHeight,
  };
};
