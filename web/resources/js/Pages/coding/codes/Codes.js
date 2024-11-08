import { AbstractStore } from '../../../state/AbstractStore.js';
import { createStoreRepository } from '../../../state/StoreRepository.js';
import { request } from '../../../utils/http/BackendRequest.js';
import { randomUUID } from '../../../utils/randomUUID.js';

class CodeStore extends AbstractStore {
  toggle(codeId) {
    const entry = this.entry(codeId);
    return this.active(codeId, !entry.active);
  }

  active(codeId, value) {
    const docs = [];
    const setActive = (id) => {
      const entry = this.entry(id);
      entry.active = value;
      docs.push(entry);
      if (entry.children?.length) {
        entry.children.forEach((child) => setActive(child.id));
      }
      this.observable.run('updated', docs);
      this.observable.run('changed', { type: 'updated', docs });
    };
    setActive(codeId);
    return docs;
  }

  init(docs) {
      if (this.size.value === 0 && docs.length > 0) {
          const codeList = []
          const parseCodes = (codes, parent = null) => {
              codes.forEach((code) => {
                  code.active = true
                  code.parent = parent
                  code.order = getOrder(code.codebook)
                  code.order = getOrder(code.codebook)

                  codeList.push(code)
                  if (code.children?.length) {
                      parseCodes(code.children, code)
                  }
              })
          }
          parseCodes(docs)
          this.add(...codeList)
      }
  }
}

const orders = {}
const getOrder = (codebook) => {
    if (!(codebook in orders)) {
        orders[codebook] = 0
    }
    return orders[codebook]++
}

export const Codes = createStoreRepository({
  key: 'store/codes',
  factory: (options) => new CodeStore(options),
});

Codes.create = async ({ projectId, source, title, name, description, codebookId, color }) => {
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
    order: store.size
  };
  store.add(code);
  console.debug('create new code', code)
  const { response, error } = await request({
    url: `/projects/${projectId}/codes`,
    type: 'post',
    body: code,
  });

  if (response.status >= 400 || error) {
    store.remove(code.id);
  } else {
    store.update(code.id, { id: response.data.id }, { updateId: true });
  }

  return { response, error, code };
};

Codes.delete = ({ projectId, source, code }) => {
  return request({
    url: `/projects/${projectId}/sources/${source.id}/codes/${code.id}`,
    type: 'delete',
  });
};

Codes.updateTitle = ({ projectId, code, title, name }) => {
  return request({
    url: `/projects/${projectId}/codes/${code.id}/update-title`,
    type: 'post',
    body: { title: title ?? name },
  });
};

Codes.updateColor = ({ projectId, code, color }) => {
  return request({
    url: `/projects/${projectId}/codes/${code.id}/update-color`,
    type: 'post',
    body: { color },
  });
};

Codes.updateDescription = ({ projectId, source, code, description }) => {
  return request({
    url: `/projects/${projectId}/sources/${source.id}/codes/${code.id}/description`,
    type: 'post',
    body: { description },
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
