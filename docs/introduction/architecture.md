# OpenQDA Architecture
In terms of software architecture OpenQDA is a pluggable monolith. That means,
there is a core application that can be extended by internal or external programs
that follow a certain interface definition.

## Overview

Coming soon!

## Plugins
One of the main goals of this architecture is to make it easy for you to extend OpenQDA
without the need to know its exact internals but by following only a few rules that
a plugin must comply with.

A plugin can thereby be a php module (backend), a JavaScript module or Vue component (frontend)
or an external service (both).

## Server

Coming soon!

## Client

We use Vue 3 as our frontend engine, vite as bundler and many Laravel-specific tools for communication
(Echo, Intertia etc.)

### Component System
Our long-term goal is to maintain a flexible component system that "speaks" the OpenQDA design system.
For this we use [Storybook](https://storybook.js.org) to develop and update the components.
