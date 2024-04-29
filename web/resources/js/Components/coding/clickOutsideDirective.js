export const vClickOutside = {
    mounted(el, binding, vnode) {
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
