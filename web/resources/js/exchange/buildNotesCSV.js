import { createCSVBuilder } from '../utils/files/createCSVBuilder.js';
import { toLocaleDateString } from '../utils/date/toLocaleDateString.js';

/**
 * Builds a CSV string from a project's notes.
 * Resolves the human-readable name of whatever each note is attached to
 * (a code, source, or the project itself) by cross-referencing the provided
 * codes and sources lists.
 *
 * @param {object} options
 * @param {object[]} options.notes - array of note objects
 * @param {object[]} [options.codes=[]] - array of code objects with id and name
 * @param {object[]} [options.sources=[]] - array of source objects with id and name
 * @param {object} options.project - project object with name
 * @param {object} [options.users={}] - map of userId -> user object
 * @return {string} CSV string
 */
export const buildNotesCSV = ({
  notes,
  codes = [],
  sources = [],
  project,
  users = {},
}) => {
  const codeMap = Object.fromEntries(codes.map((c) => [c.id, c.name]));
  const sourceMap = Object.fromEntries(sources.map((s) => [s.id, s.name]));

  const csv = createCSVBuilder({
    header: [
      'attached to',
      'name',
      'created by',
      'created at',
      'visibility',
      'note',
    ],
  });

  notes.forEach((note) => {
    let targetName = '';
    if (note.type === 'code') {
      targetName = codeMap[note.target] ?? note.target ?? '';
    } else if (note.type === 'source') {
      targetName = sourceMap[note.target] ?? note.target ?? '';
    } else if (note.type === 'project') {
      targetName = project?.name ?? '';
    } else {
      targetName = note.target ?? '';
    }

    const user = note.user ?? users[note.creating_user_id];
    csv.addRow([
      note.type ?? '',
      targetName,
      user?.name ?? '',
      toLocaleDateString(note.created_at),
      note.visibility === 1 ? 'team' : 'private',
      note.content ?? '',
    ]);
  });

  return csv.build();
};
