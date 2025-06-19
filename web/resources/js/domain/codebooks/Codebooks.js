import { AbstractStore } from '../../state/AbstractStore.js';
import { createStoreRepository } from '../../state/StoreRepository.js';
import { request } from '../../utils/http/BackendRequest.js';
import { isDefined } from '@vueuse/core';

class CodebookStore extends AbstractStore {
  active(codebookId, value) {
    const entry = this.entry(codebookId);

    if (typeof value === 'boolean') {
      entry.active = !entry.active;
      return this; // for chaining
    }

    return entry.active;
  }

  init(docs) {
    if (this.size.value === 0 && docs.size !== 0) {
      docs.forEach((book) => {
        book.code_order = book.code_order ?? book.properties?.code_order ?? [];
        book.active = true;
      });
      this.add(...docs);
    }

    return { added: docs, clean: [] };
  }
}

export const Codebooks = createStoreRepository({
  key: 'store/codebooks',
  factory: (options) => new CodebookStore(options),
});

Codebooks.schemas = {};
Codebooks.schemas.create = (codebook) => ({
  name: {
    type: String,
    defaultValue: codebook?.name,
  },
  description: {
    type: String,
    formType: 'textarea',
    defaultValue: codebook?.description,
  },
  shared: {
    type: String,
    label: 'Shared with others',
    defaultValue: codebook?.properties?.sharedWithPublic ? 'public' : 'private',
    options: [
      { value: 'private', label: 'Not shared' },
      { value: 'public', label: 'Shared with public' },
    ],
  },
});
Codebooks.schemas.update = (codebook) => ({
  ...Codebooks.schemas.create(codebook),
  codebookId: {
    type: String,
    label: null,
    formType: 'hidden',
    defaultValue: codebook.id,
  },
});

Codebooks.toggle = (projectId, codebookId) => {
  const store = Codebooks.by(projectId);
  const book = store.entry(codebookId);
  const newValue = !book?.active;
  store.active(codebookId, newValue);
  return newValue;
};

Codebooks.active = (projectId, codebookId) =>
  Codebooks.by(projectId).entry(codebookId).active;

Codebooks.entries = (projectId) => Codebooks.by(projectId).all();

Codebooks.create = ({
  projectId,
  name,
  description,
  sharedWithPublic,
  sharedWithTeams,
  codebookId,
}) => {
  const body = {
    name,
    description,
    sharedWithPublic,
    sharedWithTeams,
  };
  // importing another codebook is
  // using the same endpoint with
  // additional parameters
  if (isDefined(codebookId)) {
    body.import = true;
    body.id = codebookId;
  }
  return request({
    url: '/projects/' + projectId + '/codebooks',
    type: 'post',
    body,
  });
};

Codebooks.update = ({
  projectId,
  codebookId,
  name,
  description,
  sharedWithPublic,
  sharedWithTeams,
}) => {
  const body = {};
  if (name) {
    body.name = name;
  }
  if (description) {
    body.description = description;
  }
  if (isDefined(sharedWithTeams)) {
    body.sharedWithTeams = sharedWithTeams;
  }
  if (isDefined(sharedWithPublic)) {
    body.sharedWithPublic = sharedWithPublic;
  }

  return request({
    url: `/projects/${projectId}/codebooks/${codebookId}`,
    type: 'patch',
    body,
  });
};

Codebooks.importFromFile = ({ projectId, file }) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('project_id', projectId);
  const uploadUrl = route('codebook-codes.import', { project: projectId });
  return request({
    url: uploadUrl,
    type: 'post',
    body: formData,
    headers: { 'Content-Type': 'multipart/form-data' },
  });
};

Codebooks.delete = ({ projectId, codebookId }) => {
  return request({
    url: `/projects/${projectId}/codebooks/${codebookId}`,
    type: 'delete',
  });
};

/**
 * Requests the server to update the code order after sorting
 * @param projectId {string}
 * @param codebookId {string}
 * @param order {object[]}
 * @return {Promise<BackendRequest>}
 */
Codebooks.order = ({ projectId, codebookId, order }) => {
  const url = route('codebook-codes.update-order', {
    project: projectId,
    codebook: codebookId,
  });
  return request({
    url,
    type: 'patch',
    body: { code_order: order },
  });
};
