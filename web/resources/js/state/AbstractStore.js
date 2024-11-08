import { reactive, ref } from 'vue';
import { Observable } from '../utils/NanoHooks.js';
import { noop } from '../utils/function/noop.js';

export class AbstractStore {
  constructor({ projectId, key }) {
    this.observable = new Observable();

    /**
     * @type {number}
     */
    this.projectId = projectId;
    /**
     * @type {Reactive<{}>}
     */
    this.entries = reactive({});
    this.size = ref(0);
    /**
     * Namespace key for unique identification
     * @type {string}
     */
    this.key = key;
  }

  entry(id) {
    const entry = this.entries[id];
    if (!entry) {
      throw new Error(
        `Entry not found by id ${id} in ${this.projectId}/${this.key}`
      );
    }
    return entry;
  }

  /**
   * Pass in a callback to observe all changes for add, update and delete.
   * Returns a function to unregister.
   * @param callbacks
   */
  observe(callbacks) {
    const removeAdded = callbacks.added
      ? this.observable.on('added', callbacks.added)
      : noop;
    const removeUpdated = callbacks.updated
      ? this.observable.on('updated', callbacks.updated)
      : noop;
    const removeDeleted = callbacks.removed
      ? this.observable.on('removed', callbacks.removed)
      : noop;
    const removeChanged = callbacks.changed
      ? this.observable.on('changed', callbacks.changed)
      : noop;

    // call on dispose/unmount
    return () => {
      console.debug(this.key, 'dispose observer');
      removeAdded();
      removeUpdated();
      removeDeleted();
      removeChanged();
    };
  }

  add(...docs) {
    const documents = {};
    docs.forEach((doc) => {
      // skip existing docs / avoid duplicates
      if (!(doc.id in this.entries)) {
        documents[doc.id] = doc;
        this.size.value++;
      }
    });
    Object.assign(this.entries, documents);
    const added = Object.values(documents)
    this.observable.run('added', added);
    this.observable.run('changed', { type: 'added', docs:added });
    return added
  }

  update(docIdOrFn, value, { updateId = false } = {}) {
    if (typeof docIdOrFn === 'function') {
      // nested changes are applied directly by
      // consumer and this reflected in a function
      // that returns all ids of updated docs
      const allDocs = this.all()
      const updatedDocs = docIdOrFn(allDocs);
      if (updatedDocs) {
        this.observable.run('updated', updatedDocs, allDocs);
        this.observable.run('changed', { type: 'updated', docs: updatedDocs });
      }
    } else {
      const entry = this.entries[docIdOrFn];
      const { id, ...values } = value
      if (updateId) {
          values.id = id
      }
      Object.assign(entry, values);
      this.observable.run('updated', [entry], this.all());
      this.observable.run('changed', { type: 'updated', docs: [entry] });
    }
  }

  remove(...ids) {
    // check for existence independent of
    // actual removal operation
    ids.forEach((id) => this.entry(id));
    const docs = [];
    ids.forEach((id) => {
      const entry = this.entries[id];
      docs.push(entry);
      delete this.entries[id];
      this.size.value--;
    });
    this.observable.run('removed', docs);
    this.observable.run('changed', { type: 'removed', docs });
  }

  all() {
    console.debug(this.key, 'get all');
    return Object.values(this.entries);
  }

  async save() {
    throw new Error(`Method must be implemented by subclass`);
  }
}
