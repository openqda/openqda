import { defineAsyncComponent, markRaw, reactive, ref, toRefs } from 'vue';
import { OpenQDAPlugins } from '../../../exchange/OpenQDAPlugins.js';
import '../../../../../plugins.js'

const state = reactive({
  visualizerComponent: null,
  visualizerName: null,
  loaded: {},
});

const type = 'visualization';

export const useVisualizerPlugins = () => {
  const { visualizerComponent, visualizerName } = toRefs(state);
  const selectVisualizerPlugin = ({ value, unlessExists = false }) => {
    if (visualizerComponent.value && unlessExists) {
      return;
    }

    const plugin = OpenQDAPlugins.get({ type, key: value });
    const loader = plugin.load;
    state.visualizerComponent =
      loader === null ? loader : markRaw(defineAsyncComponent(loader));
    state.visualizerName = value;
  };

  const availablePlugins = ref(
    OpenQDAPlugins.all({ type }).map((plugin) => ({
      value: plugin.key,
      label: plugin.title,
    }))
  );

  return {
    availablePlugins,
    selectVisualizerPlugin,
    visualizerComponent,
    visualizerName,
  };
};
