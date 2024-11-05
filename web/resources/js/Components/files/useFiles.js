import axios from 'axios';
import { createBlob } from '../../utils/files/createBlob.js';
import { flashMessage } from '../notification/flashMessage.js';

export const useFiles = () => {
  return {
    downloadSource,
  };
};

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
    console.error('Error downloading source file:', error);
    flashMessage('An error occurred while downloading the source file.', {
      type: error,
    });
  }
};
