# OpenQDA Client Refactoring Guidelines

## Architectural Principles

Coming soon!

## JavaScript-specific Principles

### Move globals into modules

In order to avoid pollution of the global (`window`) namespace and also to avoid
spaghetti code architecture any globals *should* be moved into modules.

```js
window.Theme = Theme // don't do this!
```

```js
export const Theme = { ... } // do this
```

Modules can be imported anywhere, plus they help to encapsulate logic.

> Note: there might be occasions where global scope is necessary to provide
> access to certain functions at runtime, especially for debugging after
> application has been build.

### Use only named exports internally

While it makes sense for libraries to use default exports and allow for
custom naming, internally we should stick with named exports.

```js
export const Theme = { ... } // prefer this

const Foobar = {};
export default Foobar // avoid this
```

There are several reasons to do so, from improved Intellisense/code-completion
to proper entity naming.

Note that this does not apply for Vue templates, since Vue automatically
transforms the templates to modules with default export.

### Use arrow functions where possible
If there is no reliance on `this` then functions
should be declared as arrow functions, instead of `function`.

**Note:** the reliance on `this` should be generally avoided.

### Use single-object parameter
Functions should favour single objects as parameters, instead of 
parameter lists.

```js
let wrong = (a, b, c) => { ... }
let right = ({ a, b, c }) => { ... }
```

This allows for a much more flexible change in function signatures. 

### Facade to repeatedly used NPM packages

Example: we use axios a lot in our client to make server requests.
However, if we want to replace axios one day with, i.e. `fetch` then we
have to rewrite the whole client.

Instead, we should create a Facade to the dependency, which is designed to be flexible
to the outside while specifically handle the internals.

### if-flattening

Avoid multiple levels of if-nesting and flatten to optimally one level.

### Prefer-Async/Await

Asynchronous code should prefer `async/await` instead of `promise.then().catch()`. 

## Vue-specific Principles

### Extract general logic from templates

Vue Templates allow for Template-level logic and reactivity.
However, consider a hypotehtical scenario in which we want to drop Vue
and replace it with something else.

All the client-code that should be dropped together with Vue would
be considered Vue-specific. However, any code that should remain as
general client logic should remain and thus exist outside of the Templates.

Consider this when designing new Templates and components.

### Avoid binding reusable component with $page

While Laravel+Inertia offer an easy integration for page-level
properties, they should only be used within a "Page Template".

Consider the following user's `ProfileImage.vue` component:

```vue
<script setup></script>

<template>
  <img
      class="object-cover w-full h-full rounded-full"
      :src="$page.props.auth.user.profile_photo_url"
      :alt="$page.props.auth.user.name"
  />
</template>

<style scoped></style>
```

It hard-wires it's `src` and `alt` attributes to a specific object structure
within the current page (indicated by `$page`).

## Documenting

### Vue components

The `<script>` part should begin with a summary comment:

For higher-order-components and pages the script parts should be structured
by topics:

```js
/*---------------------------------------------------------------------------*/
// CODES
/*---------------------------------------------------------------------------*/
const { codes } = useCodes()
```
