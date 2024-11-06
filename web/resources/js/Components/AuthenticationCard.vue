<template>
  <LayoutContainer :title="$page.props.slogan">
    <div class="grid grid-cols-1 lg:grid-cols-2 items-stretch min-h-screen">
      <!--left side -->
      <div
        class="flex-1 bg-gradient-to-b from-secondary to-primary dark:from-background dark:to-background flex justify-center"
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
              class="[text-shadow:0px_16.76px_33.52px_#0000001a] [-webkit-text-stroke:1px_#347aad] font-semibold text-primary-foreground text-[53.6px] tracking-wider leading-[normal]"
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
          <ul class="self-center space-y-4" v-else>
              <li>
                  📔 In case you need help, please consult our <Link href="https://openqda.github.io/user-docs/" :external="true">OpenQDA user documentation</Link>
              </li>
              <li>
                  🗣️ You can give technical <Link href="https://github.com/openqda/feedback" :external="true">feedback directly to our developers</Link>
              </li>
              <li>
                  📧 Contact us directly via email: <Link href="mailto:openqda@uni-bremen.de" :external="true">openqda@uni-bremen.de</Link>
              </li>
          </ul>
      </div>
    </div>
  </LayoutContainer>
</template>
<script setup>
import LayoutContainer from '../Layouts/LayoutContainer.vue';
import ImageCarousel from './image/ImageCarousel.vue';
import Link from './Link.vue'
</script>

<style scoped></style>
