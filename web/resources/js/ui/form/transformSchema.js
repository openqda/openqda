import { FormElements } from './FormElements.js';
import { markRaw } from 'vue';

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
  schema.required = schema.optional !== true || schema.required !== false;
  delete schema.optional;
  schema.formType = getFormType(schema);
  schema.formElement =
    FormElements.get(schema.formType) ?? FormElements.default();
  schema.label = getLabel(schema);
  schema.options = getOptions(schema);
  const data = ((_schema) => {
    const { type, formType, formElement, options, data, ...rest } = _schema;
    const d = {
      type: formType,

      ...rest,
    };
    console.debug({ d });
    return d;
  })(schema);

  return {
    data,
    component: markRaw(schema.formElement),
  };
};

const getFormType = ({ formType, type, options, allowedValues }) => {
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
      return 'select-radio';
    default:
      throw new Error(`unknown type ${type}`);
  }
};

const getFormElement = ({ formType }) => {
  // major types are
  // input
  // textarea
  // select
  // radio
  // check
  // custom
  // Maybe we to this open-close?
  switch (formType) {
    case 'textarea':
      return 'textarea';
    default:
      return 'input';
  }
};

const getLabel = ({ label, name }) => {
  if (label) return label;
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

const getOptions = ({ formType, options, allowedValues }) => {
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
