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
  active: (name) => name.startsWith('project'),
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
    return projectId && href(Routes.project, projectId);
  },
  active: (name) => name.startsWith('project'),
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
  path: (projectId) => projectId && href(Routes.preparation, projectId),
  active: (name) => name.startsWith('preparation'),
};

/**
 * The actual coding takes place on this page.
 * @type {Route}
 */
Routes.coding = {
  key: 'coding.show',
  name: 'coding',
  label: 'Coding',
  path: (projectId) => projectId && href(Routes.coding, projectId),
  active: (name) => name.startsWith('coding'),
};

/**
 * Analysis page of the current coding.
 * @type {Route}
 */
Routes.analysis = {
  key: 'analysis.show',
  name: 'analysis',
  label: 'Analysis',
  path: (projectId) => projectId && href(Routes.analysis, projectId),
  active: (name) => name.startsWith('analysis'),
};

/**
 * User's profile settings etc.
 * @type {Route}
 */
Routes.profile = {
    key: 'profile.show',
    name: 'profile',
    label: 'Profile',
    path: (projectId) => {
        const base = href(Routes.profile)
        return projectId ? `${base}?projectId=${projectId}` : base
    },
    active: (name) => name.startsWith('profile'),
}
