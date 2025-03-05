import { ref } from 'vue';

/** @module */

/**
 * A simple generic wizard helper that is not yet persistent across Templates.
 * @function
 * @param start {string} name of the initial step
 * @return {{next: next, isCurrent: (function(string): boolean), getHistory: (function(): string[]), hasPrev:
 *     (function(): boolean), updateData: (function(Object): any), show: (isCurrent|(function(*): boolean)), back:
 *     back, step: (function(string): number), getData: (function(): *)}}
 */
export const useWizard = ({ start }) => {
  const current = ref(start);
  const history = ref([]);
  const data = ref({});

  /**
   * checks if name equals the name of the current step
   * @param name {string}
   * @return {boolean}
   */
  const isCurrent = (name) => current.value === name;
  /**
   * checks if there are previous entries in history
   * @return {boolean}
   */
  const hasPrev = () => history.value.length > 0;
  /**
   * sets the given next step as current and pushes the "old" one
   * to the history
   * @param name {string} name of the next step
   */
  const next = (name) => {
    history.value.push(current.value);
    current.value = name;
  };

  /**
   * Returns the index of the step by name
   * @param name {string} name of the step
   * @return {number}
   */
  const step = (name) => {
    const _history = history.value;
    const index = _history.indexOf(name);
    return index > -1 ? index + 1 : _history.length + 1;
  };
  /**
   * @alias isCurrent
   * @param name
   * @return {boolean}
   */
  const show = (name) => isCurrent(name);
  /**
   * Moves a step back, if possible
   */
  const back = () => {
    if (hasPrev()) {
      const _history = history.value;
      _history.pop();
      current.value =
        _history.length === 0 ? start : _history[_history.length - 1];
    }
  };

  /**
   * returns the history
   * @return {string[]}
   */
  const getHistory = () => history.value;

  /**
   * assigns new data to the wizard
   * @param obj {object}
   * @return {object}
   */
  const updateData = (obj) => Object.assign(data.value, obj);

  /**
   * return the wizards data
   * @return {object}
   */
  const getData = () => ({ ...data.value });

  return {
    isCurrent,
    hasPrev,
    show,
    step,
    next,
    back,
    getHistory,
    updateData,
    getData,
  };
};
