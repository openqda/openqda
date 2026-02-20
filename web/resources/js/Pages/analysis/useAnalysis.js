import { reactive, ref, toRefs } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { debounce } from '../../utils/dom/debounce.js';
import { unfoldCodes } from './unfoldCodes.js';
import { createByPropertySorter } from '../../utils/array/createByPropertySorter.js';

const state = reactive({
  checkedSources: new Map(),
  allSourcesChecked: false,
  checkedCodes: new Map(),
  allCodesChecked: false,
  selection: [],
  hasSelections: false,
});

const byName = createByPropertySorter('name');

export const useAnalysis = () => {
  const { sources, codes, codebooks } = usePage().props;
  const {
    hasSelections,
    checkedCodes,
    allCodesChecked,
    checkedSources,
    allSourcesChecked,
    selection,
  } = toRefs(state);
  const activeCodebooks = {};
  codebooks.forEach((cb) => {
    if (cb.active !== false) {
      activeCodebooks[cb.id] = true;
    }
  });

  const allCodes = ref(
    unfoldCodes(codes)
      .filter((code) => activeCodebooks[code.codebook])
      .sort(byName)
  );

  const allSources = ref(
    sources
      .map((source) => {
        const isLocked = (source.variables ?? []).some(
          ({ name, boolean_value }) =>
            name === 'isLocked' && boolean_value === 1
        );
        const copy = { ...source };
        copy.date = new Date(source.updated_at).toLocaleDateString();
        copy.variables = { isLocked };
        copy.isConverting = false;
        copy.failed = false;
        copy.converted = true;
        return copy;
      })
      .toSorted(byName)
  );

  const checkCode = (id) => {
    const isAllCodes = id === 'all';

    if (isAllCodes) {
      const newCheckValue = !state.allCodesChecked;
      getAllCodes().forEach((code) =>
        state.checkedCodes.set(code.id, newCheckValue)
      );
      state.allCodesChecked = newCheckValue;
    } else {
      const isChecked = !!state.checkedCodes.get(id);
      state.checkedCodes.set(id, !isChecked);
      state.allCodesChecked = false;
    }

    updateHasSelection();
  };

  const getAllSources = () => sources;
  const getAllCodes = () => allCodes.value;

  const checkSource = (id) => {
    const isAllSources = id === 'all';

    if (isAllSources) {
      const newCheckValue = !state.allSourcesChecked;
      getAllSources().forEach((code) =>
        state.checkedSources.set(code.id, newCheckValue)
      );
      state.allSourcesChecked = newCheckValue;
    } else {
      const isChecked = !!state.checkedSources.get(id);
      state.checkedSources.set(id, !isChecked);
      state.allSourcesChecked = false;
    }

    updateHasSelection();
  };

  const checkedSourcesSize = () =>
    Array.from(state.checkedSources.values()).filter(Boolean).length;
  const checkedCodesSize = () =>
    Array.from(state.checkedCodes.values()).filter(Boolean).length;

  const updateHasSelection = debounce(() => {
    // TODO debounce or throttle?
    const values = [];

    sources.forEach((file) => {
      if (!state.checkedSources.get(file.id)) {
        return;
      }

      const entry = {
        name: file.name,
        codes: [],
      };

      const iterateCodes = (list) => {
        list.forEach((code) => {
          if (state.checkedCodes.get(code.id) && code.text) {
            const current = {
              name: code.name,
              segments: [],
            };

            code.text.forEach((text) => {
              if (text.source_id === file.id) {
                current.segments.push(text);
              }
            });

            if (current.segments.length > 0) {
              entry.codes.push(current);
            }
          }

          if (code.children) {
            iterateCodes(code.children);
          }
        });
      };

      iterateCodes(allCodes.value);

      if (entry.codes.length > 0) {
        values.push(entry);
      }
    });

    selection.value = values;
    hasSelections.value = values.length > 0;
  }, 500);

  return {
    checkedSources,
    checkedCodes,
    allCodesChecked,
    allSourcesChecked,
    selection,
    hasSelections,
    checkSource,
    checkCode,
    codes: allCodes,
    sources: allSources,
    checkedSourcesSize,
    checkedCodesSize,
  };
};
