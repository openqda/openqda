import { ref } from 'vue';

export const useWizard = ({ start }) => {
  const current = ref(start);
  const history = ref([]);
  const data = ref({});

  const isCurrent = (name) => current.value === name;
  const hasPrev = () => history.value.length > 0;
  const next = (name) => {
    history.value.push(current.value);
    current.value = name;
  };
  const step = (name) => {
    const _history = history.value;
    const index = _history.indexOf(name);
    return index > -1 ? index + 1 : _history.length + 1;
  };
  const show = (name) => isCurrent(name);
  const back = () => {
    if (hasPrev()) {
      const _history = history.value;
      _history.pop();
      current.value =
        _history.length === 0 ? start : _history[_history.length - 1];
    }
  };

  const getHistory = () => history.value;
  const updateData = (obj) => Object.assign(data.value, obj);
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
