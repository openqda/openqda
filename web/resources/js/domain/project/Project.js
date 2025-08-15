import { request } from '../../utils/http/BackendRequest.js';

/**
 * Use this for any communication with the backend in regards
 * with Projects.
 */
export const Project = {};

Project.create = {
  schema: {
    name: String,
    description: {
      type: String,
      formType: 'textarea',
      placeholder: '...',
      optional: true,
      rows: 6,
    },
  },
  /**
   * Calls backend to create a new project by given args
   * @param name
   * @param description
   * @return {Promise<*>}
   */
  method: async ({ name, description }) => {
    // Validate the project details. For example, check if the name is empty.
    if (!name) {
      throw new Error('A project name is required.');
    }

    const { response, error } = await request({
      url: '/projects/new',
      type: 'post',
      body: { name, description },
    });

    if (error) {
      const message = `An error occurred while creating the project: ${response.data.message}`;
      throw new Error(message);
    }

    if (!response.data.success) {
      const message = `Failed to create a new project: ${response.data.message}`;
      throw new Error(message);
    }

    return { response, error };
  },
};

Project.update = {
  method: async ({ projectId, ...payload }) => {
    // Validate the project details. For example, check if the name is empty.
    if (!projectId) {
      throw new Error('A projectId is required.');
    }

    const { response, error } = await request({
      url: `/projects/update/${projectId}`,
      type: 'post',
      body: payload,
    });

    if (error) {
      const message = `An error occurred while updating the project: ${response.data.message}`;
      throw new Error(message);
    }

    if (!response.data.success) {
      const message = `Failed to updat project: ${response.data.message}`;
      throw new Error(message);
    }

    return { response, error };
  },
};
