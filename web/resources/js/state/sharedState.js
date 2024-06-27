export function setupEcho({ teamId, usersInChannel }) {
  // Assuming you have already configured Echo globally
  if (!window.Echo) {
    console.error('Echo has not been initialized.');
    return;
  }

  window.Echo.join(`team.${teamId}`)
    .here((/* users */) => {
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
    })
    .joining((/* user */) => {
      // FIXME: why is this commented?
      // Use Vue.set or equivalent in Vue 3 for reactivity if needed
      // usersInChannel.value.push({
      //     ...user,
      //     currentUrl: window.location.href, // This might not be correct for a joining user
      //     profile_photo: user.profile_photo // This assumes profile_photo is provided on the joining event
      // });
    })
    .leaving((user) => {
      sessionStorage.clear();
      // Remove the leaving user by filtering the array
      usersInChannel.value = usersInChannel.value.filter(
        (u) => u.id !== user.id
      );
    })
    .listen('UserNavigated', (event) => {
      const userIndex = usersInChannel.value.findIndex(
        (u) => u.id === event.userId
      );
      if (userIndex !== -1) {
        // Update the user's current URL
        usersInChannel.value[userIndex].currentUrl = event.url;
      } else {
        // If the user is not already in the channel, add them
        usersInChannel.value.push({
          id: event.userId,
          currentUrl: event.url,
          profile_photo: event.profile_photo,
        });
      }
    });
}
