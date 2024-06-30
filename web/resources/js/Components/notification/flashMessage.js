import { usePage } from '@inertiajs/vue3';

export const flashMessage = (message, options = {}) => {
  const flash = usePage().props.flash;
  flash.message = message;
  if ('type' in options) {
    flash.type = options.type;
  }
};
