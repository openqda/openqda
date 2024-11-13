import { usePage } from '@inertiajs/vue3';

/**
 * invokes a new flash-message (notification)
 * @param message {string}
 * @param options {object}
 * @param options.type {string}
 */
export const flashMessage = (message, options = {}) => {
  const flash = usePage().props.flash;
  flash.message = message;
  if ('type' in options) {
    flash.type = options.type;
  }
};
