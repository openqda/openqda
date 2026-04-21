import { usePage } from '@inertiajs/vue3';
import { reactive, toRefs } from 'vue';
import { request } from '../../utils/http/BackendRequest.js';
import { Notes } from './NoteStore.js';

const state = reactive({
  loading: false,
});

export const useNotes = () => {
  const { loading } = toRefs(state);
  const page = usePage();
  const { projectId, notes, auth, teamMembers, source } = page.props;
  const allUsers = [auth.user].concat(teamMembers);
  const sourceId = source?.id;
  const key = sourceId ? `${projectId}-${sourceId}` : projectId;
  const noteStore = Notes.by(key);

  const initNotes = () => {
    return noteStore.init(notes, allUsers);
  };

  const onError = ({ response, error, message }) => {
    if (response.status >= 400 || error) {
      throw new Error(
        `${response.status} ${message} ${response.data?.message || error.message}`
      );
    }
  };

  const fetchNotes = async ({ types }) => {
    loading.value = true;
    let body = undefined;
    if (types) {
      body = { types };
    }

    const { error, response } = await request({
      url: `/projects/${projectId}/notes`,
      type: 'GET',
      body,
    });

    onError({ response, error, message: 'Failed to load notes.' });
    return { error, response };
  };

  const createNote = async (data, target) => {
    const payload = {
      ...data,
      target: target.id,
      projectId,
    };
    const { error, response } = await request({
      url: `/projects/${projectId}/notes`,
      type: 'POST',
      body: payload,
    });

    onError({ response, error, message: 'Failed to create notes.' });
    target.notes = target.notes ?? [];
    target.notes.push(response.data.note);
    noteStore.add(response.data.note);
    return { error, response };
  };

  const updateNote = async (data, target) => {
    const { error, response } = await request({
      url: `/projects/${projectId}/notes/${data.id}`,
      type: 'PATCH',
      body: data,
    });
    onError({ response, error, message: 'Failed to update note.' });
    target.notes = target.notes ?? [];
    const idx = target.notes.findIndex((n) => n.id === response.data.note.id);
    if (idx > -1) {
      target.notes[idx] = response.data.note;
    }
    return { response, error };
  };

  const deleteNote = async (note, target) => {
    const noteId = note.id;
    const { error, response } = await request({
      url: `/projects/${projectId}/notes/${noteId}`,
      type: 'DELETE',
    });

    if (response.status >= 400 || error) {
      throw new Error(
        `${response.status} Failed to create note. ${response.error.message}`
      );
    } else {
      target.notes = target.notes ?? [];
      target.notes.splice(
        target.notes.findIndex((n) => n.id === noteId),
        1
      );
      noteStore.remove(noteId);
    }

    return { error, response };
  };

  return {
    notes,
    loading,
    initNotes,
    createNoteSchema,
    editNoteSchema,
    fetchNotes,
    createNote,
    updateNote,
    deleteNote,
  };
};

const createNoteSchema = ({ target, type, scope }) => {
  return {
    type: {
      type: String,
      formType: 'hidden',
      label: null,
      defaultValue: type,
    },
    target: {
      type: String,
      formType: 'hidden',
      label: null,
      defaultValue: target,
    },
    scope: {
      type: String,
      label: null,
      formType: 'hidden',
      defaultValue: scope,
    },
    content: {
      type: String,
      formType: 'textarea',
      placeholder: '...write your note here',
    },
    visibility: {
      type: Number,
      label: 'Visibility of this note',
      formType: 'select',
      defaultValue: 0,
      options: [
        {
          value: 0,
          label: 'Only I can see this note',
        },
        {
          value: 1,
          label: 'Team members can see this note',
        },
      ],
    },
  };
};

const editNoteSchema = ({ id, content, visibility }) => {
  return {
    id: {
      type: String,
      formType: 'hidden',
      label: null,
      defaultValue: id,
    },
    content: {
      type: String,
      formType: 'textarea',
      defaultValue: content,
      autofocus: true,
      placeholder: '...write your note here',
    },
    visibility: {
      type: Number,
      label: 'Visibility of this note',
      formType: 'select',
      defaultValue: visibility,
      options: [
        {
          value: 0,
          label: 'Only I can see this note',
        },
        {
          value: 1,
          label: 'Team members can see this note',
        },
      ],
    },
  };
};
