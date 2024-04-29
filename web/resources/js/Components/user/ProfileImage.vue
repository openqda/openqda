<script setup>

</script>

<template>
    <img
        class="object-cover w-full h-full rounded-full"
        :src="$page.props.auth.user.profile_photo_url"
        :alt="$page.props.auth.user.name"/>
</template>

<style scoped>

</style>
