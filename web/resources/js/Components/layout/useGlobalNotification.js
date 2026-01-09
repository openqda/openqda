const notificationText = import.meta.env.VITE_NOTIFICATION;

export const useGlobalNotification = () => {
  return {
    notificationText,
  };
};
