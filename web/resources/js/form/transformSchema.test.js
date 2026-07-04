import { describe, expect, vi } from 'vitest';
import {
  getLabel,
  getOptions,
  transformSchema,
  getFormType,
  getValidator,
} from './transformSchema.js';

describe('transform schema', () => {
  describe(getLabel.name, () => {
    it('returns a given label', () => {
      const label = 'moo';
      expect(getLabel({ label })).toEqual('moo');
    });
    it('derives from function', () => {
      const ctx = { moo: 1 };
      const label = vi.fn((formData) => {
        expect(formData).toBe(ctx);
        return 'foo';
      });
      expect(getLabel({ label }, ctx)).toEqual('foo');
      expect(label).toHaveBeenCalled();
    });
    it('suppresses label with null', () => {
      const label = null;
      expect(getLabel({ label })).toEqual(null);
    });
    it('creates a humanized fallback from field name', () => {
      const names = [
        ['foo', 'Foo'],
        ['barBaz', 'Bar Baz'],
        [' foo ', 'Foo'],
        [' barBaz ', 'Bar Baz'],
        [' barXMLParser ', 'Bar XML Parser'],
      ];
      for (const [name, expected] of names) {
        expect(getLabel({ name })).toEqual(expected);
      }
    });
  });
  describe(getOptions.name, () => {
    it('skips if allowed values are defined', () => {
      const allowedValues = [{ foo: 1 }, { bar: 1 }];
      expect(getOptions({ allowedValues })).toEqual(undefined);
    });
    it('resolved a function to options', () => {
      const expected = [{ foo: 1 }, { bar: 1 }];
      const ctx = { moo: 1 };
      const options = vi.fn((formData) => {
        expect(formData).toBe(ctx);
        return expected;
      });
      expect(getOptions({ options }, ctx)).toStrictEqual(expected);
      expect(options).toHaveBeenCalled();
    });
    it('returns options if already given', () => {
      const options = [{ foo: 1 }, { bar: 1 }];
      expect(getOptions({ options })).toStrictEqual(options);
    });
    it('resolves from formType', () => {
      const types = [
        [
          'select-radio',
          [
            {
              value: true,
              label: 'Yes',
            },
            {
              value: false,
              label: 'No',
            },
          ],
        ],
      ];
      for (const [formType, expected] of types) {
        expect(getOptions({ formType })).toStrictEqual(expected);
      }
    });
  });
  describe(getFormType.name, () => {
    it('throws on unknown type', () => {
      const schema = { type: 'test' };
      expect(() => getFormType(schema)).toThrow(`unknown type test`);
    });
    it('passes on formType', () => {
      const schema = { formType: 'test' };
      expect(getFormType(schema)).toEqual('test');
    });
    it('creates a select from options', () => {
      const schemas = [{ options: [] }, { allowedValues: [] }];
      for (const schema of schemas) {
        expect(getFormType(schema)).toEqual('select');
      }
    });
    it('creates a type from constructor', () => {
      const values = [
        [String, 'text'],
        [Number, 'number'],
        [Boolean, 'checkbox'],
      ];
      for (const [type, expected] of values) {
        expect(getFormType({ type })).toBe(expected);
      }
    });
    it('resolves a function formType', () => {
      const ctx = { moo: 1 };
      const formType = vi.fn((formData) => {
        expect(formData).toBe(ctx);
        return 'moo';
      });
      const schema = { formType };
      expect(getFormType(schema, ctx)).toEqual('moo');
      expect(formType).toHaveBeenCalled();
    });
  });
  describe(getValidator.name, () => {
    const evaluate = (result, expected) => {
      const { key, value, valid, errors } = result;
      expect(key).toEqual(expected.key);
      expect(value).toEqual(expected.value);
      expect(errors).toStrictEqual(expected.errors);
      expect(valid).toEqual(expected.valid);
    };
    it('fails if key is not in schema', () => {
      const validate = getValidator();
      evaluate(validate('moo'), {
        key: 'moo',
        value: undefined,
        valid: false,
        errors: ['moo is not defined in schema'],
      });
    });
    it('fails if key is required but required value is not defined', () => {
      const schema = {
        type: String,
        label: 'Moo',
        required: true,
      };
      const validate = getValidator(schema);
      evaluate(validate('moo'), {
        key: 'moo',
        value: undefined,
        valid: false,
        errors: ['Moo is required'],
      });
    });
  });
  describe(transformSchema.name, () => {
    it('transforms a most minimal schema to a normalized version', () => {
      const schema = { type: String };
      const normalized = transformSchema(schema, 'foo');
      expect(normalized.component).toBe(undefined);

      const { validate, ...data } = normalized.data;
      expect(data).toStrictEqual({
        label: 'Foo',
        name: 'foo',
        required: true,
        options: undefined,
        type: 'text',
        value: undefined,
      });
    });
    it('transforms with allowed values', () => {
      const schema = { type: String, allowedValues: ['moo', 'foo'] };
      const normalized = transformSchema(schema, 'foo');
      expect(normalized.component).toBe(undefined);

      const { validate, ...data } = normalized.data;
      expect(data).toStrictEqual({
        label: 'Foo',
        name: 'foo',
        required: true,
        options: undefined,
        allowedValues: ['moo', 'foo'],
        type: 'select',
        value: undefined,
      });
    });
    it('transforms with resolver functions', () => {
      const data = {
        optional: true,
        formType: 'foobar',
        label: 'Foo XML Parser',
        disabled: true,
        options: [{ moo: 1 }, { foo: 2 }],
      };
      const schema = {
        type: String,
        optional: () => data.optional,
        formType: () => data.formType,
        label: () => data.label,
        disabled: () => data.disabled,
        options: () => data.options,
      };

      const formData = new FormData();
      formData.set('foo', 'moo');
      const normalized = transformSchema(schema, 'foo', formData);
      expect(normalized.component).toBe(undefined);

      const { validate, ...field } = normalized.data;
      expect(field).toStrictEqual({
        label: 'Foo XML Parser',
        name: 'foo',
        required: false,
        options: [{ moo: 1 }, { foo: 2 }],
        type: 'foobar',
        disabled: true,
        value: 'moo',
      });
    });
  });
});
