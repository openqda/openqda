import { FormElements } from './FormElements.js';
import { markRaw } from 'vue';
import { isDefined } from '@vueuse/core';

/**
 * Transforms a given schema into a normalized
 * form that is easily readable and processable
 * by the AutoForm component and it's children
 * @param value {FormSchema}
 * @param name {string}
 * @return {{component: Raw<unknown>, data: {optional: boolean, type}}}
 */
export const transformSchema = (value, name) => {
  let schema = { ...value };
  if (typeof value === 'function') {
    schema = { type: value, optional: false };
  }
  schema.name = name;

  // every field is optional by default unless
  // explicitly made optional by intent
  let required = true;
  if (schema.optional === true || schema.required === false) {
    required = false;
  }

  schema.required = required;

  delete schema.optional;
  schema.formType = getFormType(schema);
  schema.formElement =
    FormElements.get(schema.formType) ?? FormElements.default();
  schema.label = getLabel(schema);
  schema.options = getOptions(schema);
  schema.validate = getValidator(schema);
  const data = ((_schema) => {
    const { type, formType, formElement, data, ...rest } = _schema;
    return { type: formType, ...rest };
  })(schema);

  return {
    data,
    component: markRaw(schema.formElement),
  };
};

// =====================================================
// INTERNAL
// -----------------------------------------------------
// the following functions are only exported for tests
// =====================================================

export const getFormType = ({ formType, type, options, allowedValues }) => {
  if (formType) return formType;

  if (options || allowedValues) {
    return 'select';
  }
  if (Array.isArray(type)) {
    throw new Error('not yet implemented');
  }
  switch (type) {
    case String:
      return 'text';
    case Number:
      return 'number';
    case Boolean:
      return 'checkbox';
    default:
      throw new Error(`unknown type ${type}`);
  }
};

export const getLabel = ({ label, name }) => {
  if (label || label === null) return label;
  return (
    name
      // remove whitespace
      .trim()
      // split at any uppercase
      .split(/[A-Z]/g)
      // capitalize
      .map((s) => s.charAt(0).toUpperCase() + s.slice(1))
      // construct label
      .join(' ')
  );
};

export const getOptions = ({ formType, options, allowedValues }) => {
  if (options) return options;
  if (allowedValues) {
    return;
  }
  switch (formType) {
    case 'select-radio':
      return [
        {
          value: true,
          label: 'Yes',
        },
        {
          value: false,
          label: 'No',
        },
      ];
  }
};

export const getValidator = (schema) => {
  return (key, value) => {
    const validation = { key, value, valid: true, errors: [] };
    const addError = ({ message }) => {
      validation.valid = false;
      validation.errors.push(message);
    };
    if (!schema) {
      addError({ message: `${key} is not defined in schema` });
      return validation;
    }

    const { label } = schema;
    const name = label || key;

    if (schema.required && !requiredValueDefined(value)) {
      addError({ message: `${name} is required` });
    }

    return validation;
  };
};

export const requiredValueDefined = (value) => {
  if (!isDefined(value)) {
    return false;
  }
  if (Array.isArray(value)) {
    return value.length > 0 && value.every(isDefined);
  }

  switch (typeof value) {
    case 'string':
      return value?.length > 0;
    case 'number':
      return !Number.isNaN(value) && Number.isFinite(value);
    default:
      return true;
  }
};
