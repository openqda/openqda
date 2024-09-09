<template>
  <LayoutContainer :title="$page.props.slogan">
    <div class="grid grid-cols-1 md:grid-cols-2 items-stretch min-h-screen">
      <!--left side -->
      <div
        class="left flex-1 bg-gradient-to-b from-secondary-l to-primary-l flex justify-center"
      >
        <div
          class="p-2 m-2 md:p-5 md:m-5 text-center xl:w-3/4 md:w-full self-center"
        >
          <img
            :src="$page.props.logo"
            class="w-1/3 lg:w-1/4 xl:w-1/5 ml-auto mr-auto"
          />

            <div class="w-64 h-[84px]">
          <h1 class="fixed top-0 left-0 [text-shadow:0px_16.76px_33.52px_#0000001a] [-webkit-text-stroke:3.35px_#76a5c7] opacity-20 [font-family:'Saira-SemiBold',Helvetica] font-semibold text-[#c2edff] text-[53.6px] tracking-[0] leading-[normal]">
            {{ $page.props.title }}
          </h1>
            </div>

          <slot />
        </div>
      </div>

      <!-- right side -->
      <div class="right flex-1 bg-background-l dark:bg-background-d">
        <span class="float-right m-3">
          <ThemeSwitch light="text-gray-500" dark="text-white" />
        </span>
        <div class="w-full h-full flex justify-center content-center">
          <ImageCarousel
            :sources="[
              $page.props.bgtr,
              $page.props.bgtl,
              $page.props.bglr,
              $page.props.bgll,
            ]"
            class="w-1/2 mt-auto mb-auto"
          />
        </div>
      </div>
    </div>
  </LayoutContainer>
</template>
<script setup>
import ThemeSwitch from '../ui/theme/ThemeSwitch.vue';
import LayoutContainer from '../Layouts/LayoutContainer.vue';
import ImageCarousel from '../ui/image/ImageCarousel.vue';
</script>

<style scoped>
.head-shadow {
  text-shadow:
    0px 0px 1px black,
    0 0 10px white,
    0 0 1px black;
}
</style>
