import { reactive, toRefs } from 'vue';
import { request } from '../../utils/http/BackendRequest.js'

const state = reactive({
  isActive: false,
});

export const useHelpDialog = () => {
  const { isActive } = toRefs(state);
  const open = () => {
    state.isActive = true;
  };
  const close = () => (state.isActive = false);
  const submit = (formData) => {
      console.debug('Submitting form data:', formData);
      return request({
          type: 'post',
          url: '/feedback',
          body: formData
      })
  }
  return {
    isActive,
    open,
    close,
    submit
  };
};
