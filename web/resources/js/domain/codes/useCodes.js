import { usePage } from '@inertiajs/vue3';
import { computed, reactive, toRefs } from 'vue';
import { Codebooks } from '../codebooks/Codebooks.js';
import { Codes } from './Codes.js';
import { Selections } from '../../Pages/coding/selections/Selections.js';
import { CodeList } from './CodeList.js';
import { createCodeSchema } from './createCodeSchema.js';

const state = reactive({
  details: {}, // code ids
});

export const useCodes = () => {
  const { details } = toRefs(state);
  const page = usePage();
  const { allCodes, codebooks, projectId, source, preferences } = page.props;
  const sourceId = source?.id;
  const key = `${projectId}-${sourceId}`;
  const codeStore = Codes.by(key);
  const codebookStore = Codebooks.by(projectId);
  const selectionStore = Selections.by(key);

  const initCoding = async () => {
    const initialSelections = source?.selections ?? [];
    const results = { added: [], clean: [] };
    const toResults = (res) => {
      results.added.push(...res.added);
      results.clean.push(...res.clean);
    };
    toResults(codebookStore.init(codebooks, preferences[0].codebooks));
    toResults(codeStore.init(allCodes, preferences[0].codebooks));
    toResults(
      selectionStore.init(initialSelections, (id) => {
        try {
          return codeStore.entry(id);
        } catch (e) {
          console.error(e);
        }
      })
    );
    return results;
  };

  //---------------------------------------------------------------------------
  // CREATE
  //---------------------------------------------------------------------------
  const createCode = async (options) => {
    ['title', 'color', 'codebookId'].forEach((key) => {
      if (!options[key]) {
        throw new Error(`${key} is required!`);
      }
    });

    const { response, error, code } = await Codes.create({
      projectId,
      source,
      ...options,
    });
    if (error) throw error;
    if (response.status < 400) {
      return code;
    }
  };
  //---------------------------------------------------------------------------
  // UPDATE
  //---------------------------------------------------------------------------
  const updateCode = async ({ id, ...value }) => {
    const entry = codeStore.entry(id);
    const diff = {};
    const restore = {};

    if (value.title !== entry.name) {
      diff.name = value.title;
      restore.name = entry.name;
    }
    if ((value.description ?? '') !== entry.description) {
      diff.description = value.description ?? '';
      restore.description = entry.description;
    }

    if (value.color !== entry.color) {
      diff.color = value.color;
      restore.color = entry.color;
    }

    if (Object.keys(diff).length === 0) {
      throw new Error(`Make at least one change in order to update`);
    }

    // optimistic UI
    codeStore.update(id, diff);
    const updatedCode = codeStore.entry(id);
    selectionStore.update((selections) => {
      const updated = [];
      selections.forEach((selection) => {
        if (selection.code.id === updatedCode.id) {
          selection.code = updatedCode;
          updated.push(selection);
        }
      });
      return updated;
    });

    const { error, response } = await Codes.update({
      projectId,
      code: entry,
      ...diff,
    });
    if (error) {
      codeStore.update(id, restore);
      throw error;
    }
    if (response.status >= 400) {
      codeStore.update(id, restore);
      throw new Error(response.data.message);
    }

    return true;
  };
  //---------------------------------------------------------------------------
  // DELETE
  //---------------------------------------------------------------------------
  const deleteCode = async (code) => {
    // here we do not optistic UI, because
    // adding-back will destroy the code-order
    const { response, error } = await Codes.delete({ projectId, source, code });
    if (error) throw error;
    if (response?.status >= 400) throw new Error(response.data.message);

    codeStore.remove(code.id);
    const selections = selectionStore
      .all()
      .filter((selection) => selection.code.id === code.id);
    selectionStore.remove(...selections.map((s) => s.id));
    return true;
  };

  /**
   *
   * @param codeId
   * @param parentId
   * @return {Promise<boolean>}
   */
  const addCodeToParent = async ({ codeId, parentId }) => {
    if (codeId === parentId) {
      throw new Error('Cannot make code its own parent!');
    }
    if (!codeId) {
      throw new Error('Cannot add code without id to a parent');
    }

    const code = codeStore.entry(codeId);
    const parent = parentId && codeStore.entry(parentId);

    // check legitimacy of this operation
    if (parent && !CodeList.dropAllowed(code, parent)) {
      throw new Error(
        `${code?.name} is not allowed to become a child of ${parent?.name}!`
      );
    }

    const oldParent = code.parent;
    //
    // // optimistic UI
    codeStore.update(code.id, { parent });
    // if (parent) {
    //   parent.children = parent.children ?? [];
    //   parent.children.push(code);
    // }
    // code.parent = parent;
    //
    // // TODO make optimistic UI procedures
    // //   a command-pattern that can be undone
    const rollback = () => {
      codeStore.update(code.id, { parent: oldParent });
      parent.children.pop();
      code.parent = oldParent;
    };
    //
    const { response, error } = await Codes.update({
      projectId,
      source,
      code,
      parent,
    });
    //
    if (error) {
      rollback();
      throw error;
    }
    if (response?.status >= 400) {
      rollback();
      throw new Error(response.data.message);
    }
    //
    // return true;
  };

  const computedCodes = computed(() => {
    return codeStore.all().filter((c) => !c.parent);
  });

  const computedCodebooks = computed(() => {
    return codebookStore.all();
  });

  const getCodebook = (id) => codebookStore.entry(id);
  const toggleCodebook = async (codebook) => {
    const active = !codebook.active;
    codebookStore.active(codebook.id, active);
    activateCodes({
      codes: codeStore.all().filter((c) => c.codebook === codebook.id),
      active,
      withIntersections: false,
    });
  };

  const toggleDetails = (codebookId) => {
    state.details[codebookId] = !state.details[codebookId];
  };

  const activateCodes = ({ codes, active, withIntersections }) => {
    const updatedSelections = new Map();
    const addSelection = (selection) => {
      const target = selection?.value ?? selection;
      if (!updatedSelections.has(target.id)) {
        updatedSelections.set(target.id, selectionStore.entry(target.id));
      }
    };
    codeStore.update(() => {
      codes.forEach((code) => {
        code.active = active;

        // side-effect: mark selections to update
        if (code.text?.length) {
          code.text.forEach(addSelection);
        }
      });
      return codes;
    });

    if (withIntersections) {
      const interSections = selectionStore.getIntersecting([
        ...updatedSelections.values(),
      ]);
      if (interSections.length) {
        interSections.forEach(addSelection);
      }
    }

    selectionStore.observable.run('updated', [...updatedSelections.values()]);
  };

  const toggleCode = (code) => {
    const active = !code.active;
    const codes = [];
    const addCode = (cd) => {
      codes.push(cd);
      if (cd.children?.length) {
        cd.children.forEach((c) => addCode(c));
      }
    };
    addCode(code);
    activateCodes({ codes, active, withIntersections: true });
  };

  const selections = computed(() => {
    return selectionStore.all().toSorted(Selections.sort.byRange);
  });

  const overlaps = computed(() => {
    return selectionStore.getIntersections(selectionStore.all());
  });
  const selectionsByIndex = (index) => {
    return selections.value.filter(({ start, end }) => {
      return start <= index && end >= index;
    });
  };

  const observe = (name, callbacks) => {
    switch (name) {
      case codeStore.key:
        return codeStore.observe(callbacks);
      case codebookStore.key:
        return codebookStore.observe(callbacks);
      case selectionStore.key:
        return selectionStore.observe(callbacks);
      default:
        throw new Error(`Unknown observe name: ${name}`);
    }
  };

  return {
    createCode,
    createCodeSchema,
    updateCode,
    deleteCode,
    initCoding,
    codes: computedCodes,
    addCodeToParent,
    getCode: (id) => codeStore.entry(id),
    getCodebook,
    observe,
    codebooks: computedCodebooks,
    selections,
    overlaps,
    selectionStore,
    toggleCodebook,
    toggleCode,
    selectionsByIndex,
    sorter: {
      byIndex: useCodes,
    },
    showDetails: details,
    toggleDetails,
  };
};
