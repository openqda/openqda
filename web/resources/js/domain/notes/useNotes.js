import { usePage } from '@inertiajs/vue3';
import { computed, reactive, toRefs } from 'vue';
import { request } from '../../utils/http/BackendRequest.js';
import { Notes } from './NoteStore.js';
import { useUsers } from '../teams/useUsers.js';

const state = reactive({
  loading: false,
});

export const useNotes = () => {
  const { allUsers } = useUsers();
  const { loading } = toRefs(state);
  const page = usePage();
  const { projectId: rawProjectId, notes, source, project } = page.props;
  const sourceId = source?.id;
  const projectId = rawProjectId ? rawProjectId : project.id;
  const key = sourceId ? `${projectId}-${sourceId}` : projectId;
  const noteStore = Notes.by(key);

  const initNotes = () => {
    return noteStore.init(notes, allUsers);
  };

  const allNotes = computed(() => {
    return noteStore.all();
  });

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
    noteStore.update(data.id, response.data.note);
    return { response, error };
  };

  const deleteNote = async (note, target) => {
    const noteId = note.id;
    const { error, response } = await request({
      url: `/projects/${projectId}/notes/${noteId}`,
      type: 'DELETE',
    });

    onError({ response, error, message: 'Failed to delete notes.' });
    target.notes = target.notes ?? [];
    target.notes.splice(
      target.notes.findIndex((n) => n.id === noteId),
      1
    );
    noteStore.remove(noteId);

    return { error, response };
  };

  return {
    notes: allNotes,
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

const createNoteSchema = ({ target, type, scope, labels }) => {
  const showLabels = labels !== false;
  return {
    type: {
      type: String,
      formType: 'hidden',
      label: null,
      optional: false,
      defaultValue: type,
    },
    target: {
      type: String,
      formType: 'hidden',
      label: null,
      optional: false,
      defaultValue: target,
    },
    scope: {
      type: String,
      label: null,
      formType: 'hidden',
      optional: true,
      defaultValue: scope,
    },
    content: {
      type: String,
      label: showLabels ? undefined : null,
      formType: 'textarea',
      autofocus: true,
      optional: false,
      placeholder: '...write your note here',
    },
    visibility: {
      type: Number,
      label: showLabels ? 'Visibility of this note' : null,
      formType: 'select',
      optional: false,
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

const editNoteSchema = ({ id, content, visibility, labels }) => {
  const showLabels = labels !== false;
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
      label: showLabels ? undefined : null,
      defaultValue: content,
      autofocus: true,
      placeholder: '...write your note here',
    },
    visibility: {
      type: Number,
      label: showLabels ? 'Visibility of this note' : null,
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
