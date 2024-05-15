import { randomHex } from './utils.js';

export class WebStorageHandler {
  constructor(webStorage, options = {}) {
    this.storage = webStorage;
    this.idGen = options.idGen || randomHex;
  }

  async fetch(query, options) {
    // we ignore query in these tests
    const { name, primary } = options;
    const docs = fromStorage({ name, storage: this.storage });
    return docs.filter((doc) => primary in doc);
  }

  async insert(documents, options) {
    const { name, primary } = options;
    const { storage } = this;
    const docs = fromStorage({ name, storage });
    const primaries = [];

    documents.forEach((doc) => {
      const key = this.idGen(8);
      doc[primary] = key;
      docs.push(doc);
      primaries.push(key);
    });

    toStorage({ docs, name, storage });
    return primaries;
  }

  async update(documents, modifier, options, updated) {
    const { name, primary } = options;
    const { storage } = this;
    const docs = fromStorage({ name, storage });

    updated.forEach((doc) => {
      const target = docs.findIndex((d) => d[primary] === doc[primary]);
      if (target > -1) {
        docs[target] = doc;
      }
    });

    toStorage({ docs, name, storage });
    return updated;
  }

  async remove(documents, options, removed) {
    const { name, primary } = options;
    const { storage } = this;
    const docs = fromStorage({ name, storage });

    documents.forEach((doc) => {
      // iterate from end to start
      // to avoid index-issues after splice
      for (let i = docs.length - 1; i >= 0; i--) {
        const d = docs[i];
        if (doc[primary] === d[primary]) {
          docs.splice(i, 1);
        }
      }
    });

    toStorage({ docs, storage, name });
    return removed;
  }
}

const toStorage = ({ docs = [], storage, name }) => {
  let str;
  try {
    str = JSON.stringify(docs, null, 0);
  } catch (e) {
    console.error(e);
    str = '[]';
  }
  storage.setItem(name, str);
};

const fromStorage = ({ name, storage }) => {
  const docsStr = storage.getItem(name) || '[]';
  let docs;

  try {
    docs = JSON.parse(docsStr) || [];
    if (!Array.isArray(docs)) {
      throw new Error(`Expected array, got ${docs}`);
    }
  } catch (e) {
    console.error(e);
    return [];
  }

  return docs;
};
