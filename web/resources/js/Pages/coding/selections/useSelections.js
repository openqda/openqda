import { reactive, toRefs } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Selections } from './Selections.js';
import { randomUUID } from '../../../utils/random/randomUUID.js';

const state = reactive({
  selected: null,
  current: null,
  toDelete: [],
});

export const useSelections = () => {
  const { projectId, source } = usePage().props;
  const sourceId = source.id;
  const key = `${projectId}-${sourceId}`;
  const { selected, toDelete, current } = toRefs(state);
  const selectionStore = Selections.by(key);
  const select = ({ code, parent }) => {
    state.selected = { code, parent };
    return true;
  };
  const createSelection = async ({ code, index, length, text }) => {
    if (!length || !code?.id) {
      return;
    }

    const start = index;
    const end = start + length;

    // optimistic UI
    // we add the selection on the ui,
    // even if we don't know if it will work out
    // and remove it only in case it didn't work
    const selection = {
      id: randomUUID(),
      start,
      end,
      length,
      text,
      code,
    };

    const { response, error } = await Selections.store({
      projectId: projectId,
      sourceId: source.id,
      code,
      start,
      end,
      text,
    });

    if (error) throw error;
    if (response.status >= 400) throw new Error(response.data.message);

    const s = response.data.selection;
    selection.id = s.id; // update missing id

    selectionStore.add(selection);
    if (!code.text) {
      code.text = [];
    }
    code.text.push(selection);
    return selection;
  };
  const deleteSelection = async (selection) => {
    const sourceId = source.id;
    const code = selection.code ?? { id: null, texts: [] };

    const { response, error } = await Selections.delete({
      projectId,
      selection,
      code,
      sourceId,
    });
    return !error && response.status < 400;
  };
  const markToDelete = (codes) => {
    state.toDelete = codes;
  };
  const markCurrentByCodeId = (selection) => {
    state.current = selection;
  };
  const reassignCode = async ({ selection, code }) => {
    await Selections.reassign({ projectId, selection, code, source });
  };
  return {
    selected,
    select,
    createSelection,
    deleteSelection,
    current,
    toDelete,
    markToDelete,
    markCurrentByCodeId,
    reassignCode,
  };
};
