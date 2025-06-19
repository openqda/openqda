import { AbstractStore } from '../../state/AbstractStore.js';
import { createStoreRepository } from '../../state/StoreRepository.js';
import { request } from '../../utils/http/BackendRequest.js';
import { randomUUID } from '../../utils/random/randomUUID.js';
import { toRaw } from 'vue';

class CodeStore extends AbstractStore {
  withChildren(codeId) {
    const codes = [];
    const code = this.entry(codeId);
    codes.push(code);
    if (code.children?.length) {
      code.children.forEach((child) => {
        codes.push(...this.withChildren(child.id));
      });
    }
    return codes;
  }

  toggle(codeId) {
    const entry = this.entry(codeId);
    return this.active(codeId, !entry.active);
  }

  active(codeId, value) {
    const codes = this.withChildren(codeId);
    codes.forEach((code) => {
      code.active = value;
    });
    this.observable.run('updated', codes);
    this.observable.run('changed', { type: 'updated', docs: codes });
    return codes;
  }

  init(docs) {
    const codeList = [];
    const toClean = [];
    if (this.size.value === 0 && docs.length > 0) {
      const parseCodes = (codes, parent = null) => {
        codes.forEach((code) => {
          if (typeof code.codebook === 'undefined' || code.codebook === null) {
            toClean.push({
              id: code.id,
              name: code.name,
              ref: code.codebook,
              type: 'code',
              reason: 'Linked codebook not found.',
              actions: [
                {
                  name: 'Delete Code',
                  type: 'delete',
                  fn: () => alert('not implemented :-('),
                },
              ],
            });
          } else {
            code.active = true;
            code.parent = parent;
            code.order = getOrder(code.codebook);
            code.order = getOrder(code.codebook);

            codeList.push(code);
            if (code.children?.length) {
              parseCodes(code.children, code);
            }
          }
        });
      };
      parseCodes(docs);
      this.add(...codeList);
    }
    return { added: codeList, clean: toClean };
  }
}

const orders = {};
const getOrder = (codebook) => {
  if (!(codebook in orders)) {
    orders[codebook] = 0;
  }
  return orders[codebook]++;
};

export const Codes = createStoreRepository({
  key: 'store/codes',
  factory: (options) => new CodeStore(options),
});

Codes.create = async ({
  projectId,
  source,
  title,
  name,
  description,
  codebookId,
  parentId,
  color,
}) => {
  const store = Codes.by(`${projectId}-${source.id}`);
  const code = {
    id: randomUUID(),
    text: [],
    color,
    active: true, // TODO respect coebook active state
    description,
    editable: true,
    codebook: parseInt(codebookId, 10), //somehow int works and not string
    title, // backwards-compat
    name: name ?? title,
    children: [],
    order: toRaw(store.size.value),
  };

  const body = { ...code };
  if (parentId) {
    body.parent_id = parentId;
  }

  const { response, error } = await request({
    url: `/projects/${projectId}/codes`,
    type: 'post',
    body,
  });

  if (!error && response?.status < 400) {
    code.id = response.data.id;
    if (parentId) {
      code.parent = store.entry(parentId);
      code.active = code.parent.active;
      code.parent.children.push(code);
    }

    store.add(code);
  }
  return { response, error, code };
};

Codes.delete = ({ projectId, source, code }) => {
  return request({
    url: `/projects/${projectId}/sources/${source.id}/codes/${code.id}`,
    type: 'delete',
  });
};

Codes.update = ({
  projectId,
  code,
  title,
  name,
  description,
  color,
  parent,
}) => {
  const body = {};
  if (title || name) {
    body.title = title ?? name;
  }
  if (description) body.description = description;
  if (color) body.color = color;
  if (parent) body.parent_id = toRaw(parent.id);
  if (parent === null) body.parent_id = null;

  return request({
    url: `/projects/${projectId}/codes/${code.id}`,
    type: 'patch',
    body,
  });
};

Codes.removeParent = ({ projectId, code, source }) => {
  return request({
    url: `/projects/${projectId}/sources/${source.id}/codes/${code.id}/remove-parent`,
    type: 'post',
  });
};

Codes.upHierarchy = ({ projectId, source, code }) => {
  return request({
    url: `/projects/${projectId}/sources/${source.id}/codes/${code.id}/up-hierarchy`,
    type: 'post',
  });
};

Codes.addChild = ({ projectId, child }) => {
  /**
     id: localId,
     title: title,
     color: currentCode.color,
     codebook: currentCode.codebook,
     children: [],
     editable: true, // make the title of the code editable already
     showText: false,
     dropdownOpen: false,
     justOpened: false,
     text: [],
     parent_id: currentCode.id,
     description: '',
     showEditDescription: false,
     showDescription: false,
     */
  return request({
    url: `/projects/${projectId}/codes`,
    type: 'post',
    body: child,
  });
};

Codes.sort = (a, b) => Number(a.active) - Number(b.active);
