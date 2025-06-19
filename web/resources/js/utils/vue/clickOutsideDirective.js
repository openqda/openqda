/**
 * @module
 */

/**
 * Common-purpose custom vue-directive
 * to run a callback when clicking outside a component.
 *
 * @directive
 * @type {{unmounted(*): void, mounted(*, *): void}}
 * @example
 * <Component :v-click-outside="() => {}" />
 */
export const vClickOutside = {
  mounted(el, binding /*, vnode */) {
    el.handleOutsideClick = (event) => {
      if (!el.contains(event.target)) {
        binding.value.callback(event, el);
      }
    };
    document.addEventListener('click', el.handleOutsideClick);
  },
  unmounted(el) {
    document.removeEventListener('click', el.handleOutsideClick);
  },
};
