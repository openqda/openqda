import { defineAsyncComponent, markRaw, reactive, ref, toRefs } from 'vue';

const state = reactive({
  visualizerComponent: null,
  visualizerName: null,
  loaded: {},
});
const plugins = {
  list: {
    title: 'List of Selections',
    load: () => import('./ListView.vue'),
  },
  portrait: {
    title: 'Code Portrait',
    load: () => import('./CodePortrait.vue'),
  },
  cloud: {
    title: 'Word Cloud',
    load: () => import('./WordCloudView.vue'),
  },
};

export const useVisualizerPlugins = () => {
  const { visualizerComponent, visualizerName } = toRefs(state);

  const selectVisualizerPlugin = ({ value, unlessExists = false }) => {
    if (visualizerComponent.value && unlessExists) {
      return;
    }

    const loader = plugins[value].load;
    state.visualizerComponent =
      loader === null ? loader : markRaw(defineAsyncComponent(loader));
    state.visualizerName = value;
  };

  const availablePlugins = ref(
    Object.entries(plugins).map(([name, val]) => {
      return { value: name, label: val.title };
    })
  );

  return {
    availablePlugins,
    selectVisualizerPlugin,
    visualizerComponent,
    visualizerName,
  };
};
