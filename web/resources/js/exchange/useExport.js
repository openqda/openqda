import { createCSVBuilder } from '../utils/files/createCSVBuilder.js';
import { saveTextFile } from '../utils/files/saveTextFile.js';
import { usePage } from '@inertiajs/vue3';
import { toLocaleDateString } from '../utils/date/toLocaleDateString.js';
import { buildNotesCSV } from './buildNotesCSV.js';

/**
 * Export hook for exporting project data.
 * @return {{exportToCSV: (function({contents: *, users: *}): Promise<void>), exportNotesToCSV: (function({notes: *, codes: *, sources: *, users: *}): Promise<void>)}}
 */
export const useExport = () => {
  const { project } = usePage().props;
  return {
    exportToCSV: ({ contents, users }) =>
      exportToCSV({ contents, project, users }),
    exportNotesToCSV: ({ notes, codes, sources, users }) =>
      exportNotesToCSV({ notes, codes, sources, project, users }),
    exportVariables,
  };
};

const exportNotesToCSV = ({ notes, codes, sources, project, users }) => {
  const out = buildNotesCSV({ notes, codes, sources, project, users });
  const date = new Date().toLocaleDateString().replace(/[_.:,\s]+/g, ' ');
  const name = `OpenQDA ${project.name} Notes ${date}.csv`;
  return saveTextFile({ text: out, name, type: 'text/csv' });
};

const exportVariables = ({ sources, variables, users, project }) => {
  const header = ['name'];
  const csv = createCSVBuilder({ header });

  const type = ['datatype'];
  const description = ['description'];
  const createdAt = ['created_at'];
  const updatedAt = ['updated_at'];
  const createdBy = ['created_by'];
  for (const v of variables) {
    header.push(v.name);
    type.push(v.type_of_variable);
    description.push(v.description ?? '');
    createdAt.push(toLocaleDateString(v.created_at));
    updatedAt.push(toLocaleDateString(v.updated_at));
    const user = users[v.created_by];
    createdBy.push(user?.name ?? user?.id ?? v.created_by);
  }

  csv.addRow(type);
  csv.addRow(description);
  csv.addRow(createdAt);
  csv.addRow(createdBy);
  csv.addRow(updatedAt);

  for (const s of sources) {
    const bySource = [s.name];
    for (const v of variables) {
      const value = String(s.variables?.[v.name] ?? '');
      bySource.push(value);
    }
    csv.addRow(bySource);
  }
  const out = csv.build();
  const date = new Date().toLocaleDateString().replace(/[_.:,\s]+/g, ' ');
  const name = `OpenQDA ${project.name} Variables ${date}.csv`;
  return saveTextFile({
    text: out,
    name: name,
    type: 'text/csv',
  });
};

const exportToCSV = ({ contents, project, users }) => {
  const csv = createCSVBuilder({
    header: [
      'source',
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
        const user = users[selection.createdBy];
        csv.addRow([
          /* file */ entry.name,
          /* code category */ code.name,
          /* created by */ user?.name ?? user?.id ?? selection?.createdBy ?? '',
          /* created at */ toLocaleDateString(selection.createdAt),
          toLocaleDateString(
            selection.updatedAt !== selection.createdAt
              ? selection.updatedAt
              : ''
          ),
          selection.start,
          selection.end,
          selection.text,
        ]);
      });
    });
  });
  const out = csv.build();
  const date = new Date().toLocaleDateString().replace(/[_.:,\s]+/g, ' ');
  const name = `OpenQDA ${project.name} Selections ${date}.csv`;
  return saveTextFile({
    text: out,
    name: name,
    type: 'text/csv',
  });
};
