import { request } from '../../utils/http/BackendRequest.js';
import { usePage } from '@inertiajs/vue3';
import { createStoreRepository } from '../../state/StoreRepository.js';
import { AbstractStore } from '../../state/AbstractStore.js';
import { computed } from 'vue';
import { isDefined } from '@vueuse/core';

export const useVariables = () => {
  const {
    variables = [],
    allUsers,
    project,
    projectId: rawProjectId,
  } = usePage().props;
  const projectId = rawProjectId ?? project.id;
  const store = Variables.by(projectId);

  const initVariables = () => {
    return store.init(variables, allUsers);
  };
  const allVariables = computed(() => {
    return store.all();
  });

  const uniqueVariables = computed(() => {
    const all = store.all();
    const map = {};
    for (const variable of all) {
      if (!map[variable.name]) {
        map[variable.name] = {
          name: variable.name,
          description: variable.description,
          type_of_variable: variable.type_of_variable,
          created_at: variable.created_at,
          updated_at: variable.updated_at,
        };
      }
    }
    return Object.values(map);
  });

  const onError = ({ response, error, message }) => {
    if (response.status >= 400 || error) {
      throw new Error(
        `${response.status} ${message} ${response.data?.message || error.message}`
      );
    }
  };

  const createVariable = async ({ source, name, description, type, value }) => {
    const payload = { name, type_of_variable: type, source_id: source.id };
    const key = `${type}_value`;
    payload[key] = value;
    if (isDefined(description)) {
      payload.description = description;
    }

    const { response, error } = await request({
      url: route('variables.store', { project: projectId }),
      type: 'post',
      body: payload,
    });
    onError({ response, error, message: 'Failed to create variable.' });
    if (!source.variables) {
      source.variables = [];
    }
    source.variables.push(response.data.variable);
    store.add(response.data.variable);
    return { response, error };
  };

  const updateVariable = async ({
    source,
    id,
    name,
    type,
    description,
    ...payload
  }) => {
    const url = route('variables.update', { project: projectId, variable: id });
    const { response, error } = await request({
      url,
      type: 'put',
      body: payload,
    });

    onError({ response, error, message: 'Failed to create variable.' });
    const updated = response.data.variable;
    const index = source.variables.findIndex((v) => v.id === id);
    source.variables[index] = updated;
    store.update(id, updated);
    return { response, error };
  };

  const deleteVariable = async ({ source, id }) => {
    const { response, error } = await request({
      url: route('variables.destroy', { project: projectId, variable: id }),
      type: 'delete',
    });
    onError({ response, error, message: 'Failed to delete variable.' });
    const index = source.variables.findIndex((variable) => variable.id === id);
    source.variables.splice(index, 1);
    store.remove(id);
    return { response, error };
  };

  return {
    createVariable,
    deleteVariable,
    allVariables,
    initVariables,
    uniqueVariables,
    updateVariable,
  };
};

const Variables = createStoreRepository({
  key: 'store/variables',
  factory: (options) => new VariablesStore(options),
});

class VariablesStore extends AbstractStore {
  /**
   * add notes to the store if the store is empty
   * @param docs {object[]}
   * @param users {object}
   * @return {{added, clean: *[]}}
   */
  init(docs, users = {}) {
    if (this.size.value === 0 && docs.length !== 0) {
      const mapped = docs.map((doc) => {
        doc.user = users[doc.creating_user_id];
        return doc;
      });
      this.add(...mapped);
    }

    return { added: docs, clean: [] };
  }
}
