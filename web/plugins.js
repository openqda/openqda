// --------------------------------------------------------------------------//
// CLIENT PLUGIN REGISTRY
//
// This is the entry point to register all client-side plugins.
// Please make sure you followed the installation guide on Plugins
// in order to understand what's going on here!
//
// https://github.com/openqda/openqda/tree/main/docs/plugins/VISUALIZATION.md
//
// --------------------------------------------------------------------------//
import { OpenQDAPlugins } from './resources/js/exchange/OpenQDAPlugins.js'
import registerVisualizationPlugins from '@openqda/visualization';

// --------------------------------------------------------------------------//
// PLACE YOUR CUSTOM PLUGIN IMPORTS HERE AND MAKE SURE YOU REGISTER THEM AT
// THE BOTTOM OF THIS FILE!
// --------------------------------------------------------------------------//
// import registerMyPlugin from 'mycoolvisualization';

// --------------------------------------------------------------------------//
// REGISTER DEFAULT PLUGINS
// --------------------------------------------------------------------------//
registerVisualizationPlugins(OpenQDAPlugins);

// --------------------------------------------------------------------------//
// REGISTER CUSTOM PLUGINS
// --------------------------------------------------------------------------//
// registerMyPlugin(OpenQDAPlugins);
