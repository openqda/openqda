import { Codebooks } from './Codebooks.js';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue'

export const useCodebooks = () => {
    const { project, projectId, codebooks } = usePage().props
    const codebookStore = Codebooks.by(projectId ?? project.id)
    const initCodebooks = () => codebookStore.init(codebooks ?? project.codebooks);

    const createCodebook = async ({ name, description, shared }) => {
        if (!name) throw new Error('Name is required')
        if (typeof shared === 'undefined') throw new Error('Select a share- option')
        const { response, error } = await Codebooks.create({
            projectId: projectId ?? project.id,
            name, description,
            sharedWithPublic: shared === 'public',
            sharedWithTeams: shared === 'teams'
        })

        if (error) throw error
        if (response.status > 400) throw new Error(response.data.message)

        const codebook = response.data.codebook;
        codebook.codes = [];
        codebookStore.add(response.data)

        return codebook
    };

    const computedCodebooks = computed(() => codebookStore.all())

  return {
    codebooks: computedCodebooks,
    initCodebooks,
    updateSortOrder,
    getSortOrderBy,
    createCodebookSchema,
    createCodebook,
  };
};

const createCodebookSchema = () => ({
  name: String,
  description: {
    type: String,
    formType: 'textarea',
  },
  shared: {
    type: String,
    label: 'Shared with others',
    defaultValue: 'private',
    options: [
      { value: 'private', label: 'Not shared' },
      { value: 'teams', label: 'Shared with teams' },
      { value: 'public', label: 'Shared with public' },
    ],
  },
});

const updateSortOrder = async ({ order, codebook }) => {
  codebook.code_order = order;
};

const getSortOrderBy = (codebook) => {
  const order = codebook.code_order ?? [];

  if (!order.length) {
    return () => 0;
  }

  // transform to a read-optimized version of the order
  const map = {};
  const parseOrder = (list) => {
    list.forEach((item, i) => {
      item.index = i;
      map[item.id] = item;
      if (item.children?.length) {
        parseOrder(item.children);
      }
    });
  };

  parseOrder(order);

  return (a, b) => {
    const indexA = map[a.id].index;
    const indexB = map[b.id].index;
    return indexA - indexB;
  };
};
