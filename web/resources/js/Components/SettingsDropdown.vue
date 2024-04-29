<script setup>
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import { router } from '@inertiajs/vue3'
import { Project } from '../state/Project.js';
import ProfileImage from './user/ProfileImage.vue'

function onLogout () {
    router.post(route('logout'))
}

function getprofileLink (profileUrl) {
    const projectId = Project.getId();
    return Project.isValidId(projectId)
        ? `${profileUrl}?projectId=${projectId}`
        : profileUrl
}

</script>


<template>
    <Dropdown align="right" width="48">
        <template #trigger>
            <button
                class="flex text-sm relative transition w-16 h-16 border-2 border-transparent focus:outline-none focus:border-gray-300"
                v-if="$page.props.jetstream.managesProfilePhotos">
                <ProfileImage />
            </button>

            <span v-else class="inline-flex">
                <button
                    class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out bg-white border border-transparent rounded-md hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50"
                    type="button">
                    {{ $page.props.auth.user.name }}
                    <svg
                        class="ml-2 -mr-0.5 h-4 w-4"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                        stroke="currentColor">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                        />
                    </svg>
                </button>
            </span>
        </template>

        <template #content>
            <!-- Account Management -->
            <div
                class="block px-4 py-2 text-xs text-gray-400"
            >
                Manage Account
            </div>

            <DropdownLink :href="getprofileLink(route('profile.show'))">
                Profile
            </DropdownLink>

            <DropdownLink
                v-if="$page.props.jetstream.hasApiFeatures"
                :href="route('api-tokens.index')"
            >
                API Tokens
            </DropdownLink>

            <div class="border-t border-gray-200"/>

            <!-- Authentication -->
            <form @submit.prevent="logout">
                <DropdownLink @click="onLogout" href="#">
                    Log Out
                </DropdownLink>
            </form>
        </template>
    </Dropdown>
</template>

