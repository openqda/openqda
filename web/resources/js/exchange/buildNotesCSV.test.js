import { describe, expect, test } from 'vitest';
import { buildNotesCSV } from './buildNotesCSV.js';

const project = { name: 'Test Project' };

describe('buildNotesCSV', () => {
  test('produces only a header row when there are no notes', () => {
    const result = buildNotesCSV({ notes: [], project });
    expect(result).toBe(
      'note;attached to;name;created by;created at;visibility\n'
    );
  });

  test('resolves the name of the code a note is attached to', () => {
    const notes = [
      {
        content: 'Interesting quote',
        type: 'code',
        target: 'code-1',
        creating_user_id: 'user-1',
        user: { name: 'Alice' },
        created_at: null,
        visibility: 0,
      },
    ];
    const codes = [{ id: 'code-1', name: 'Drinking' }];
    const result = buildNotesCSV({ notes, codes, project });
    expect(result).toContain('Drinking');
    expect(result).toContain('Interesting quote');
  });

  test('resolves the name of the source a note is attached to', () => {
    const notes = [
      {
        content: 'About this file',
        type: 'source',
        target: 'src-1',
        creating_user_id: 'user-1',
        user: { name: 'Bob' },
        created_at: null,
        visibility: 1,
      },
    ];
    const sources = [{ id: 'src-1', name: 'breakfast_recording.txt' }];
    const result = buildNotesCSV({ notes, sources, project });
    expect(result).toContain('breakfast_recording.txt');
  });

  test('uses the project name for project-scoped notes', () => {
    const notes = [
      {
        content: 'Overall thought',
        type: 'project',
        target: 'proj-1',
        creating_user_id: 'user-1',
        user: { name: 'Carol' },
        created_at: null,
        visibility: 0,
      },
    ];
    const result = buildNotesCSV({ notes, project });
    expect(result).toContain('Test Project');
  });

  test('marks private notes as "private" and team notes as "team"', () => {
    const notes = [
      {
        content: 'Private note',
        type: 'project',
        target: 'proj-1',
        user: { name: 'Alice' },
        created_at: null,
        visibility: 0,
      },
      {
        content: 'Team note',
        type: 'project',
        target: 'proj-1',
        user: { name: 'Alice' },
        created_at: null,
        visibility: 1,
      },
    ];
    const result = buildNotesCSV({ notes, project });
    expect(result).toContain('private');
    expect(result).toContain('team');
  });

  test('falls back to users map when note has no embedded user object', () => {
    const notes = [
      {
        content: 'A note',
        type: 'project',
        target: 'proj-1',
        creating_user_id: 'user-42',
        created_at: null,
        visibility: 0,
      },
    ];
    const users = { 'user-42': { name: 'Dave' } };
    const result = buildNotesCSV({ notes, project, users });
    expect(result).toContain('Dave');
  });
});
