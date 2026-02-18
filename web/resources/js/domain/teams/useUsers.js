import { usePage } from '@inertiajs/vue3';

/**
 * Custom hook to manage users in a team context.
 */
export const useUsers = () => {
  const { auth, teamMembers = [] } = usePage().props;
  const allUsers = {};
  [auth.user, ...teamMembers].forEach((user) => {
    allUsers[user.id] = user;
  });

  const getOwnUser = () => {
    return auth?.user;
  };

  const userIsVerified = () => {
    const user = getOwnUser();
    return Boolean(user?.email_verified_at);
  };

  const getMemberBy = (id) => {
    return allUsers[id];
  };

  return { getMemberBy, allUsers, getOwnUser, userIsVerified };
};
