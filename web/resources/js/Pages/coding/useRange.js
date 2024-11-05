import { toRefs, reactive } from 'vue';

const state = reactive({
  prevRange: null,
  range: null,
  text: '',
});

/**
 * Composable for editor selection ranges
 */
export const useRange = () => {
  const { range, prevRange, text } = toRefs(state);
  const setRange = (data, txt) => {
    if (data !== null) {
      const { index, length } = data;

      const end = index + length;
      const start = index;
      const r = { index, length, start, end };
      state.prevRange = r;
      state.range = r;

      if (txt) {
        state.text = txt;
      }
    } else {
      state.range = data;
      state.text = '';
    }
  };
  return {
    range,
    prevRange,
    setRange,
    text,
  };
};
