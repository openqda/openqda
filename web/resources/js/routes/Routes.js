/**
 * This is the client-side definition of the app routes.
 * Use this as the single-point-of truth when dealing with routes.
 * Optimally, if changes need to be done, you only have to
 * attempt them in this module.
 */
export const Routes = {};

/**
 * @typedef Route
 * @type {object}
 * @property {string} key - the unique key of the route-resolver
 * @property {string} name - the unique name of this property within the {Route} context
 * @property {string} label - the human-readable label of this route
 * @property {function(*?):string} path - a function, resolving to a href-path by given optional args
 */

/**
 * Resolves the actual href from a given route
 * @private
 * @paranm {Route?} options
 * @param {string} options.key
 * @return {*}
 */
const href = (options, ...args) => route(options.key, ...args);

/**
 * This is the landing page
 * @type {Route}
 */
Routes.welcome = {
  key: 'welcome',
  name: 'welcome',
  label: 'Welcome to OpenQDA',
  path: () => '/',
};

/**
 * Projects list selection page.
 * Here users select a project to work on, in case
 * there is none selected yet.
 * @type {Route}
 */
Routes.projects = {
  key: 'projects.index',
  name: 'projects',
  label: 'Manage Projects',
  path: () => href(Routes.projects),
};

/**
 * Project Management page.
 * @type {Route}
 */
Routes.project = {
  key: 'project.show',
  name: 'project',
  label: 'Manage Project',
  path(projectId) {
    return projectId && href(this, projectId);
  },
};

/**
 * Preparation page for a given project by project id.
 * In the associated page users prepare their actual coding.
 * @type {Route}
 */
Routes.preparation = {
  key: 'source.index',
  name: 'preparation',
  label: 'Preparation',
  path: (projectId) => projectId && href(this, projectId),
};

/**
 * The actual coding takes place on this page.
 * @type {Route}
 */
Routes.coding = {
  key: 'coding.show',
  name: 'coding',
  label: 'Coding',
  path: (projectId) => projectId && href(this, projectId),
};

/**
 * Analysis page of the current coding.
 * @type {Route}
 */
Routes.anaysis = {
  key: 'anaysis.show',
  name: 'anaysis',
  label: 'Anaysis',
  path: (projectId) => projectId && href(this, projectId),
};
