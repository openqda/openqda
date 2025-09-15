import { reactive, toRefs } from 'vue';

const state = reactive({
  status: 'idle', // 'idle' | 'submitting' | 'success' | 'error' | 'disabled'
  disabled: false,
  message: null,
  error: null,
});

/**
 * Composable for managing submission status of forms and dialogs.
 * @return {{setSubmitting: setSubmitting, setSuccess: setSuccess, setError: setError, setDisabled: setDisabled, message, error, setIdle: setIdle, status}}
 */
export const useSubmissionStatus = () => {
  const { status, message, disabled, error } = toRefs(state);
  const setIdle = () => {
    state.status = 'idle';
    state.message = null;
    state.disabled = false;
    state.error = null;
  };
  const setSubmitting = () => {
    state.status = 'submitting';
    state.disabled = true;
    state.message = null;
    state.error = null;
  };
  const setSuccess = (msg) => {
    state.status = 'success';
    state.disabled = false;
    state.message = msg || 'Submission successful.';
    state.error = null;
  };
  const setError = (err) => {
    state.status = 'error';
    state.message = 'An error occurred during submission.';
    state.disabled = false;
    state.error = err;
  };
  const setDisabled = () => {
    state.disabled = true;
  };
  return {
    status,
    message,
    disabled,
    error,
    setIdle,
    setSubmitting,
    setSuccess,
    setError,
    setDisabled,
  };
};
