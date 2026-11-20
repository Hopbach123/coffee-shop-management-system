# Fixed Admin navigation

## Task Status
PARTIAL — implementation, targeted tests/build and live HTTP verification passed; browser visual acceptance remains unverified.

## Task ID
ADMIN-FIXED-NAV-001 / 2026-11-20

## Summary
User explicitly replaced the database-driven Admin sidebar with a complete fixed navigation. The actual layout now renders a fixed component backed only by source configuration. Empty/missing/edited admin_menus records cannot remove application navigation.

## Files Created
- `config/admin_navigation.php`
- `resources/views/components/internal/fixed-admin-sidebar.blade.php`
- `tests/Feature/FixedAdminSidebarTest.php`
- This report.
- Ignored live checker/results: `storage/logs/verify-fixed-navigation.py`, `fixed-navigation-live.txt`, `fixed-navigation-tests.json`.

## Files Modified
- `resources/views/layouts/internal.blade.php`: replaces the dynamic component and separate Dashboard link with one fixed component.
- `resources/css/internal/sneat.css`: scoped group headings, separators, labels and explanatory stock hint.
- `resources/views/admin/menus/index.blade.php`: explicitly identifies legacy menu data as unrelated to current sidebar.
- `tests/Feature/DynamicAdminSidebarTest.php`: legacy component assertions now render that component explicitly, while actual-page assertions expect fixed navigation.
- `requirements.md`, `frontend.md`, `plan.md`: document the explicit user change in navigation policy.

The worktree was already extensively modified. This task changes only the navigation areas described above, preserving prior dashboard, Catalog, Sales, Finance and UI work.

## Files Deleted
None.

## Implementation Details
Twelve real, parameterless Admin GET entry points grouped as:

| Group | Entry points |
| --- | --- |
| Tổng quan | Tổng quan cửa hàng |
| Thực đơn & công thức | Danh mục đồ uống; Sản phẩm; Size & công thức; Topping |
| Nguyên liệu & kho | Nguyên liệu & tồn kho |
| Không gian & đặt bàn | Khu vực quán; Quản lý bàn; Đặt bàn |
| Khách hàng & ưu đãi | Khách hàng; Khuyến mãi |
| Tài khoản | Nhân viên & tài khoản |

Recipe pages require a variant, so their entry point is the existing variant list with per-row recipe actions. Import, adjustment/stocktake and ledger pages require an ingredient; their entry point remains the ingredient list with per-row actions. A stock-group hint explains this workflow. No fake standalone warehouse/payment/report links are introduced.

Groups stay visible with separators; existing whole-sidebar desktop collapse and mobile drawer remain unchanged. Active patterns cover CRUD pages, recipe aliases and ingredient import/adjustment/history. Each supported context has exactly one active link and aria-current. Existing icons, tokens and 48px navigation targets are reused. No JavaScript/dependency added.

Staff keeps POS/orders/requests/brewing/tables/reservations in its own workspace. Existing Admin-only Mở POS/Về Quản trị switch is retained. The new component only renders for an active Admin; server middleware still owns access control.

Legacy AdminMenu data/model/services/controller/routes/components are preserved and not linked from the fixed sidebar. They no longer influence current application navigation. The old editor now says this explicitly. No migration, seed, source reset or data deletion.

## Tests / Checks Executed
- `php artisan test --compact --filter="AdminMenu|AdminSidebar|RoleAuthorization|Dashboard"`
- `node --test tests/Frontend/internal-shell.test.mjs`
- `npm run build`
- Read-only authenticated HTTP smoke of all 12 Admin destinations and Staff in the already-running isolated demo at `http://127.0.0.1:8000`.
- Targeted diff/whitespace review and source review.

## Test Results
83 passed, 1 skipped, 1003 assertions in targeted PHP tests. Three native Node shell tests passed. Build passed; pre-existing optional fontaine and unresolved public/auth image warnings remain.

New tests confirm no menu table or database queries are needed to render the component; all 12 configured routes are GET, parameterless, auth:web and role:admin; expected links appear; management/POS routes are not accidentally mixed into the component; recipe/inventory child routes activate the right parent exactly once; Staff/guest/inactive render no Admin links; real layout uses the fixed component.

Live: `/admin`, categories, products, product-variants, toppings, ingredients, areas, tables, reservations, customers, promotions and users all returned 200 with 12 fixed links and one active item. Staff returned 200 with separate Sales navigation and Admin return. Latest CSS bundles served correctly. This is HTTP/render evidence, not screenshot/browser evidence.

## Acceptance Criteria
- PASS: source-controlled fixed menu replaces database menu in the actual Admin layout.
- PASS: all 12 existing parameterless module entry points are covered.
- PASS: no admin_menus query for rendering fixed sidebar; legacy data cannot hide items.
- PASS: grouping, exact active state and Admin/Staff separation verified by source/tests/HTTP.
- PASS: existing authorization and shell-collapse behavior preserved by targeted tests.
- PASS: build and live destination checks.
- UNVERIFIED: real-browser visual/responsive/keyboard acceptance after the CSS change.

## Risks
New modules must add an entry to source configuration; the old menu editor cannot do so. Menu visibility does not replace backend authorization. Legacy endpoints remain reachable directly to preserve existing work; they are not current navigation configuration.

## Blockers
Established browser connection unavailable; not retried without an environment change. Visual QA remains pending.

## Assumptions
Current authorization model allows active Admins to access all configured Admin entry points. No new permission model or business route is invented.

## Out-of-Scope Findings
Existing unrelated worktree changes and build warnings left unchanged. A historical architecture-doc path referenced by requirements is missing; the current explicit user instruction and updated requirements/frontend now define navigation behavior. RTK unavailable in PATH; native commands used.

## Unverified Items
Browser screenshots and manual pointer/keyboard/responsive checks at standard widths; skipped check.

## Engineer Review Required
Open `/admin`, review all groups, follow Size & công thức and Nguyên liệu & tồn kho to per-record actions, and verify drawer/desktop collapse at normal zoom. Review current edits separately from earlier worktree changes.

## Recommended Next Task
Visual QA when browser access is restored. Do not automatically remove the legacy menu module or implement additional workflows. No commit/push/merge performed.


## V5 presentation refinement after destination pages — 2026-11-21

Implemented the user-supplied V5 reference on actual Admin pages before final sidebar styling. Fixed configuration remains 12 items / 6 groups; no dynamic-menu rollback or backend changes. Current detail supersedes earlier Admin presentation descriptions: [V5 implementation report](ADMIN_12_SCREEN_V5_REPORT.md) and [12-screen matrix](ADMIN_12_SCREEN_V5_MATRIX.md).

Verification: focused PHP 298 passed / 1 skipped; overlapping final form rerun 58 passed; 7 Node tests passed; build passed; HTTP roots/116 child pages/11 empty searches verified. Visual QA remains UNVERIFIED because CUA was policy-stopped; no real browser screenshots produced. An initial broader test filter exposed an unrelated Authentication fixture failure, recorded in the V5 report; no claim of a full green project suite.
