import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https';
const enabledTransports = [forceTLS ? 'wss' : 'ws'];
const state = {
  echo: null,
};

export const useEcho = () => {
  return {
    init: () => {
      if (!state.echo) {
        state.echo = new Echo({
          broadcaster: 'reverb',
          key: import.meta.env.VITE_REVERB_APP_KEY,
          wsHost: import.meta.env.VITE_REVERB_HOST,
          wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
          wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
          forceTLS,
          encrypted: true,
          enabledTransports,
          withoutInterceptors: true,
        });
      }
      return state.echo;
    },
    echo: () => state.echo,
  };
};
