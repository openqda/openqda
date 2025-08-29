import { reactive, toRefs } from 'vue';

const state = reactive({
  isActive: false,
});

export const useHelpDialog = () => {
  const { isActive } = toRefs(state);
  const open = () => {
    state.isActive = true;
  };
  const close = () => (state.isActive = false);
  return {
    isActive,
    open,
    close,
  };
};
