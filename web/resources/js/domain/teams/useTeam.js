import { reactive, toRefs } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { request } from '../../utils/http/BackendRequest.js';
import { useEcho } from '../../collab/useEcho.js';
import { useDebug } from '../../utils/useDebug.js';

const state = reactive({
  usersInChannel: {},
  userInAteam: false,
  teamsInitialized: {},
  teamId: null,
});

export const useTeam = () => {
  const debug = useDebug({ scope: 'teams' });
  const { sharedTeam: team, auth, teamMembers } = usePage().props;
  const sharedTeam = team ?? {};
  const { usersInChannel, teamsInitialized, teamId } = toRefs(state);
  const userId = auth.user.id;

  if (teamId.value !== sharedTeam.id) {
    debug('change team id to', sharedTeam.id);
    state.teamId = sharedTeam.id;
  }

  const getMemberBy = (id) => {
    if (id === userId) return auth.user;
    return teamMembers.find((member) => member.id === id);
  };

  const initTeams = () => {
    debug('init teams?', !teamsInitialized.value);
    const teamWasInitialized =
      teamId.value && teamsInitialized.value[teamId.value];
    const channel = `team.${teamId.value}`;
    const Echo = useEcho().echo();

    if (teamId.value && Echo && !teamWasInitialized) {
      debug(
        'Echo join channel',
        teamId.value,
        channel,
        Echo,
        teamWasInitialized
      );

      Echo.join(channel)
        .error((e) => debug(`error joining channel ${channel}`, e))
        .listen('UserNavigated', (event) => {
          if (userId === event.userId) return;
          debug(channel, `user ${event.userId} navigated to`, event.url);

          if (!state.usersInChannel[event.userId]) {
            state.usersInChannel[event.userId] = {
              id: event.userId,
              url: event.url,
              profile_photo: event.profile_photo,
            };
          }

          const name = state.usersInChannel[event.userId].name;

          state.usersInChannel[event.userId] = {
            id: event.userId,
            name,
            url: event.url,
            profile_photo: event.profile_photo,
          };

          debug(
            'after navigation received',
            state.usersInChannel[event.userId]
          );
        })
        .here((users) => {
          debug(channel, 'here', users);
          users.forEach(addUser);
        })
        .joining((user) => {
          debug(channel, 'joining', user);
        })
        .leaving((user) => {
          debug(channel, 'leaving', user);
          // sessionStorage.clear();
          // Remove the leaving user by filtering the array
          delete state.usersInChannel[user.id];
        });

      const addUser = (user) => {
        debug('add user', user);
        if (userId === user.id) return;

        if (!state.usersInChannel[user.id]) {
          state.usersInChannel[user.id] = {
            url: '',
            profile_photo: '',
            ...user,
          };
        } else {
          state.usersInChannel[user.id].name = user.name;
        }
        debug('after add user', state.usersInChannel[user.id]);
      };
    }
    state.teamsInitialized[teamId] = true;
  };

  const dispatchPresence = async () => {
    debug('dispatch presence');
    const { response, error } = await request({
      url: '/user/navigation',
      type: 'post',
      body: {
        url: window.location.href,
        team: teamId.value,
      },
    });
    debug('dispatch presence response?', response);
    debug('dispatch presence error?', error);
    return { response, error };
  };

  const dispose = () => {
    if (teamId.value) {
      debug('leave team', `team.${teamId.value}`);
      useEcho().echo().leave(`team.${teamId.value}`);
    }
  };

  const hasTeam = () => !!teamId.value;

  return {
    usersInChannel,
    sharedTeam,
    teamId,
    getMemberBy,
    hasTeam,
    teamInitialized: (id) => !!teamsInitialized.value[id],
    dispose,
    initTeams,
    dispatchPresence,
  };
};
