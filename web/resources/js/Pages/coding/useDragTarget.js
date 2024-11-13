import { reactive, toRefs } from 'vue';

const state = reactive({
  current: null,
  start: null,
});

/**
 * Keeps track of the current drag/drop operation involved entities.
 */
export const useDragTarget = () => {
  const { current, start } = toRefs(state);

  const setDragStart = (id) => {
    state.start = id;
  };
  const setDragTarget = (id) => {
    state.current = id;
  };

  const clearDrag = () => {
    state.start = null;
    state.current = null;
  };

  return {
      /**
       * The current target that is dragged over by
       * the one, used to start dragging.
       */
    dragTarget: current,
      /**
       * The `id` of the item that was used to start
       * the dragging.
       */

    dragStarter: start,
    setDragTarget,
    clearDrag,
    setDragStart,
  };
};
