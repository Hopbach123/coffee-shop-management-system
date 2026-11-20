# Maison du Café — 12 Admin screens, V5 adaptation

## Task Status
PARTIAL — code implemented, focused tests/build/live HTTP checks passed; actual browser visual acceptance remains UNVERIFIED.

## Task ID
ADMIN-12-SCREEN-V5 / 2026-11-21

## Summary
User requested code implementation after reviewing the V5 ZIP and `F:/Prompt_TasteSkill/PROMPT_UIUX_TASTE_AGENT_SKILLS.md`: design Admin destination pages first, then match menu presentation. Read the ZIP README, master prompt, screen spec and contact sheet; inspected product/table screenshots. Extracted reference assets only to ignored `storage/app/ui-v5-reference/`; did not execute archive scripts or import prototype data/JS.

Actual skills used: `design-taste-frontend` for hierarchy/spacing/art direction and `frontend-ui-engineering` for scoped implementation, accessibility, responsive rules and verification. Landing-page photography/hero/animation rules do not apply to an operations dashboard.

## Files Created
- `resources/css/admin/v5.css`
- `resources/js/admin/catalog-view.js`
- `resources/views/admin/products/_actions.blade.php`
- `tests/Frontend/catalog-view.test.mjs`
- This report and `ADMIN_12_SCREEN_V5_MATRIX.md`.
- Ignored authoring/verification helpers and results in `storage/logs/`: apply/refine/finish/close-admin-v5 scripts, product-v5 template draft, route JSON, focused test JSON and live HTTP log.

## Files Modified
- `resources/views/layouts/internal.blade.php`: Admin-only V5 body class, excluding Staff and legacy menu editor.
- `resources/css/internal/sneat.css`: imports scoped V5 stylesheet.
- `resources/js/app.js`: guarded initialization of presentation-only catalog view switch.
- All 11 non-dashboard entry views under Admin categories/products/product-variants/toppings/ingredients/areas/tables/reservations/customers/promotions/users: shared hierarchy/toolbar/action presentation. Product, Area, Table and Promotion layouts were recomposed.
- Related child views in those modules plus product-recipes: only matching shared layout literals, button inline-style cleanup and canonical breadcrumb links changed. New `.v5-form-grid` replaces fixed inline two-column grids where matched. Existing field names, old values, errors, form actions, methods, tokens and conditions retained.
- `admin/products/_form.blade.php`: keyboard-accessible native file input retained alongside existing preview/upload contract.
- `admin/categories/_form.blade.php`, `admin/ingredients/_form.blade.php`: removed misleading upload controls not supported by their FormRequests/Controllers; preserved actual data fields and existing category-image preview.
- `tests/Feature/CategoryManagementTest.php`: stale shell-label assertion updated to the actual Admin identity.
- `frontend.md`, `plan.md` and the three existing UI reports/matrix: current scope and evidence.

The worktree contained extensive earlier edits. Git diff against HEAD includes earlier UI/Finance/Sales work; none of those backend edits were introduced by this task. No Controller, Service, Model, route, schema, seed, dependency manifest, permission or public/staff view was changed in this task. The source fixed-navigation configuration/component is unchanged.

## Files Deleted
None.

## Implementation Details

### Audit and decisions

| Evidence | Finding | Applied change |
| --- | --- | --- |
| `internal/sneat.css` + Admin inline markup | Legacy styles and many inline sizes compete; hierarchy varies | Explicit `.admin-v5` token/component layer in the existing bundle; replace matching inline layout/button declarations with classes |
| `products/index` | Separate inline `switchView` edits display/colors; table-first composition | Product gallery with real images/brand icon fallback; reusable actions; native buttons + aria-pressed; guarded progressive switch, storage failure fallback |
| `tables/index` | Long wide list mixes identity, status and many actions | Group the already-loaded page by area ID; readable status cards, per-table actions, unchanged expected_status/CSRF/methods; label counts as page counts |
| `areas/index`, `promotions/index` | All content presented as dense tables | Purposeful cards with real fields; Area links use the existing area_id table filter; Promotion conditions/dates/limits unchanged |
| Ingredient row | Import/adjustment/history plus destructive controls crowded | Native details action group, one ingredient context, unchanged availability conditions |
| Ingredient/category forms | Upload controls imply persistence unsupported by backend | Remove unsupported upload interactions; no backend feature invented |
| Fixed menu | Correct links already exist; small headings/dense hierarchy | Polish only after page layouts: spaced groups, separators, 48px targets, high-contrast active state; keep all 12 links and workspace behavior |

Chosen direction: restrained Premium Café Operations from V5, preserving actual data density. Ivory `#FFFEFA`, background `#F7F3EE`, espresso `#2D201A`, caramel `#AF764B`; a darker action `#95613D` for light button text. One existing Instrument Sans family, 24–28px page headings, 14px tables, 12px helpers, 16px card radius and soft shadow. Non-clickable cards do not lift. No brand assets from a famous café, new photography, fake KPIs, unsupported filters or prototype business actions were copied.

Twelve existing entry points keep their real backend. The prototype described three Dashboard KPIs, but current source already has four legitimate period metrics; retained them rather than deleting data or inventing another metric. V5 only changes their skin/spacing/nowrap behavior. Money/IDs stay readable; wide table/chart interiors scroll rather than shrinking the whole page.

Cards and controls stack at project breakpoints; paginated grouping does not claim to be a full venue floor plan. Expanded row actions remain in document flow so table overflow cannot clip a floating menu. Product file input is keyboard reachable. Product view controls remain hidden with JavaScript unavailable, leaving the working gallery visible; empty catalogs expose no inert toggle. Staff/Public and the legacy editor are outside V5 scope.

## Tests / Checks Executed
- Focused PHP class suites across fixed/legacy navigation, roles, Dashboard, Catalog/image/recipe, Ingredient/Inventory, stock workflows, Area/Table, Reservation, Customer/User and Promotion: command stored in `storage/logs/admin-v5-targeted-tests.json` evidence and run history.
- After final form fixes: `php artisan test --compact --filter="CategoryManagementTest|ProductManagementTest|ProductImageUploadTest|IngredientManagementTest|UserListingTest"`.
- `node --test tests/Frontend/catalog-view.test.mjs tests/Frontend/internal-shell.test.mjs`.
- `npm run build` and targeted `git diff --check`.
- Read-only authenticated HTTP checks against the existing isolated MySQL demo at `http://127.0.0.1:8000`: 12 entry screens, 116 discovered child GET forms/recipe/history/import/adjustment pages and 11 empty-search states. Standard demo login/session only; no migration, seed or business write.

## Test Results
- Focused PHP: **298 passed, 1 skipped, 4842 assertions** (299 total). A skip is not a pass.
- Final affected-form rerun: **58 passed, 944 assertions**; this is an overlapping subset, not 58 additional unique tests.
- JavaScript: **7 passed** (catalog toggle/default/invalid preference/blocked storage/empty state and existing sidebar collapse/drawer tests).
- Build passed. Existing optional fontaine and missing public/auth-image warnings remain; no package installed.
- HTTP: **140 successful checks** in the live log, including 12 Admin roots + Staff, 116 children and 11 empty searches. Fixed links/active item and Staff scope remain correct. New CSS/JS bundles served.
- Targeted diff whitespace check passed; existing CRLF normalization warnings only.

An initial broad method-name filter also selected `AuthenticationTest::test_admin_can_return_to_admin_area_from_public_home_without_losing_session`. It fails with 503 because its test DB creates users but not Dashboard statistic tables. This dependency exists before V5; no auth or Dashboard backend was changed here. Left that unrelated fixture issue unmodified. Another stale Category shell-label assertion was updated to the actual Admin label, then passed. Do not describe the full project suite as green.

## Acceptance Criteria
- PASS: real code adapted for all 12 Admin destinations and related forms; navigation remains 12 fixed entry points.
- PASS: gallery/card/table hierarchy and shared form/button/token structure implemented; actual backend interactions retained by focused tests and HTTP render checks.
- PASS: no copied demo data, fake price field, unsupported upload, DB change, new dependency, altered state workflow or auth bypass.
- PASS: build, native JS interaction tests and targeted PHP suites.
- UNVERIFIED: actual browser visual appearance, pointer/keyboard behavior, overflow and zoom200% at 375/390/768/1024/1280/1440px.

## Risks
CSS source and HTTP success do not prove visual quality. Legacy CSS remains for Staff/older pages; Admin V5 uses deliberate scoped specificity, with no !important overrides. Some contextual inline error/value styling remains by design; this is not a project-wide CSS rewrite. Prototype screenshot labels/data differ from the real schema and were not copied blindly.

## Blockers
Earlier CUA attempt returned “browser access host unavailable”, then native Edge access was stopped because the current URL could not be determined confidently. User explicitly requested continuing with code; no browser workaround or further CUA input was attempted. Visual QA therefore stays UNVERIFIED.

## Assumptions
The V5 ZIP authorizes full Admin rollout; current approved requirements and real backend take precedence over outdated prototype field/KPI assumptions. Existing 48px targets are retained rather than reducing to the prototype's 40px. Reservation UTC display/filter semantics are unchanged, not silently converted as a cosmetic change.

## Out-of-Scope Findings
Authentication test fixture dependency described above; existing unrelated dirty files, build image/font warnings and root stray file remain untouched. Legacy AdminMenu implementation is preserved but not used by fixed navigation.

## Unverified Items
All real-browser screenshots and visual gates, zoom and assistive-technology checks; skipped test; production data behavior and comprehensive MySQL boundary/concurrency acceptance are not established by this UI task.

## Engineer Review Required
Open `/admin`, refresh normally/with Ctrl+F5, review each fixed entry. Check product grid/table, file input, Table status action payloads, ingredient action disclosure, recipe/ledger forms and page-specific empty states. Compare real Laravel pages against the V5 reference at standard widths; do not use ZIP PNGs as acceptance screenshots.

## Recommended Next Task
Restore browser access and perform the listed visual gates. Do not automatically change backend rules or remove the legacy menu module. No commit, push or merge performed.


## User feedback refinement — 2026-11-21

Richer card colors/equal grids and horizontal row actions implemented after the tall-row screenshot. Root cause: legacy wrapping/mobile-stacking rules plus a 130px action column. Scoped Admin table reset and intrinsic-width nowrap actions preserve all existing forms. Targeted 84 tests passed, build and 12-destination HTTP passed. Visual QA remains UNVERIFIED. Details: [Card/action refinement report](ADMIN_CARD_ACTION_REFINEMENT_REPORT.md).
