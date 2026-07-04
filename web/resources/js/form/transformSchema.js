import { FormElements } from './FormElements.js';
import { markRaw } from 'vue';
import { isDefined } from '../utils/isDefined.js';

/**
 * Transforms a given schema into a normalized
 * form that is easily readable and processable
 * by the AutoForm component and it's children
 * @param value {FormSchema}
 * @param name {string}
 * @param formData {object=} optional, formData used in reactive form to adjust schema, based on input values
 * @return {{component: Raw<unknown>, data: {optional: boolean, type}}}
 */
export const transformSchema = (value, name, formData) => {
  let schema = { ...value };
  if (typeof value === 'function') {
    schema = { type: value, optional: false };
  }
  schema.name = name;

  // every field is required by default unless
  // explicitly made optional by intent
  if (Object.hasOwn(schema, 'required') && Object.hasOwn(schema, 'optional')) {
    throw new Error(
      `Schema may contain either required or optional but never both`
    );
  }

  let required = true;
  const optionalValue = getValueOrFn(schema.optional, formData);
  const requiredValue = getValueOrFn(schema.required, formData);
  if (optionalValue === true || requiredValue === false) {
    required = false;
  }

  // resolve to the final "required" html property
  // to enable browser support in required fields
  schema.required = required;
  delete schema.optional;

  // reactive support for certain simple properties
  const values = ['readonly', 'disabled', 'defaultValue'];
  for (const propName of values) {
    if (Object.hasOwn(schema, propName)) {
      schema[propName] = getValueOrFn(schema[propName], formData);
    }
  }

  schema.formType = getFormType(schema, formData);
  schema.formElement =
    FormElements.get(schema.formType) ?? FormElements.default();
  schema.label = getLabel(schema, formData);
  schema.options = getOptions(schema, formData);
  schema.validate = getValidator(schema, formData);

  const data = ((_schema) => {
    const { type, formType, formElement, data, ...rest } = _schema;
    const value = formData ? formData.get(name) : undefined;
    return { type: formType, value, ...rest };
  })(schema);

  return {
    data,
    component: schema.formElement && markRaw(schema.formElement),
  };
};

// =====================================================
// INTERNAL
// -----------------------------------------------------
// the following functions are only exported for tests
// =====================================================

export const getValueOrFn = (value, formData) => {
  if (typeof value === 'function') {
    return value(formData);
  }
  return value;
};

export const getFormType = (
  { formType, type, options, allowedValues },
  formData
) => {
  const formTypeT = typeof formType;
  if (formTypeT === 'function') return formType(formData);
  if (formTypeT === 'string') return formType;

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

export const getLabel = ({ label, name }, formData) => {
  const typeL = typeof label;
  if (typeL === 'function') return label(formData);
  if (label === null || typeL === 'string') return label;

  return (
    name
      // remove loading whitespace
      .trim()
      // split at any single uppercase
      .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
      .replace(/([A-Z]+)([A-Z][a-z])/g, '$1 $2')
      .split(/\s+/)
      // capitalize
      .map((s) => s.charAt(0).toUpperCase() + s.slice(1))
      // construct label
      .join(' ')
  );
};

export const getOptions = ({ formType, options, allowedValues }, formData) => {
  const opts = getValueOrFn(options, formData);
  if (opts) return opts;
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

export const getValidator = (field) => {
  return (key, value) => {
    const validation = { key, value, valid: true, errors: [] };
    const addError = ({ message }) => {
      validation.valid = false;
      validation.errors.push(message);
    };
    if (!field) {
      addError({ message: `${key} is not defined in schema` });
      return validation;
    }

    const { label } = field;
    const name = label || key;

    if (field.required && !requiredValueDefined(value)) {
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
