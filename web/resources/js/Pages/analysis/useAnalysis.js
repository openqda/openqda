import { reactive, ref, toRefs } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { debounce } from '../../utils/dom/debounce.js';
import { unfoldCodes } from './unfoldCodes.js';
import { createByPropertySorter } from '../../utils/array/createByPropertySorter.js';
import { request } from '../../utils/http/BackendRequest.js';
import { useUsers } from '../../domain/teams/useUsers.js';

const state = reactive({
  checkedSources: new Map(),
  allSourcesChecked: false,
  checkedCodes: new Map(),
  allCodesChecked: false,
  selection: [],
  hasSelections: false,
  selectionsLoaded: false,
});

const byName = createByPropertySorter('name');

export const useAnalysis = () => {
  const {
    sources,
    codes,
    codebooks,
    project,
    notes: rawNotes,
  } = usePage().props;
  const { allUsers } = useUsers();
  const notes = rawNotes.map((n) => {
    n.user = allUsers[n.creating_user_id];
    return n;
  });
  const projectId = String(project.id);
  const {
    hasSelections,
    checkedCodes,
    allCodesChecked,
    checkedSources,
    allSourcesChecked,
    selection,
    selectionsLoaded,
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

  const loadSelections = async () => {
    if (selectionsLoaded.value) {
      return true;
    }
    const url = route('analysis.selections', { project: projectId });
    const { error, response } = await request({ url, type: 'get' });
    if (error) {
      throw error;
    }
    if (response.status >= 400) {
      throw new Error(`[${response.status}]: Failed to load selections`);
    }
    const { selections } = response.data;
    selections.forEach((selection) => {
      const { code_id } = selection;
      (allCodes.value ?? []).forEach((code) => {
        if (code.id === code_id) {
          if (!Array.isArray(code.text)) {
            code.text = [];
          }
          code.text.push(toSelection(selection));
        }
      });
    });
    updateHasSelection();
    state.selectionsLoaded = true;
    return true;
  };
  const leaveAnalysis = () => {
    state.selectionsLoaded = false;
  };

  return {
    notes,
    checkedSources,
    checkedCodes,
    allCodesChecked,
    allSourcesChecked,
    selection,
    hasSelections,
    selectionsLoaded,
    loadSelections,
    leaveAnalysis,
    checkSource,
    checkCode,
    codes: allCodes,
    sources: allSources,
    checkedSourcesSize,
    checkedCodesSize,
  };
};

const toSelection = (s) => ({
  id: s.id,
  code_id: s.code_id,
  start: s.start_position,
  end: s.end_position,
  createdBy: s.creating_user_id,
  updatedAt: s.updated_at,
  createdAt: s.created_at,
  source_id: s.source_id,
  text: s.text,
});
