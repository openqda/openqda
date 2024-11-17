import { createCSVBuilder } from '../utils/files/createCSVBuilder.js';
import { saveTextFile } from '../utils/files/saveTextFile.js';
import { usePage } from '@inertiajs/vue3';

export const useExport = () => {
  const { project } = usePage().props;

  return {
    exportToCSV: ({ contents, users }) => exportToCSV({ contents, project, users }),
  };
};

const exportToCSV = ({ contents, project, users }) => {
  const doubleQuote = /"/g;
  const quote = "'";
  const whitespace = /\s+/g;
  const csv = createCSVBuilder({
    header: [
      'file',
      'code category',
      'created by',
      'created at',
      'last update',
      'start pos',
      'end pos',
      'selection',
    ],
  });
  contents.forEach((entry) => {
    entry.codes.forEach((code) => {
      code.segments.forEach((selection) => {
        const user = users[selection.createdBy]
        csv.addRow([
          entry.name,
          code.name,
          user?.name
              ? user.name
              : selection.createdBy,
          selection.createdAt,
          selection.updatedAt !== selection.createdAt
            ? selection.updatedAt
            : '',
          selection.start,
          selection.end,
          `"${selection.text.replace(doubleQuote, quote).replace(whitespace, ' ')}"`,
        ]);
      });
    });
  });

  const out = csv.build();
  const date = new Date().toLocaleDateString().replace(/[_.:,\s]+/g, ' ');
  const name = `${project.name} ${date}.csv`;
  return saveTextFile({
    text: out,
    name: name,
    type: 'text/csv',
  });
};
