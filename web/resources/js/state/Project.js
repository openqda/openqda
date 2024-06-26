/**
 * Global client-side facade to
 * retrieve the current valid project id.
 * @type {object}
 */
export const Project = {};

/**
 * Retrieves the current project id from multiple sources.
 * Returns the project id if found or undefined if not found or invalid.
 * @returns {undefined|string}
 */
Project.getId = () => {
  const path = window.location.pathname;
  const segments = path.split('/').filter(Boolean);
  let projectId = segments[0] === 'projects' && segments[1];

  if (!Project.isValidId(projectId)) {
    projectId = sessionStorage.getItem('projectId');
  }

  if (!Project.isValidId(projectId)) {
    const query = new URLSearchParams(window.location.search);
    projectId = query.get('projectId');
  }

  if (Project.isValidId(projectId)) {
    return projectId;
  }

  throw new Error(`Could not retrieve a valid project id.`);
};

/**
 * @private
 * @type {[]}
 */
const invalidIdValues = [
  0,
  '0',
  false,
  undefined,
  null,
  '',
  'undefined',
  'null',
];

/**
 * checks, whether a given project id is valid
 * @param id {any}
 * @return {boolean}
 */
Project.isValidId = (id) => !invalidIdValues.includes(id);
