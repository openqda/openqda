import { setupEcho } from './sharedState.js'

export const TeamManager = {};

const emitter = new EventTarget();
let pingIntervalId = null;
let userInAteam = false;

TeamManager.saveUsersToStorage = users => {
    sessionStorage.setItem('usersInChannel', JSON.stringify(users));
}

TeamManager.on = (name, event) => emitter.addEventListener(name, event);

TeamManager.loadUsersFromStorage = () => {
    const users = sessionStorage.getItem('usersInChannel');
    return users ? JSON.parse(users) : [];
}

TeamManager.setupPresence = ({ usersInChannel, sharedTeam }) => {
    // Load the users from local storage when the component is mounted
    const loadedUsers = TeamManager.loadUsersFromStorage();
    if (loadedUsers.length > 0) {
        // usersInChannel.value = savedUsers;
        const e = new Event('usersLoaded');
        e.users = loadedUsers;
        emitter.dispatchEvent('usersLoaded', e);
    }

    // Set up Echo presence channel subscription
    const url = window.location.href;

    axios
        .post('/user/navigation', {
            url: url,
            team: sharedTeam.id,
        })
        .then((/* response */) => {})
        .catch((error) => {
            console.error('Error sending navigation update:', error);
        });

    setupEcho({
        teamId :sharedTeam.id,
        usersInChannel,
    });

    pingIntervalId = setInterval(() => {
        // Dispatch an event to the server to indicate that the user is still on the page
        // This could be done via an Axios call to a specific endpoint that handles this logic
        axios
            .post('/user/navigation', {
                url: url,
                team: sharedTeam.id,
            })
            .then((/* response */) => {});
    }, 5000); // Every 5 seconds
}

TeamManager.clearPresence = () => {
    cleanup
}
