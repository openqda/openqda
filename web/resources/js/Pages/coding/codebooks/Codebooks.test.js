import { Codebooks } from './Codebooks.js';
import { describe, it, expect } from 'vitest';
import { randomUUID } from '../../../utils/random/randomUUID.js';

describe('Codebooks (ctx)', () => {
  let codebook;

  beforeEach(() => {
    codebook = {
      id: randomUUID(),
      name: randomUUID(),
      description: randomUUID(),
      sharedWithPublic: false,
      sharedWithTeams: false,
    };
  });

  describe('schemas', () => {
    describe('create', () => {
      it('creates a new schema by given codebook', () => {
        const schema = Codebooks.schemas.create(codebook);
        expect(schema).toEqual({
          name: {
            type: String,
            defaultValue: codebook.name,
          },
          description: {
            type: String,
            formType: 'textarea',
            defaultValue: codebook.description,
          },
          shared: {
            type: String,
            label: 'Shared with others',
            defaultValue: 'private',
            options: [
              { value: 'private', label: 'Not shared' },
              { value: 'teams', label: 'Shared with teams' },
              { value: 'public', label: 'Shared with public' },
            ],
          },
        });
      });
    });
    describe('update', () => {
      it('creates a updated schema by given codebook', () => {
        const schema = Codebooks.schemas.update(codebook);
        expect(schema).toEqual({
          name: {
            type: String,
            defaultValue: codebook.name,
          },
          description: {
            type: String,
            formType: 'textarea',
            defaultValue: codebook.description,
          },
          shared: {
            type: String,
            label: 'Shared with others',
            defaultValue: 'private',
            options: [
              { value: 'private', label: 'Not shared' },
              { value: 'teams', label: 'Shared with teams' },
              { value: 'public', label: 'Shared with public' },
            ],
          },
          codebookId: {
            type: String,
            label: null,
            formType: 'hidden',
            defaultValue: codebook.id,
          },
        });
      });
    });
  });
  describe('toggle', () => {
    test.todo('not yet implemented');
  });
  describe('active', () => {
    test.todo('not yet implemented');
  });
  describe('entries', () => {
    test.todo('not yet implemented');
  });
  describe('create', () => {
    test.todo('not yet implemented');
  });
  describe('update', () => {
    test.todo('not yet implemented');
  });
  describe('importFromFile', () => {
    test.todo('not yet implemented');
  });
  describe('delete', () => {
    test.todo('not yet implemented');
  });
});
