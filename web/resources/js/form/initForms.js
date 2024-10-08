import { FormElements } from './FormElements.js';
import TextInput from './TextInput.vue';
import TextArea from './TextArea.vue';

let initialized = false;

export const initForms = () => {
  if (!initialized) {
    FormElements.register('textarea', TextArea);
    FormElements.register('default', TextInput);
    initialized = true;
  }
};
