<script setup lang="ts">
import DialogBase from '../../dialogs/DialogBase.vue';
import { useLegal } from './useLegal';
import InputLabel from '../../form/InputLabel.vue';
import InputError from '../../form/InputError.vue';
import { router, useForm } from '@inertiajs/vue3';
import { DocumentIcon } from '@heroicons/vue/24/outline';
import Button from '../../Components/interactive/Button.vue';
import { ref } from 'vue';
import { flashMessage } from '../../Components/notification/flashMessage';
import { request } from '../../utils/http/BackendRequest';

const show = ref(true);
const {
  privacyConsentRequired,
  termsConsentRequired,
  privacyUpdated,
  termsUpdated,
  researchRequired,
} = useLegal();
const form = useForm({
  terms: false,
  privacy: false,
  research: false,
});
const submissionError = ref();
const submit = async () => {
  if (form.processing) return;
  const data = form.data();
  try {
    await request({
      type: 'post',
      url: route('consent'),
      body: data,
    });
    flashMessage('Consent successfully updated', { type: 'success' });
    router.reload({ only: ['auth', 'privacy', 'terms'] });
    show.value = false;
  } catch (e) {
    console.error(e);
    submissionError.value = e;
  }
};
</script>

<template>
  <DialogBase
    :show="show"
    title="Your consent is required to continue"
    :static="true"
  >
    <template #body>
      <p v-if="termsConsentRequired" class="">
        We have updated our terms of use on
        {{ privacyUpdated.toLocaleDateString() }}.
      </p>
      <p v-if="privacyConsentRequired" class="">
        We have updated our privacy policy on
        {{ termsUpdated.toLocaleDateString() }}.
      </p>
      <p class="">
        Please note, OpenQDA remains GDPR compliant, free and open source.
        Please, take your time to review and accept the changes to continue
        using OpenQDA online.
      </p>

      <div class="my-6">
        <a
          href="/terms"
          target="_blank"
          v-if="termsConsentRequired"
          class="text-foreground/80 hover:text-secondary hover:underline text-nowrap inline-flex items-center underline-offset-3 my-2"
        >
          <DocumentIcon class="w-4 h-4" />
          Read the updated terms of use</a
        ><br />
        <a
          href="/privacy"
          target="_blank"
          v-if="privacyConsentRequired"
          class="text-foreground/80 hover:text-secondary hover:underline text-nowrap inline-flex items-center underline-offset-3 my-2"
        >
          <DocumentIcon class="w-4 h-4" />
          Read the updated privacy policy</a
        >
      </div>

      <div v-if="termsConsentRequired" class="input-group group contents">
        <InputLabel class="flex items-center" for="terms">
          <input
            id="terms"
            type="checkbox"
            v-model="form.terms"
            class="outline outline-0 px-0.5 focus:ring-foreground/80 checked:bg-primary"
          />
          <span class="ms-2 text-nowrap">
            <span class="ms-2 text-foreground"
              >I agree to the
              <a
                href="/terms"
                class="underline text-foreground hover:text-foreground/60"
                target="_blank"
                >terms of use</a
              >
              (required)</span
            >
          </span>
        </InputLabel>
        <InputError v-if="form.errors.terms" :message="form.errors.terms" />
      </div>

      <div v-if="privacyConsentRequired" class="input-group group contents">
        <InputLabel class="flex items-center" for="privacy">
          <input
            id="privacy"
            type="checkbox"
            v-model="form.privacy"
            class="outline outline-0 px-0.5 focus:ring-foreground/80 checked:bg-primary"
          />
          <span class="ms-2 text-nowrap">
            <span class="ms-2 text-foreground"
              >I agree to the
              <a
                href="/privacy"
                class="underline text-foreground hover:text-foreground/60"
                target="_blank"
              >
                privacy policy</a
              >(required)</span
            >
          </span>
        </InputLabel>
        <InputError v-if="form.errors.privacy" :message="form.errors.privacy" />
      </div>

      <div v-if="researchRequired" class="input-group group contents">
        <InputLabel class="flex items-baseline mt-2" for="research">
          <input
            id="research"
            type="checkbox"
            v-model="form.research"
            class="outline outline-0 px-0.5 focus:ring-foreground/80 checked:bg-primary"
          />
          <span class="ms-4 text-foreground">
            I want to participate in research to improve OpenQDA and the overall
            state of qualitative data analysis (optional)
          </span>
        </InputLabel>
        <InputError
          v-if="form.errors.research"
          :message="form.errors.research"
        />
      </div>
      <div v-if="submissionError">
        <InputError
          class="mt-2"
          :message="
            submissionError?.message ||
            Object.values(submissionError).flat().join(', ')
          "
        />
      </div>
    </template>
    <template #footer>
      <Button
        class="w-full"
        :disabled="
          (termsConsentRequired && !form.terms) ||
          (privacyConsentRequired && !form.privacy) ||
          form.processing
        "
        @click="submit"
      >
        Accept and Continue
      </Button>
    </template>
  </DialogBase>
</template>

<style scoped></style>
