<script setup>
import LayoutContainer from '../Layouts/LayoutContainer.vue';
import ImageCarousel from './image/ImageCarousel.vue';
import HelpResources from './HelpResources.vue';
</script>
<template>
  <LayoutContainer :title="$page.props.slogan">
    <div class="grid grid-cols-1 lg:grid-cols-2 items-stretch min-h-screen">
      <!--left side -->
      <div
        class="flex-1 bg-linear-to-b from-secondary to-primary dark:from-background dark:to-background flex justify-center"
      >
        <div
          class="p-2 m-2 md:p-5 md:m-5 text-center xl:w-3/4 md:w-full self-center"
        >
          <img
            :src="$page.props.logo"
            alt="OpenQDA Logo"
            class="w-1/3 lg:w-1/4 xl:w-1/5 ml-auto mr-auto"
          />
          <div class="w-full my-4">
            <h1
              class="font-bold text-primary-foreground text-5xl tracking-widest leading-normal"
            >
              {{ $page.props.title }}
            </h1>
          </div>
          <slot />
        </div>
      </div>

      <!-- right side -->
      <div class="flex-1 bg-surface flex justify-center">
        <ImageCarousel
          v-if="$page.props.bgtl"
          class="self-center"
          :sources="[
            {
              img: $page.props.bgtl,
              name: 'Data Collection',
              description: 'Data collection description foobar baz',
            },
            {
              img: $page.props.bgbl,
              name: 'Data Preparation',
              description: 'Data preparation',
            },
            {
              img: $page.props.bgtr,
              name: 'Coding',
              description: 'Coding description foobar baz',
            },
            {
              img: $page.props.bgbr,
              name: 'Analysis',
              description: 'Data analysis description foobar baz',
            },
          ]"
        />
        <HelpResources class="self-center flex flex-col gap-4" v-else />
      </div>
    </div>
  </LayoutContainer>
</template>
