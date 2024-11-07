import { usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { Codebooks } from './codebooks/Codebooks.js';
import { Codes } from './codes/Codes.js';
import { Selections } from './selections/Selections.js';

const state = reactive({
  loaded: false,
});

const orders = {};
const getOrder = (codebook) => {
  if (!(codebook in orders)) {
    orders[codebook] = 0;
  }
  return orders[codebook]++;
};

export const useCodes = () => {
  const { allCodes, codebooks, projectId } = usePage().props;
  const codeStore = Codes.by(projectId);
  const codebookStore = Codebooks.by(projectId);
  const selectionStore = Selections.by(projectId);

  /**
   * To lazy load codes and codebooks
   */
  const initCodebooks = () => {
    if (state.loaded) {
      return;
    }
    codebooks.forEach((book) => {
      book.active = true;
    });

    const selections = [];
    const codeList = [];
    const parseCodes = (codes, parent = null) => {
      codes.forEach((code) => {
        code.active = true;
        code.parent = parent;
        code.order = getOrder(code.codebook);

        // parse selections
        if (code.text?.length) {
          code.text.forEach((selection) => {
            selection.start = Number(selection.start);
            selection.end = Number(selection.end);
            selection.length = selection.end - selection.start;
            selection.code = code;
            selections.push(selection);
          });
        }
        codeList.push(code);
        if (code.children?.length) {
          parseCodes(code.children, code);
        }
      });
    };
    parseCodes(allCodes);

    console.debug('load codebooks into stores');
    codebookStore.add(...codebooks);
    console.debug('load codes into stores');
    codeStore.add(...codeList);
    console.debug('load selection into stores');
    selectionStore.add(...selections);
    state.loaded = true;
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

        const { response, error, code } = await Codes.create({ projectId, ...options });
        if (error) throw error;
        if (response.status < 400) {
            return code
        }
    };


  const computedCodes = computed(() => {
    return codeStore.all().filter((c) => !c.parent);
  });

  const computedCodebooks = computed(() => {
    return codebookStore.all();
  });

  const toggleCodebook = async (codebook) => {
    const active = !codebook.active;
    codebookStore.active(codebook.id, active);
    codeStore.all().forEach((code) => {
      if (code.codebook === codebook.id && code.active !== active) {
        code.active = active;
      }
    });
  };

  const toggleCode = async (code) => {
    const codes = codeStore.toggle(code.id);

    // notify selections updated
    const selections = [];
    const addSelections = (code) => {
      if (code.text?.length) {
        code.text.forEach((selection) =>
          selections.push(selectionStore.entry(selection.id))
        );
      }
    };
    codes.forEach((code) => addSelections(code));

    // reactivate codebook, in case it was inactive
    const codebook = codebookStore.entry(code.codebook);
    if (codebook && code.active && !codebook.active) {
      codebookStore.active(codebook.id, true);
    }

    selectionStore.observable.run('updated', selections);
  };

  const selections = computed(() => {
    return [...selectionStore.all()].toSorted((a, b) => a.start - b.start);
  });

  const overlaps = computed(() => {
      return []
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
    codes: computedCodes,
    getCode: (id) => codeStore.entry(id),
    observe,
    codebooks: computedCodebooks,
    selections,
    overlaps,
    selectionStore,
    toggleCodebook,
    toggleCode,
    initCodebooks,
    selectionsByIndex,
    sorter: {
      byIndex: useCodes,
    },
  };
};
