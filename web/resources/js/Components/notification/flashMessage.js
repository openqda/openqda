import { reactive, toRefs } from 'vue';

const state = reactive({
  message: null,
});

/**
 * This is used to listen to incoming
 * flash messages, usually only used
 * by the templates that implement the
 * actual notification components.
 * @return {Ref<null | { message: string, type: string=}>}
 */
export const useFlashMessage = () => {
  const { message } = toRefs(state);
  return message;
};

/**
 * Globally available notification (flash-message) invoker.
 * @param message {string|null}
 * @param options {object=}
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
