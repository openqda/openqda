export const FormElements = {};
const elements = new Map();

FormElements.register = (name, component) => elements.set(name, component);

FormElements.get = (name) => elements.get(name);

FormElements.default = () => elements.get('default');

FormElements.size = () => elements.size;
