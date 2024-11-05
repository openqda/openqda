import { FormElements } from './FormElements.js';
import TextInput from './TextInput.vue';
import TextArea from './TextArea.vue';
import SelectField from './SelectField.vue';

let initialized = false;

export const initForms = () => {
  if (!initialized) {
    FormElements.register('select', SelectField);
    FormElements.register('textarea', TextArea);
    FormElements.register('default', TextInput);
    initialized = true;
  }
};
