export const readFromFile = (file, as) =>
  new Promise((resolve, reject) => {
    if (!(file instanceof File))
      return reject(new Error('A File is required to read text'));
    const reader = new FileReader();
    reader.addEventListener('error', reject);
    reader.addEventListener('load', () => resolve(reader.result), false);

    switch (as) {
      case 'binaryString':
        return reader.readAsBinaryString(file);
      case 'dataUrl':
        return reader.readAsDataURL(file);
      case 'arrayBuffer':
        return reader.readAsArrayBuffer(file);
      case 'text':
      default:
        return reader.readAsText(file);
    }
  });
