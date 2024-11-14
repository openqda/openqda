import { computed, reactive, ref, toRef, toRefs } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { debounce } from '../../utils/dom/debounce.js';
import { unfoldCodes } from './unfoldCodes.js';
import { createByPropertySorter } from '../../utils/array/createByPropertySorter.js';

const state = reactive({
  checkedSources: new Map(),
  checkedCodes: new Map(),
  selection: [],
  hasSelections: false,
});

const byName = createByPropertySorter('name');

export const useAnalysis = () => {
  const { sources, codes, codebooks } = usePage().props;
  const { hasSelections, checkedCodes, checkedSources, selection } =
    toRefs(state);
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
    const isAllCodes = id === 'all_codes';
    const isChecked = !!checkedCodes.value.get(id);

    if (isAllCodes) {
      getAllCodes().forEach((code) => {
        state.checkedCodes.set(code.id, !isChecked);
      });
    } else {
      state.checkedCodes.set('all_codes', false);
    }

    state.checkedCodes.set(id, !isChecked);
    updateHasSelection();
  };

  const getAllFiles = () => sources;
  const getAllCodes = () => allCodes.value;

  const checkSource = (id) => {
    const isAllFiles = id === 'all_files';
    const isChecked = !!checkedSources.value.get(id);

    if (isAllFiles) {
      getAllFiles().forEach((source) => {
        state.checkedSources.set(source.id, !isChecked);
      });
    } else {
      state.checkedSources.set('all_files', false);
    }

    state.checkedSources.set(id, !isChecked);
    updateHasSelection();
  };

  const updateHasSelection = debounce(() => {
    // TODO debounce or throttle?
    const values = [];

    sources.forEach((file) => {
      if (!checkedSources.value.get(file.id)) {
        return;
      }

      const entry = {
        name: file.name,
        codes: [],
      };

      const iterateCodes = (list) => {
        list.forEach((code) => {
          if (checkedCodes.value.get(code.id) && code.text) {
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
    selection,
    hasSelections,
    checkSource,
    checkCode,
    codes: allCodes,
    sources: allSources,
  };
};
