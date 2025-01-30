import globals from 'globals';
import pluginJs from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';

export default [
  {
    languageOptions: {
      globals: {
        ...globals.browser,
        ...globals.mocha,
        ...globals.jest,
        Ziggy: true,
        axios: true,
        route: true,
        cy: true,
      },
    },
  },
  pluginJs.configs.recommended,
  ...pluginVue.configs['flat/essential'],
  {
    rules: {
      'vue/multi-word-component-names': 'off',
      'vue/no-use-v-if-with-v-for': 'off',
      'no-unused-vars': ['error', { ignoreRestSiblings: true }],
      'no-console': ['error', { allow: ['warn', 'error'] }],
    },
  },
];
