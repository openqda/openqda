# Developing Visualization plugins

In OpenQDA the visualization (on the analysis page) is realized using
little plugins, written in [Vue 3](https://vuejs.org/) and [Tailwind](https://tailwindcss.com/).

Integrating a visualization plugin requires only two major procedures:

1. writing the plugin
2. registering the plugin

## Requirements and setup

You need to [have OpenQDA installed on your system](../installation/preparations.md) 
in order to locally test the written plugins.

Usually with installation you will also have [NodeJS](https://nodejs.org/en) and [NPM](https://www.npmjs.com)
available, which are the tools you definitely need.

## Writing a custom plugin

You can write new plugins outside from OpenQDA and install them on your
own hosted instance (on-premise).

For that you need to create a npm module which you can either install locally
or [publish to the npm registry](https://docs.npmjs.com/cli/v11/commands/npm-publish).

### Initiating a new NPM module

Create a new directory that will contain your plugins:

```shell
$ mkdir -p ~/myplugins/myvisualization
$ cd mkdir -p ~/myplugins/myvisualization
$ touch package.json # create the module config file
$ touch index.js # create entry point for the module
$ touch MyVisualization.vue # create template
```

Copy the following template and save it as `package.json`:

```json
{
  "name": "myvisualization",
  "version": "1.0.0",
  "description": "Collection of default visualizations",
  "main": "index.js",
  "scripts": {
    "test": ""
  },
  "author": "Jan Küster",
  "license": "MIT",
  "peerDependencies": {
    "@heroicons/vue": "*",
    "vue": "*"
  }
}
```

The NPM website has an [introduction on modules](https://docs.npmjs.com/about-packages-and-modules#about-packages)
and a [documentation of all fields in `package.json`](https://docs.npmjs.com/cli/v11/configuring-npm/package-json?v=true).

### Adding dependencies

Sometimes you might want to use a library to do the heavy lifting,
such as D3 or Plotly:


```shell
$ npm install --save plotly.js-basic-dist-min
```

### Create the entry point

In your `index.js` you will create a default entry point for OpenQDA to
register your plugin. The following assumes you will define and register
a single visualization plugin:

```js
// the actual plugin definition
const plugin = {
  /**
   * Used to find and access plugin within the registry.
   * Should be unique.
   */
  key: 'myPLugin',

  /**
   * Human-readable title to display.
   */
  title: 'My Visualization',

  /**
   * Optional.
   */
  summary: 'So fancy, many colorz!',

  /**
   * For filtering. Needs to be exactly
   * this value.
   */
  type: 'visualization',

  /**
   * Dynamically load Vue component.
   * Must use dynamic imports!
   */
  load: () => import('./MyVisualization.vue'),

  /**
   * let host render options button
   */
  hasOptions: true,
};


// this is called by OpenQDA
export default function register (api) {
  api.register(plugin);
};
```

### Create the renderer Template

OpenQDA uses [Vue 3](https://vuejs.org) as frontend library in order to manage state,
reactivity, html template etc. and you should get familiar with its reactivity model.

As a starting point, you can copy the following template and tweak your way through
until your result is satisfiable:

```vue
<template>
  <div>
    <component
        :is="props.menu"
        title="My Plugin Options"
        :show="props.showMenu"
        @close="API.setShowMenu(false)"
    >
      <ul>
        <li>
          <label class="text-left text-xs font-medium uppercase w-full">
            Show Empty Sources
          </label>
          <select v-model="showEmpty" class="w-full">
            <option value="all">By Codes</option>
            <option value="source">By Source</option>
          </select>
        </li>
      </ul>
    </component>  
    <div
        v-for="(source, index) in $props.sources"
        class="my-10 border-l border-l-border"
        :key="`${source.id}-${index}`"
    >
        <div
            v-if="
        !!$props.checkedSources.get(source.id) &&
        (codesList.get(source.id)?.length || options.showEmpty)
      "
        >
            <Headline3 class="ms-3">
                <span>{{ source.name }}</span>
                <button
                    title="Hide this source"
                    @click="$emit('remove', source.id)"
                    class="float-right"
                >
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </Headline3>
            <div
                v-for="code in codesList.get(source.id)"
                :key="code.id"
                :style="{ borderColor: code.color }"
                class="border-l border-r ml-2 mt-3"
            >
                <div class="p-2" :style="{ backgroundColor: code.color }">
                    <span>{{ code.name }}</span>
                </div>
                <div v-for="selection in code.text" :key="selection.source_id">
                    <div
                        v-if="source.id === selection.source_id"
                        class="my-1 border-b"
                        :style="{ borderBottomColor: code.color }"
                    >
                        <div
                            class="text-sm text-silver-500 min-w-[6rem] flex justify-between p-2"
                        >
                            <div>{{ selection.start }} - {{ selection.end }}</div>
                            <span>
                <span>{{
                        new Date(selection.updatedAt).toLocaleDateString()
                    }}</span
                ><span>, </span>
                <span>by {{ API.getMemberBy(selection.createdBy)?.name }}</span>
              </span>
                        </div>
                        <div class="p-2 flex-grow">{{ selection.text }}</div>
                    </div>
                </div>
            </div>
            <div v-if="!codesList.has(source.id)" class="ml-2 mt-2 p-2 bg-silver-100">
                No selections found in this source
            </div>
        </div>
    </div>
  </div>
</template>

<script setup>
// you can import any vue core function
// as well as any hero icon
// and any module you have installed within your plugin
import { onMounted, ref, watch, inject } from 'vue';

// this will tell OpenQDA that you removed
// a source, which is the same as if you unchecked it
// in the list on the left side of the analysis page.
defineEmits(['remove']);

// the current data, directly passed as props
// in case you need fine-grained access
const props = defineProps([
  'sources',
  'codes',
  'checkedSources',
  'checkedCodes',
  'hasSelections',
  'menu',
  'showMenu',
]);

// unless you need fine-grained access,
// you should use the API
const API = inject('api');

// you cannot import any custom components,
// defined in OpenQDA, however, common components
// are injected from the parent template.
const { Headline3 } = inject('components');
const codesList = ref(new Map());
const options = ref({
  showEmpty: false,
});

// compute intensive operations should be
// debounced, so they do not block the UI
const rebuildList = API.debounce(() => {
  props.sources.forEach((source) => {
    const c = API.getCodesForSource(source);

    if (c.length) {
      codesList.value.set(source.id, c);
    } else {
      codesList.value.delete(source.id);
    }
  });
}, 100);

// watching the props deeply enables
// to rebuild the list whenever something changes
// from the "outside", such as un/selected Sources or Codes
watch(props, rebuildList, { immediate: true, deep: true });

// do not build the list for the first time,
// before the component hasn't mounted! 
onMounted(() => rebuildList());
</script>
```

You can view the existing plugins in `web/plugins/visualization` in order
to get an idea of how they work.

#### Conventions / Best Practices

Here are some tips to speed up your plugin development:

- Get familiar with [Vue 3](https://vuejs.org) and its reactivity model
- Use the `API` before implementing your own logic. It provides
  many useful functions to access the data.
- Make sure your template has a single root element (e.g. a `<div>`) to avoid Vue warnings and potential quirks
- Use the injected common components (e.g. `Headline3`) for a consistent look and feel

### API

As shown in the template, you can leverage an API in order
to reactively get the filtered data and re-render your output.

For a better understanding you might want to look at [the API file](../../web/resources/js/Pages/analysis/visualization/createVisualizationAPI.js).

### Register the plugin

In contrast to default plugins (see next section of this document),
you will have to actively import your plugin in order to be registered!

First, cd into `openqda/web` and install your plugin via `npm install --save  ~/myplugins/myvisualization`,
assuming you used the path described above. If you published your plugin, you can also install it from
the registry via `npm install --save myvisualization`, assuming `myvisualization` was the name under which
you published the package.

Now you need to register the plugin by following the rules, described in `openqda/web/plugins.js`.
It guides you in where to place your import and where to call the registration.
From here, the plugin is automatically integrated in the Analysis page.


## Extending the default plugins

> Important! If you intend to extend the default plugins for [openqda.org](https://openqda.org)
> you will first need to reach consensus with the core team.
> This is, because a default plugin will affect all users of OpenQDA and
> we there take a closer look at the use-case and necessity, before
> reviewing pull requests for default plugins.

You will find the default plugins located at `web/plugins/visualization`

Reach out to us by opening an issue or discussion if you are interested
in providing a new default visualization plugin.
