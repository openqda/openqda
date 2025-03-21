import axios from 'axios';
import { createBlob } from '../../utils/files/createBlob.js';
import { flashMessage } from '../notification/flashMessage.js';
import { asyncTimeout } from '../../utils/asyncTimeout.js';
import { usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';

/**
 * Provides upload and download functionality for files (REFI:sources).
 * As a composable it enables to run independent of the current template,
 * which allows to handle uploads, while switching routes, as long as
 * there is no full page reload.
 *
 * @composable
 * @return {{queueFilesForUpload: queueFilesForUpload, downloadSource: ((function(*): Promise<void>)|*)}}
 */
export const useFiles = () => {
  const { projectId, sources, auth } = usePage().props; // TODO (refactoring): decouple from props
  const profilePhotoUrl = auth.user.profile_photo_url;

  /**
   * Adds given list of files to an upload queue that processes
   * each file sequentially.
   *
   * @param {File[]} files list of files (Browser API File class)
   * @param {function} onError error handler
   */
  const queueFilesForUpload = ({ files, onError }) => {
    for (const file of files) {
      const source = reactive({
        name: file.name,
        type: file.type,
        size: file.size,
      });
      source.isQueued = true;
      source.isConverting = false;
      source.isUploading = false;
      source.converted = false;
      source.failed = false;
      source.progress = 0;

      sources.push(source);
      queue.unshift({ file, source, projectId, profilePhotoUrl });
    }
    setTimeout(() => {
      if (queueIsRunning) return;
      runQueue({ onError })
        .catch(onError)
        .finally(() => {
          queueIsRunning = false;
        });
    }, 500);
  };
  return {
    downloadSource,
    queueFilesForUpload,
  };
};

const queue = [];
let queueIsRunning = false;

/**
 * Runs the async upload queue until completion.
 * To prevent breaking, all failures are forwarded to the
 * error handler
 * @private
 * @param onError {function}
 * @return {Promise<void>}
 */
const runQueue = async ({ onError }) => {
  queueIsRunning = true;
  queue.sort((a, b) => {
    const aIsText = a.file.type === 'text/plain' ? 1 : 0;
    const bIsText = b.file.type === 'text/plain' ? 1 : 0;
    return aIsText - bIsText;
  });
  while (queue.length) {
    const { file, source, projectId, profilePhotoUrl } = queue.pop();
    source.isQueued = false;
    source.isUploading = true;

    // have the user notice, that upload is starting
    await asyncTimeout(500);
    try {
      const newFile = file.type.startsWith('audio/')
        ? await transcribeFile({ file, source, projectId })
        : await uploadFile({ file, source, projectId });
      Object.assign(source, newFile);
      const d = new Date();
      source.date = `${d.getFullYear()}-${d.getMonth() + 1}-${d.getDate()}`;
      source.userPicture = profilePhotoUrl;
    } catch (e) {
      source.failed = true;
      onError(e);
    } finally {
      source.isUploading = false;
    }

    // have the user notice, that upload is complete
    await asyncTimeout(500);
  }

  queueIsRunning = false;
};

/**
 * Actual file upload request to the backend.
 * Note the source is expected to already exist
 * as part of the optimistic UI.
 *
 * @param file {File}
 * @param source {object}
 * @param projectId {string}
 * @return {Promise<*>}
 */
async function uploadFile({ file, source, projectId }) {
  const isRtf =
    file.type === 'text/rtf' || (file.name && file.name.endsWith('.rtf'));

  const formData = new FormData();
  formData.append('file', file);
  formData.append('projectId', projectId);

  const response = await axios.post('/files/upload', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
    onUploadProgress: (e) => {
      source.progress = (e.loaded / e.total) * 100;
    },
  });

  if (response.data.newDocument) {
    if (isRtf) {
      response.data.newDocument.isConverting = true;
    } else {
      response.data.newDocument.converted = true;
    }

    return response.data.newDocument;
  }

  throw new Error(`No response for ${file.name}`);
}

/**
 * This is specific to audio files, where
 * transcription is expected.
 * TODO: replace with generic transformFile implementation,
 *   once plugin protocol is final
 * @param file
 * @param source
 * @param projectId
 * @return {Promise<*>}
 */
async function transcribeFile({ file, source, projectId }) {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('project_id', projectId);
  formData.append('model', 'default_model'); // Replace with your actual model name
  formData.append('language', 'en'); // Replace with the desired language code

  const response = await axios.post('/files/transcribe', formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
    onUploadProgress: (e) => {
      source.progress = (e.loaded / e.total) * 100;
    },
  });

  if (response.data.newDocument) {
    response.data.newDocument.isConverting = true;
    return response.data.newDocument;
  }

  throw new Error(`No response for ${file.name}`);
}

/**
 * Attempts to request a given source as downloadable content
 * and invokes an immediate download by virtually clicking a
 * temporary blob file.
 * @param source {object}
 * @return {Promise<void>}
 */
const downloadSource = async (source) => {
  try {
    // Perform the GET request to download the file
    const response = await axios({
      url: `/sources/${source.id}/download`,
      method: 'POST',
      responseType: 'blob', // Important to set response type to blob for binary data
    });

    // Extract the filename from the Content-Disposition header
    const disposition = response.headers['content-disposition'];
    let filename = source.name; // Fallback filename
    if (disposition && disposition.includes('attachment')) {
      const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
      const matches = filenameRegex.exec(disposition);
      if (matches != null && matches[1]) {
        filename = matches[1].replace(/['"]/g, ''); // Clean up the filename
      }
    }

    // Create a URL for the blob response data
    const url = window.URL.createObjectURL(createBlob({ data: response.data }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename); // Set the download attribute with the filename
    document.body.appendChild(link);
    link.click(); // Trigger the download

    // Clean up and remove the link from the DOM
    link.parentNode.removeChild(link);
  } catch (error) {
    // TODO (refactoring): exclude from composable and wrap in attemptAsync on template level
    console.error('Error downloading source file:', error);
    flashMessage('An error occurred while downloading the source file.', {
      type: error,
    });
  }
};
