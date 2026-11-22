# Storefront Advisor

**Task ID:** `CAFE-PUBLIC-ADVISOR-001`  
**Status:** PARTIAL — targeted automated checks and frontend build passed; browser and configured-provider checks remain unverified.

The existing public chat shell is retained. Its hard-coded FAQ, price, opening-hour and promotion text is replaced by a read-only Laravel endpoint. The endpoint reads only sellable menu items and active, in-window, non-exhausted promotions. It does not add cart lines, apply a promotion, create Orders or Payments, or consume promotion quota.

`GEMINI_API_KEY` and `GEMINI_MODEL` are optional runtime configuration only. When a key is absent or the provider fails, the service returns deterministic catalogue suggestions. No secret is stored in this repository. Provider output is reduced to text and existing record IDs; the server validates those IDs and produces local route URLs.

## Verification evidence

- `tests/Feature/StorefrontAdvisorTest.php`: 2 passed, 17 assertions; covers fallback, provider-result ID filtering, promotion immutability, validation and route throttling.
- PHP syntax checks passed for the new request, controller and service.
- `php artisan route:list --name=storefront-advisor` found the throttled POST endpoint.
- `npm run build` completed successfully. Existing unresolved runtime asset warnings for the coffee poster and authentication background remain.

Browser interaction and a real Gemini request were not run. No provider key was read or written.
