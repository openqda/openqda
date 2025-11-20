import { usePage } from '@inertiajs/vue3';

export const useLegal = () => {
  const { privacy, terms, auth } = usePage().props;
  const privacyUpdated = privacy ? new Date(privacy) : null;
  const termsUpdated = terms ? new Date(terms) : null;
  const userTermsConsent = auth.user?.terms_consent
    ? new Date(auth.user?.terms_consent)
    : null;
  const userPrivacyConsent = auth.user?.privacy_consent
    ? new Date(auth.user?.privacy_consent)
    : null;

  const privacyConsentRequired =
    !userPrivacyConsent || userPrivacyConsent < privacyUpdated;
  const termsConsentRequired =
    !userTermsConsent || userTermsConsent < termsUpdated;
  const consentRequired = privacyConsentRequired || termsConsentRequired;
  const researchRequired =
    !auth.user?.research_requested && !auth.user?.research_consent;

  return {
    consentRequired,
    privacyConsentRequired,
    privacyUpdated,
    termsUpdated,
    termsConsentRequired,
    researchRequired,
  };
};
