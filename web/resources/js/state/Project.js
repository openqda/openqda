export const Project = {}

/**
 * Retrieves the current project id from multiple sources.
 * Returns the project id if found or undefined if not found or invalid.
 * @returns {undefined|string}
 */
Project.getId = () => {
  const path = window.location.pathname
  const segments = path.split('/').filter(Boolean)
  let projectId = segments[0] === 'projects' && segments[1]

  if (!Project.isValidId(projectId)) {
    projectId = sessionStorage.getItem('projectId')
  }

  if (!Project.isValidId(projectId)) {
    const query = new URLSearchParams(window.location.search)
    projectId = query.get('projectId')
  }

  if (Project.isValidId(projectId)) {
    return projectId
  }
}

const invalidIdValues = [0, false, undefined, null, '', 'undefined', 'null']

Project.isValidId = (id) => !invalidIdValues.includes(id)
