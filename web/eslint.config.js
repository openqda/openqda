import globals from "globals";
import pluginJs from "@eslint/js";
import pluginVue from "eslint-plugin-vue";

export default [
  {
      languageOptions: {
          globals: {
              ...globals.browser,
              Ziggy: true,
              axios: true,
          }
      }
  },
  pluginJs.configs.recommended,
  ...pluginVue.configs["flat/essential"],
];
