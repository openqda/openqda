<script setup lang="ts">
import { PencilIcon, PlusIcon, TrashIcon } from '@heroicons/vue/20/solid';
import Button from '../../Components/interactive/Button.vue';
import { computed, ref } from 'vue';
import AutoForm from '../../form/AutoForm.vue';
import { useVariables } from './useVariables';
import { attemptAsync } from '../../Components/notification/attemptAsync';
import { cn } from '../../utils/css/cn';
import { isDefined } from '../../utils/isDefined';

const props = defineProps({
  source: Object,
  projectVariables: Array,
});

const { createVariable, deleteVariable, updateVariable, uniqueVariables } =
  useVariables();
const variableValue = (variable) => {
  const prefix = variable.type_of_variable;
  const key = `${prefix}_value`;
  return variable[key];
};

const variables = computed(() => {
  const current = props.source?.variables ?? [];
  return Object.values(uniqueVariables.value).map((variable) => {
    const found = Array.isArray(current)
      ? current.find((v) => v.name === variable.name)
      : current[variable.name];
    const hasOwn = !!found;
    return {
      ...variable,
      ...found,
      hasOwn,
    };
  });
});
const form = ref();
const forms = {
  create: {
    schema: (doc = {}) => {
      const defaultType = doc.type_of_variable ? doc.type_of_variable : 'text';
      const defaultValueKey = `${doc.type_of_variable}_value`;
      const defaultValue = doc[defaultValueKey];
      return {
        name: {
          type: String,
          defaultValue: doc.name,
          autofocus: !doc.name,
          readonly: !!doc.name,
        },
        type: {
          type: String,
          defaultValue: defaultType,
          readonly: !!doc.type_of_variable,
          options: [
            { value: 'boolean', label: 'Boolean' },
            { value: 'text', label: 'Text' },
            { value: 'date', label: 'Date' },
            { value: 'datetime', label: 'Date-Time' },
            { value: 'float', label: 'Number' },
          ],
        },
        description: {
          type: String,
          formType: 'textarea',
          optional: true,
          readonly: isDefined(doc.description),
          defaultValue: doc.description,
        },
        value: {
          type: String,
          autofocus: !!doc.name,
          defaultValue,
        },
      };
    },
    title: () => `Create a new variable for source "${props.source.name}"`,
    color: 'primary',
    submit: {
      label: 'Create',
      variant: 'secondary',
      fn: createVariable,
      successMessage: 'Variable successfully created',
    },
  },
  edit: {
    title: ({ name }) =>
      `Update variable "${name}" for source "${props.source.name}"`,
    schema: (doc = {}) => {
      const schema = forms.create.schema(doc);
      schema.id = {
        type: String,
        defaultValue: doc.id,
        formType: 'hidden',
        label: null,
      };
      return schema;
    },
    color: 'secondary',
    submit: {
      label: 'Update',
      variant: 'secondary',
      fn: ({ source, variable, ...doc }) => {
        const key = `${doc.type}_value`;
        const data = { id: doc.id };
        data[key] = doc.value;
        return updateVariable({ source, ...data });
      },
      successMessage: 'Variable successfully updated',
    },
  },
  delete: {
    title: ({ name }) =>
      `Delete variable "${name}"  for source "${props.source.name}"`,
    schema: (doc = {}) => {
      return {
        id: {
          type: String,
          defaultValue: doc.id,
          formType: 'hidden',
          label: null,
        },
      };
    },
    color: 'destructive',
    preview: true,
    info: 'You are about to delete this variable for this Source. This action cannot be undone and is logged to the audit.',
    submit: {
      label: 'Delete',
      variant: 'destructive',
      fn: ({ source, ...doc }) => {
        const data = { id: doc.id };
        return deleteVariable({ source, ...data });
      },
      successMessage: 'Variable successfully deleted',
    },
  },
};

const setForm = (options) => {
  const def = forms[options?.type];
  const schema = def?.schema(options.target);
  form.value = options
    ? {
        ...def,
        ...options,
        title: def.title(options.target),
        schema,
      }
    : null;
};

const submitForm = async (data) => {
  const type = form.value.type;
  const { submit } = forms[type];
  const fn = () => submit.fn({ source: props.source, ...data });
  await attemptAsync(fn, submit.successMessage);
  setForm(null);
};
</script>

<template>
  <div>
    <table v-if="!form || form.preview" class="table table-auto w-full my-4">
      <thead>
        <tr>
          <th scope="col" class="text-start">Name</th>
          <th scope="col">Type</th>
          <th scope="col">Value</th>
          <th scope="col"></th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="variable in variables"
          v-show="!form || form.target?.id === variable.id"
          :key="variable.id"
          class="text-center h-8"
        >
          <td class="text-start">{{ variable.name }}</td>
          <td>{{ variable.type_of_variable }}</td>
          <td>{{ variableValue(variable) }}</td>
          <td class="text-end">
            <button
              v-if="variable.name !== 'isLocked' && !form && variable.hasOwn"
              class="text-foreground hover:text-primary p-1"
              title="Edit variable"
              @click.stop="setForm({ type: 'edit', target: variable })"
            >
              <PencilIcon class="w-3 h-3" />
            </button>
            <button
              v-if="variable.name !== 'isLocked' && !form && !variable.hasOwn"
              class="text-foreground hover:text-primary p-1"
              title="Create variable entry"
              @click.stop="setForm({ type: 'create', target: variable })"
            >
              <PlusIcon class="w-3 h-3" />
            </button>
            <button
              v-if="variable.name !== 'isLocked' && !form && variable.hasOwn"
              class="text-foreground hover:text-destructive p-1"
              title="Delete variable"
              @click.stop="setForm({ type: 'delete', target: variable })"
            >
              <TrashIcon class="w-3 h-3" />
            </button>
          </td>
        </tr>
      </tbody>
    </table>
    <Button
      v-if="!form"
      variant="outline-secondary"
      size="sm"
      class="w-full my-4"
      @click.stop="setForm({ type: 'create' })"
    >
      <PlusIcon class="w-4 h-4" /> Add New Variable
    </Button>
    <div v-else class="my-4">
      <div :class="cn('font-semibold', `text-${form.color}`)">
        {{ form.title }}
      </div>
      <p v-if="form.info" class="my-4">
        {{ form.info }}
      </p>
      <AutoForm
        v-if="form.schema"
        id="variableForm"
        :schema="form.schema"
        class="my-4"
        @submit="submitForm"
        :show-cancel="false"
        :show-submit="false"
      />
      <div class="flex justify-between items-center">
        <Button variant="outline" @click.stop="setForm(null)"> Cancel </Button>
        <Button
          type="submit"
          form="variableForm"
          :variant="form.submit.variant"
        >
          {{ form.submit.label }}
        </Button>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
