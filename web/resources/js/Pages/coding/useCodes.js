import { usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { Codebooks } from './codebooks/Codebooks.js';
import { Codes } from './codes/Codes.js';
import { Selections } from './selections/Selections.js';
import { randomColor } from '../../utils/random/randomColor.js';

const createCodeSchema = ({
  title,
  description,
  color,
  codebooks,
  codes,
  parent,
}) => {
  const schema = {
    title: {
      type: String,
      placeholder: 'Name of the code',
      defaultValue: title,
    },
    description: {
      type: String,
      placeholder: 'Code description, optional',
      formType: 'textarea',
      defaultValue: description,
    },
    color: {
      type: String,
      formType: 'color',
      defaultValue: color ?? randomColor({ type: 'hex' }),
    },
  };
  if (codebooks) {
    schema.codebookId = {
      type: Number,
      label: 'Codebook',
      defaultValue: codebooks?.[0]?.id,
      options: codebooks?.map((c) => ({
        value: c.id,
        label: c.name,
      })),
    };
  }
  if (codes) {
    schema.parentId = {
      type: String,
      optional: true,
      label: 'Parent code',
      options: codes.map((c) => ({
        value: c.id,
        label: c.name,
      })),
    };
    if (parent) {
      schema.parentId.defaultValue = parent.id;
    }
  }
  return schema;
};

export const useCodes = () => {
  const page = usePage();
  const { allCodes, codebooks, projectId, source, auth } = page.props;
  const userId = auth.user.id;
  const sourceId = source.id;
  const key = `${projectId}-${sourceId}`;
  const codeStore = Codes.by(key);
  const codebookStore = Codebooks.by(projectId);
  const selectionStore = Selections.by(key);

  const initCoding = async () => {
    codebookStore.init(codebooks);
    codeStore.init(allCodes);
    selectionStore.init(
      source.selections.filter((s) => s.creating_user_id === userId),
      (id) => codeStore.entry(id)
    );
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

    let response;
    const handle = (res) => {
      if (res.error) {
        codeStore.update(id, restore);
        throw res.error;
      }
      if (res.response.status >= 400) {
        codeStore.update(id, restore);
        throw res.response.data.message;
      }
    };
    if ('title' in diff) {
      response = await Codes.updateTitle({
        projectId,
        title: diff.name,
        code: entry,
      });
      handle(response);
    }
    if ('description' in diff) {
      response = await Codes.updateDescription({
        projectId,
        source,
        description: diff.description,
        code: entry,
      });
      handle(response);
    }
    if ('color' in diff) {
      response = await Codes.updateColor({
        projectId,
        code: entry,
        color: diff.color,
      });
      handle(response);
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

  const addCodeToParent = async ({ codeId, parentId }) => {
    const code = codeStore.entry(codeId);
    const parent = codeStore.entry(parentId);
    const oldParent = code.parent;

    // optimistic UI
    codeStore.update(code.id, { parent });
    parent.children = parent.children ?? [];
    parent.children.push(code);

    // TODO make optimistic UI procedures
    //   a command-pattern that can be undone
    const rollback = () => {
      codeStore.update(code.id, { parent: oldParent });
      parent.children.pop();
    };

    const { response, error } = await Codes.updateParent({
      projectId,
      source,
      code,
      parent,
    });

    if (error) {
      rollback();
      throw error;
    }
    if (response?.status >= 400) {
      rollback();
      throw new Error(response.data.message);
    }

    return true;
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
    codeStore.all().forEach((code) => {
      if (code.codebook === codebook.id && code.active !== active) {
        toggleCode(code);
      }
    });
  };

  const toggleCode = (code) => {
    const codes = codeStore.toggle(code.id);

    // notify selections updated
    const updatedSelections = [];
    const addSelections = (code) => {
      if (code.text?.length) {
        code.text.forEach((selection) =>
          updatedSelections.push(selectionStore.entry(selection.id))
        );
      }
    };
    codes.forEach((code) => addSelections(code));

    // reactivate codebook, in case it was inactive
    const codebook = codebookStore.entry(code.codebook);
    if (codebook && code.active && !codebook.active) {
      codebookStore.active(codebook.id, true);
    }

    const interSections = selectionStore.getIntersecting(updatedSelections);
    if (interSections.length) updatedSelections.push(...interSections);

    selectionStore.observable.run('updated', updatedSelections);
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
  };
};
