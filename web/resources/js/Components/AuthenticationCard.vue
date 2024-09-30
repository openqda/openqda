<template>
  <LayoutContainer :title="$page.props.slogan">
    <div class="grid grid-cols-1 lg:grid-cols-2 items-stretch min-h-screen">
      <!--left side -->
      <div class="left flex-1 bg-gradient-to-b from-secondary to-primary dark:from-background  dark:to-background flex justify-center">
          <div class="p-2 m-2 md:p-5 md:m-5 text-center xl:w-3/4 md:w-full self-center">
              <img :src="$page.props.logo" class="w-1/3 lg:w-1/4 xl:w-1/5 ml-auto mr-auto" />
              <div class="w-full my-4">
                  <h1 class="[text-shadow:0px_16.76px_33.52px_#0000001a] [-webkit-text-stroke:1px_#347aad]  font-semibold text-primary-foreground text-[53.6px] tracking-wider leading-[normal]">
                      {{ $page.props.title }}
                  </h1>
              </div>
          <slot />
        </div>
      </div>

      <!-- right side -->
      <div class="right flex-1 bg-surface">
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
