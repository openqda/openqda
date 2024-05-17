import FileSaver from 'file-saver';
export const saveTextFile = async ({
  text,
  name,
  type = 'text/plain',
  encoding = 'utf-8',
}) => {
  const options = { type: `${type};charset=${encoding}` };
  const blob = new Blob([text], options);
  FileSaver.saveAs(blob, name);
};
