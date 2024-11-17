import { usePage } from '@inertiajs/vue3'

export const useUsers = () => {
    const { auth, teamMembers } = usePage().props
    const allUsers = {}
    ;[auth.user, ...teamMembers].forEach(user => {
        allUsers[user.id] = user
    })

    const getMemberBy = id => {
        return allUsers[id]
    }

    return { getMemberBy, allUsers }
}
