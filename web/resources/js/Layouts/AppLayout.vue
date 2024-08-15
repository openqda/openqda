<script setup>
/*------------------------------------
 | TODO: DEPREATED, remove
 *------------------------------------*/
import { computed, onMounted, onUnmounted, provide, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Navigation from '../Components/Navigation.vue';
import FlashMessage from '../Components/notification/FlashMessage.vue';
import { setupEcho } from '../state/sharedState.js';
import Footer from './Footer.vue';

defineProps({
  title: String,
});
let pingIntervalId;
let userInAteam = false;

const usersInChannel = ref([]);
provide('usersInChannel', usersInChannel);
// Computed property to get the list of users
// Function to save to local storage
function saveUsersToStorage(users) {
  sessionStorage.setItem('usersInChannel', JSON.stringify(users));
}

// Function to load from local storage
function loadUsersFromStorage() {
  const users = sessionStorage.getItem('usersInChannel');
  return users ? JSON.parse(users) : [];
}

function setPresence() {
  // Load the users from local storage when the component is mounted
  const savedUsers = loadUsersFromStorage();
  if (savedUsers.length > 0) {
    usersInChannel.value = savedUsers;
  }

  // Set up Echo presence channel subscription
  const url = window.location.href;

  axios
    .post('/user/navigation', {
      url: url,
      team: getSharedTeam().id,
    })
    .then((/* response */) => {})
    .catch((error) => {
      console.error('Error sending navigation update:', error);
    });

  setupEcho({
    teamId: getSharedTeam().id,
    usersInChannel,
  });

  pingIntervalId = setInterval(() => {
    // Dispatch an event to the server to indicate that the user is still on the page
    // This could be done via an Axios call to a specific endpoint that handles this logic
    axios
      .post('/user/navigation', {
        url: url,
        team: getSharedTeam().id,
      })
      .then((/* response */) => {});
  }, 5000); // Every 5 seconds
}

function cleanup() {
  // Leave the channel when the component is unmounted
  if (userInAteam) {
    clearInterval(pingIntervalId);
    window.Echo.leave('team' + getSharedTeam().id);
  }
}

onMounted(() => {
  if (getSharedTeam()) {
    userInAteam = true;
    setPresence();
    document.addEventListener('beforeunload', cleanup);
  }
});

// Watch for changes in usersInChannel and save them to local storage
watch(
  usersInChannel,
  (newUsers) => {
    saveUsersToStorage(newUsers);
  },
  { deep: true }
);

onUnmounted(() => {
  // Leave the channel when the component is unmounted
  if (userInAteam) {
    document.removeEventListener('beforeunload', cleanup);
    clearInterval(pingIntervalId);
    window.Echo.leave('team' + getSharedTeam().id);
  }
});

const getSharedTeam = () => {
  return usePage().props.sharedTeam || {};
};

const shouldShowFooter = computed(() => {
  // Extract the path and hash from the URL
  const path = route().current();

  // Define conditions based on the path and hash
  return path !== 'coding.show' && path !== 'project.show';
});
</script>

<template>
  <div class="min-h-screen bg-background-l dark:bg-background-d">
    <!-- Page Content -->
    <main>
      <Navigation :active="route().current()"></Navigation>
      <FlashMessage
        v-if="$page.props.flash.message"
        :flash="$page.props.flash"
      />
      <slot />
    </main>
    <Footer v-if="shouldShowFooter" />
  </div>
</template>
