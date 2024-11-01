import { reactive, ref } from 'vue';

export class AbstractStore {
  constructor({ projectId, key }) {
    /**
     * @type {number}
     */
    this.projectId = projectId;
    /**
     * @type {Reactive<{}>}
     */
    this.entries = reactive({});

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


  add(entry) {
    this.entries[entry.id] = entry;
  }

  remove(id) {
      this.entry(id)
      delete this.entries[id]
  }

  all () {
      return Object.values(this.entries)
  }

  async save() {
    throw new Error(`Method must be implemented by subclass`);
  }

  load(entries) {
    Object.values(entries).forEach((entry) => {
        this.add(entry)
    });
  }
}
