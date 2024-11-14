import { Codebooks } from './Codebooks.js';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export const useCodebooks = () => {
  const { project, projectId, codebooks } = usePage().props;
  const codebookStore = Codebooks.by(projectId ?? project.id);
  const initCodebooks = () =>
    codebookStore.init(codebooks ?? project.codebooks);

  const importCodebook = async ({ file }) => {
    const { response, error } = await Codebooks.importFromFile({
      projectId: projectId ?? project.id,
      file,
    });
    if (error) {
      throw error;
    }
    if (response.status >= 400) {
      throw new Error(response.data.message);
    }

    const { codebook } = response.data;
    codebook.codes = codebook.codes ?? [];
    codebookStore.add(codebook);

    return { codebook, file };
  };

  const createCodebook = async ({ name, description, shared, codebookId }) => {
    if (!name) {
      throw new Error('Name is required');
    }
    if (typeof shared === 'undefined') {
      throw new Error('Select a share- option');
    }
    const { response, error } = await Codebooks.create({
      projectId: projectId ?? project.id,
      name,
      description,
      sharedWithPublic: shared === 'public',
      sharedWithTeams: shared === 'teams',
        codebookId
    });

    if (error) {
      throw error;
    }
    if (response.status > 400) {
      throw new Error(response.data.message);
    }

    const codebook = response.data.codebook;
    codebook.codes = codebook.codes ?? [];
    codebookStore.add(codebook);

    return codebook;
  };

  const updateCodebook = async ({ name, description, codebookId, shared }) => {
    const data = {
        projectId: projectId ?? project.id,
        codebookId: codebookId,
        name,
        description,
        sharedWithPublic: shared === 'public',
        sharedWithTeams: shared === 'teams',
    }
    const { response, error } = await Codebooks.update(data);
    if (error) {
      throw error;
    }
    if (response.status > 400) {
      throw new Error(response.data.message);
    }

    const codebook = codebookStore.entry(codebookId)
    codebookStore.update(codebookId, data)
    return codebook;
  };

  const computedCodebooks = computed(() => codebookStore.all());

  return {
    codebooks: computedCodebooks,
    initCodebooks,
    updateSortOrder,
    getSortOrderBy,
    createCodebookSchema: Codebooks.schemas.create,
    importCodebookSchema,
    updateCodebook,
    createCodebook,
    importCodebook,
  };
};
const importCodebookSchema = () => ({
  file: {
    type: Object,
    formType: 'file',
    accept: '.xml,.qdc',
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
