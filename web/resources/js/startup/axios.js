import { registerGlobalRequestHook } from '../utils/http/BackendRequest.js';
/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */
import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// if we get a CSRF token error then this is likely an
// expired session. Thus, we immediately reload the window
// automatically redirecting users to the login screen
registerGlobalRequestHook((request) => {
  if (request.error && request.statusCode === 419) {
    window.location.reload();
  }
});
