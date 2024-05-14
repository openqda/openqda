<script setup xmlns="http://www.w3.org/1999/html">
import Footer from '../Layouts/Footer.vue'

const props = defineProps(['html', 'background', 'canLogin', 'canRegister'])
</script>

<template>
  <div
    :style="{
      backgroundImage: `url('${$page.props.bgtl}') ,url('${$page.props.bgtr}'), url('${$page.props.bgbl}') ,url('${$page.props.bgbr}')`,
      backgroundPosition: 'left top, right top, left bottom, right bottom',
      backgroundSize: '20%',
      backgroundAttachment: 'fixed',
    }"
    class="min-h-screen pb-8 bg-silver-50 bg-center bg-no-repeat bg-contain selection:bg-red-700"
  >
    <div class="flex relative items-center justify-center py-8 gap-x-6">
      <a
        :href="route('welcome')"
        class="rounded-md bg-cerulean-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-500"
        >Home</a
      >
      <a
        v-if="!$page.props.auth.user"
        :href="route('login')"
        class="rounded-md bg-cerulean-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-500"
        >Login</a
      >
      <a
        v-if="!$page.props.auth.user"
        :href="route('register')"
        class="rounded-md bg-cerulean-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-500"
        >Register</a
      >
      <a
        v-if="$page.props.auth.user"
        :href="route('projects.index')"
        class="rounded-md bg-cerulean-700 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-cerulean-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-cerulean-500"
        >Projects</a
      >
    </div>
    <div
      class="bg-white mx-auto max-w-7xl overflow-hidden sm:py-24 px-6 py-16 lg:px-8 shadow-2xl rounded-lg border border-silver-300"
    >
      <div
        v-html="html"
        class="mx-auto max-w-6xl prose prose-h1:font-bold prose-h1:text-3xl prose-h2:text-2xl prose-h3:text-xl prose-a:text-blue-600 prose-p:text-justify prose-img:rounded-xl"
      ></div>
    </div>
  </div>
  <Footer />
</template>

<style scoped></style>
