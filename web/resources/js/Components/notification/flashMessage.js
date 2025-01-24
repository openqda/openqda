import { reactive, toRefs } from 'vue';

const state = reactive({
  message: null,
});

export const useFlashMessage = () => {
  const { message } = toRefs(state);
  return message;
};

/**
 * invokes a new flash-message (notification)
 * @param message {string|null}
 * @param options {object}
 * @param options.type {string}
 */
export const flashMessage = (message, options = {}) => {
  if (message === null) {
    return (state.message = null);
  }
  const flash = { message };

  if ('type' in options) {
    flash.type = options.type;
  }

  state.message = flash;
};
