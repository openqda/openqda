import { useEcho } from '../collab/useEcho.js';

/**
 * Provides real-time conversion status updates via private Echo channels.
 * @param projectId
 * @return {{onConversionComplete: onConversionComplete, leaveConversionChannel: (function(): void), onConversionFailed: onConversionFailed}}
 */
export const useConversion = ({ projectId }) => {
  const channelId = `conversion.${projectId}`;

  const onConversionComplete = (fn) => {
    const echo = useEcho().init();
    echo
      .private(channelId)
      .listen('ConversionCompleted', (e) => fn(e, channelId));
  };
  const onConversionFailed = (fn) => {
    const echo = useEcho().init();
    echo.private(channelId).listen('ConversionFailed', (e) => fn(e, channelId));
  };
  const leaveConversionChannel = () => {
    const echo = useEcho().init();
    echo.leave(channelId);
  };

  return {
    onConversionFailed,
    leaveConversionChannel,
    onConversionComplete,
  };
};
