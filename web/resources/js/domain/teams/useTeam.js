import { reactive, toRefs } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { request } from '../../utils/http/BackendRequest.js'

const state = reactive({
    usersInChannel: [],
    userInAteam: false,
    teamsInitialized: {},
})

export const useTeam = () => {
    const { sharedTeam = {} } = usePage().props
    const { usersInChannel, teamsInitialized } = toRefs(state)
    const teamId = sharedTeam?.id
    const Echo = window.Echo
    const initTeams = () => {
        const teamWasInitialized = teamId && teamsInitialized.value[teamId]
        const channel = `team.${teamId}`
        if (teamId && Echo && !teamWasInitialized) {
            const joined = Echo.join(channel)
            joined.here((users) => {
                    // FIXME: why is this commented?
                    // // First, update the current user's profile photo if they are already in the channel
                    // const currentUser = usePage().props.auth.user;
                    // const currentUserIndex = usersInChannel.value.findIndex(u => u.id === currentUser.id);
                    // if (currentUserIndex !== -1) {
                    //     usersInChannel.value[currentUserIndex].profile_photo = currentUser.profile_photo_url;
                    // }
                    //
                    // // Next, add any new users that are not currently in the channel
                    // const currentUserIds = usersInChannel.value.map(u => u.id);
                    // const newUsers = users.filter(u => !currentUserIds.includes(u.id))
                    //     .map(user => ({
                    //         ...user,
                    //         currentUrl: window.location.href, // This sets the URL for new users
                    //         profile_photo: user.profile_photo // Assume this exists on the user object
                    //     }));
                    //
                    // // Push the new users to the channel list
                    // usersInChannel.value.push(...newUsers);
                    console.debug(channel, 'here', users)
                })
                .joining((user) => {
                    // FIXME: why is this commented?
                    // Use Vue.set or equivalent in Vue 3 for reactivity if needed
                    // usersInChannel.value.push({
                    //     ...user,
                    //     currentUrl: window.location.href, // This might not be correct for a joining user
                    //     profile_photo: user.profile_photo // This assumes profile_photo is provided on the joining event
                    // });
                    console.debug(channel, 'joining', user)
                })
                .leaving((user) => {
                    console.debug(channel, 'leaving', user)
                    // sessionStorage.clear();
                    // Remove the leaving user by filtering the array
                    state.usersInChannel = usersInChannel.value.filter(
                        (u) => u.id !== user.id
                    );
                })
                .listen('UserNavigated', (event) => {
                    console.debug('UserNavigated', event)
                    const userIndex = usersInChannel.value.findIndex(
                        (u) => u.id === event.userId
                    );
                    if (userIndex !== -1) {
                        // Update the user's current URL
                        state.usersInChannel[userIndex].currentUrl = event.url;
                    } else {
                        // If the user is not already in the channel, add them
                        state.usersInChannel.push({
                            id: event.userId,
                            currentUrl: event.url,
                            profile_photo: event.profile_photo,
                        });
                    }
                });
        }
        state.teamsInitialized[teamId] = true
    }

    const dispatchPresence = async () => {
        const { response, error } = await request({
            url: '/user/navigation',
            type: 'post',
            body: {
                url:  window.location.href,
                team: teamId,
            }
        })
        return { response, error }
    }

    const dispose = () => {
        if (teamId) {
            Echo.leave('team' + teamId);
        }
    }

    const hasTeam = () => !!teamId

    return {
        usersInChannel,
        sharedTeam,
        hasTeam,
        teamInitialized: () => !!teamsInitialized.value[teamId],
        dispose,
        initTeams,
        dispatchPresence
    }
};
