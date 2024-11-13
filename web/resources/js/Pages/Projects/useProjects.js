import { router, usePage } from '@inertiajs/vue3'
import { reactive, ref, toRef, watch } from 'vue';
import { debounce } from '../../utils/dom/debounce.js';
import { Project  } from '../../domain/Project.js'
import { Routes } from '../../routes/Routes.js'

const state = reactive({
  sortBy: {
    id: 0,
    field: 'name',
    type: 'string',
    order: 'asc',
    label: 'Sort by name ascending',
  },
});

/**
 * Manage projects across pages.
 */
export const useProjects = () => {
  const { projects, project } = usePage().props;
  const sortedProjects = ref([]);
  const sortBy = toRef(state.sortBy);
  const createSchema = Project.create.schema
    const open = projectId => {
        router.visit(Routes.project.path(projectId), {
            preserveScroll: true,
        })
    }
  // ---------------------------------------------------------------
  // SORTING
  // ---------------------------------------------------------------

  const sortOptions = [
    {
      id: 0,
      field: 'name',
      type: 'string',
      order: 'asc',
      label: 'Sort by name ascending',
    },
    {
      id: 1,
      field: 'name',
      type: 'string',
      order: 'desc',
      label: 'Sort by name descending',
    },
    {
      id: 3,
      field: 'updated_at',
      type: 'date',
      order: 'desc',
      label: 'Sort by newest update',
    },
    {
      id: 2,
      field: 'updated_at',
      type: 'date',
      order: 'asc',
      label: 'Sort by oldest update',
    },
  ];

  const byConfig = (a, b) => {
    const { field, type, order } = sortBy.value;
    const valA = order === 'asc' ? a[field] : b[field];
    const valB = order === 'asc' ? b[field] : a[field];
    switch (type) {
      case 'string':
        return valA.localeCompare(valB);
      case 'date':
        return new Date(valA) - new Date(valB);
      default:
        return valA - valB;
    }
  };

  const updateProjects = () => {
    sortedProjects.value = (Array.isArray(projects) ? projects : [])
      .filter(projectsFilter)
      .sort(byConfig);
  };
  const updateSorter = (sorter) => {
    sortBy.value = sorter;
    state.sortBy = sorter;
    updateProjects();
  };

  // ---------------------------------------------------------------
  // FILTERING
  // ---------------------------------------------------------------
  let projectsFilter = (p) => !p.isTrashed; // default
  const searchTerm = ref('');
  const compare = (a = '', b = '') => a && a.trim().toLowerCase().includes(b);
  const byTerm = (p) => {
    const term = searchTerm.value;
    const value = term.trim().toLowerCase();
    return (
      !p.isTrashed &&
      p &&
      (compare(p.name, term) ||
        compare(p.description, term) ||
        compare(p.created_at, term) ||
        compare(p.updated_at, term) ||
        value === `id:${p.id}`)
    );
  };

  const initSearch = () => watch(
    searchTerm,
    debounce((value) => {
      if (value.trim() === '') {
        projectsFilter = (p) => !p.isTrashed;
      }
      if (value.length > 2) {
        projectsFilter = byTerm;
      }
      updateProjects();
    }, 300)
  );

  // initial / default sort and filter
  updateProjects();

  return {
    currentProject: project,
    projects: sortedProjects,
    searchTerm,
    initSearch,
    sortOptions,
    createSchema,
    createProject: Project.create.method,
    open,
    updateSorter,
    sortBy,
  };
};
