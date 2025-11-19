<script setup lang="ts">
import Headline3 from '../../../Components/layout/Headline3.vue';
import {
  BeakerIcon,
  ShieldCheckIcon,
  ScaleIcon,
} from '@heroicons/vue/24/outline';
import Button from '../../../Components/interactive/Button.vue';
import { onMounted } from 'vue';
import { request } from '../../../utils/http/BackendRequest';
import { attemptAsync } from '../../../Components/notification/attemptAsync';
import { flashMessage } from '../../../Components/notification/flashMessage';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  user: Object,
});

const Research = {
  request: async () => {
    await attemptAsync(
      () =>
        request({
          type: 'post',
          url: '/user/research/request',
        }),
      'Successfully requested to participate in research. Please check your email to confirm.'
    );
    router.reload({ only: ['auth'] });
  },
  confirm: async ({ token }) => {
    try {
      await request({
        type: 'post',
        url: '/user/research/confirm',
        body: { token },
      });
      flashMessage('Successfully confirmed participation in research.', {
        type: 'success',
      });
    } catch (e) {
      flashMessage(e.message, { type: 'error' });
    } finally {
      router.reload({ data: {} });
    }
    router.reload({ only: ['auth'] });
  },
  withdraw: async () => {
    await attemptAsync(
      () =>
        request({
          type: 'post',
          url: '/user/research/withdraw',
        }),
      'Successfully withdrew from research participation.'
    );
    router.reload({ only: ['auth'] });
  },
};

onMounted(async () => {
  const query = new URLSearchParams(window.location.search);
  const token = query.get('token');
  // cfr stands for  "confirm research"
  if (query.get('action') === 'cfr') {
    await Research.confirm({ token });
  }
});
</script>

<template>
  <div class="px-4 sm:px-0">
    <Headline3> Legal and GDPR </Headline3>

    <p class="mt-1 text-sm text-foreground/50 my-4">
      In the following we provide you with the legal documents you may need for
      your research.
    </p>

    <div class="font-normal flex items-center">
      <ShieldCheckIcon class="w-5 h-5 me-1" />
      <span>Privacy Policy</span>
    </div>
    <div class="flex justify-between items-center my-4 bg-transparent text-sm">
      <p class="text-foreground/50">
        You can read or download the most recent version of our privacy policy.
      </p>
      <span class="flex gap-4 items-center">
        <a
          href="/privacy"
          target="_blank"
          class="text-foreground/80 hover:text-secondary hover:underline text-nowrap inline-flex items-center underline-offset-3"
          >Read Privacy Policy</a
        >
      </span>
    </div>

    <div class="font-normal flex items-center">
      <ScaleIcon class="w-5 h-5 me-1" />
      <span>Terms of Use</span>
    </div>
    <div class="flex justify-between items-center my-4 bg-transparent text-sm">
      <p class="text-foreground/50">
        You can read or download the most recent version of our terms
      </p>
      <span class="flex gap-4 items-center">
        <a
          href="/terms"
          target="_blank"
          class="text-foreground/80 hover:text-secondary hover:underline text-nowrap inline-flex items-center underline-offset-3"
          >Read Terms of Use</a
        >
      </span>
    </div>

    <div class="font-normal flex items-center">
      <BeakerIcon class="w-5 h-5 me-1" />
      <span>Research Participation</span>
    </div>
    <div
      class="flex justify-between items-center gap-2 my-4 bg-transparent text-sm"
    >
      <p v-if="props.user.research_consent" class="text-foreground/50">
        Thank you for participating in our research! The consent was given by
        double-opt-in on
        {{ new Date(props.user.research_consent).toLocaleDateString() }}.
      </p>
      <p v-else-if="props.user.research_requested" class="text-foreground/50">
        Please click the link in the email we sent to you to confirm your
        participation. The mail has been sent on
        {{ new Date(props.user.research_requested).toLocaleDateString() }}.
      </p>
      <p v-else class="text-foreground/50">
        Help us to improve OpenQDA and contribute to research in qualitative
        data analysis by clicking the "Join" button. We will send you an email
        with a confirmation link, which you need to click to confirm your
        participation. You can withdraw from the research at any time.
        Implications for your data privacy can be found in our
        <a
          href="/privacy"
          target="_blank"
          class="text-foreground/80 hover:text-secondary hover:underline text-nowrap inline-flex items-center underline-offset-3"
          >privacy policy</a
        >.
      </p>
      <span class="flex gap-2 items-center">
        <span
          v-if="props.user.research_consent"
          class="rounded text-sm border border-primary text-primary px-1"
          >Participating</span
        >
        <Button
          v-if="props.user.research_requested || props.user.research_consent"
          variant="outline"
          size="sm"
          :onclick="Research.withdraw"
          >Withdraw
        </Button>
        <Button
          v-if="props.user.research_requested"
          variant="outline"
          size="sm"
          :onclick="Research.request"
          class="ml-auto"
          >Resend Email
        </Button>
        <Button
          v-if="!props.user.research_requested && !props.user.research_consent"
          variant="confirmative"
          size="sm"
          class="text-primary-foreground"
          :onclick="Research.request"
          >Participate</Button
        >
      </span>
    </div>
  </div>
</template>

<style scoped></style>
