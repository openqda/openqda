import { reactive, toRefs } from 'vue';
import axios from 'axios';

const state = reactive({
  codebook: null,
  loading: false,
});

export const useCodebookPreview = () => {
  const { codebook, loading } = toRefs(state);

  const open = async ({ codebook }) => {
    state.loading = true;

    // If codebook doesn't have codes loaded, fetch them
    if (!codebook.codes) {
      try {
        const response = await axios.get(`/api/codebooks/${codebook.id}/codes`);
        state.codebook = response.data;
      } catch (error) {
        console.error('Failed to fetch codebook codes:', error);
        // Fallback to the original codebook if fetching fails
        state.codebook = codebook;
      }
    } else {
      state.codebook = codebook;
    }

    state.loading = false;
  };

  const close = () => {
    state.codebook = null;
    state.loading = false;
  };

  return {
    codebook,
    loading,
    open,
    close,
  };
};
