import Quill from 'quill';

// Undo and redo functions for Custom Toolbar
export const undoChange = function undoChange() {
  this.quill.history.undo();
};
export const redoChange = function redoChange() {
  this.quill.history.redo();
};

// Add sizes to whitelist and register them
const Size = Quill.import('formats/size');
Size.whitelist = ['extra-small', 'small', 'medium', 'large'];
Quill.register(Size, true);

// Add fonts to whitelist and register them
const FontAttributor = Quill.import('formats/font');
FontAttributor.whitelist = [
  'arial',
  'comic-sans',
  'courier-new',
  'georgia',
  'helvetica',
  'lucida',
];
Quill.register(FontAttributor, true);

// Formats objects for setting up the Quill editor
export const formats = [
  'header',
  'font',
  'size',
  'bold',
  'italic',
  'underline',
  'align',
  'strike',
  'script',
  'blockquote',
  'background',
  'list',
  'bullet',
  'indent',
  'link',
  'image',
  'color',
  'code-block',
];
