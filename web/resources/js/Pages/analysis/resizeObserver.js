import { ref, reactive, onMounted, onBeforeUnmount } from 'vue';

/**
 * Creates a new reactive observer that triggers on
 * window/screen resize. This is useful, if you
 * want to implement responsive visualizations, relative
 * to the current window size.
 * @return {{resizeRef: Ref<any>, resizeState: Reactive<{dimensions: {}}>}}
 */
export const useResizeObserver = () => {
  // create a new ref,
  // which needs to be attached to an element in a template
  const resizeRef = ref();
  const resizeState = reactive({
    dimensions: {},
  });

  const observer = new ResizeObserver((entries) => {
    // called initially and on resize
    entries.forEach((entry) => {
      resizeState.dimensions = entry.contentRect;
    });
  });

  onMounted(() => {
    // set initial dimensions right before observing: Element.getBoundingClientRect()
    resizeState.dimensions = resizeRef.value.getBoundingClientRect();
    observer.observe(resizeRef.value);
  });

  onBeforeUnmount(() => {
    observer.unobserve(resizeRef.value);
  });

  // return to make them available to whoever consumes this hook
  return { resizeState, resizeRef };
};
