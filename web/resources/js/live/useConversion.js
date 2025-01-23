import { useEcho } from '../collab/useEcho.js';

export const useConversion = ({ projectId }) => {
  const channelId = `conversion.${projectId}`;
  const echo = useEcho().init();

  const onConversionComplete = (fn) => {
    echo
      .private(channelId)
      .listen('ConversionCompleted', (e) => fn(e, channelId));
  };
  const onConversionFailed = (fn) => {
    echo.private(channelId).listen('ConversionFailed', (e) => fn(e, channelId));
  };
  const leaveConversionChannel = () => echo.leave(channelId);

  return {
    onConversionFailed,
    leaveConversionChannel,
    onConversionComplete,
  };
};
