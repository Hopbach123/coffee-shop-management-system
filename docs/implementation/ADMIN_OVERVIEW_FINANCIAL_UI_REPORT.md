# Admin overview — financial dashboard refinement

## Task Status
PARTIAL. Source, targeted tests, frontend build and live authenticated HTTP verification passed. Actual browser visual/responsive acceptance remains UNVERIFIED.

## Task ID
ADMIN-OVERVIEW-FINANCIAL-UI-001 / 2026-11-20

## Summary
User requested a bank-dashboard-inspired presentation while retaining `/admin`. Implemented information hierarchy in Maison du Café colors: period controls, four period KPIs, a large daily line chart, an operational side column, recent orders and expandable all-time statistics. `/admin/dashboard` remains a redirect, not another dashboard.

## Files Created
- `app/Services/Dashboard/PeriodStatisticsService.php`
- `resources/views/admin/dashboard/_all-time.blade.php` — extracted existing statistics, not a second page.
- `tests/Feature/DashboardPeriodStatisticsTest.php`
- This report.
- Ignored verification helpers/results in `storage/logs/`: `extract-dashboard-summary.py`, `finalize-bank-overview.py`, `verify-bank-overview.py`, `bank-dashboard-tests.json`, `bank-dashboard-live-check.txt`.

## Files Modified
- `app/Http/Controllers/Admin/DashboardController.php`: read-only aggregation coordination; recent orders ordered deterministically.
- `resources/views/admin/home.blade.php`: canonical overview layout.
- `resources/css/admin/dashboard.css`: scoped dashboard styling and responsive breakpoints.
- `tests/Feature/DashboardOrderStatisticsTest.php`: view assertions target the new period payload.
- `requirements.md`, `plan.md`: record the user-approved period, metrics and UI scope.
- `docs/implementation/UI_SCREEN_MATRIX.md`: canonical dashboard row/evidence.

These tracked files already contained prior work. This task preserves unrelated changes; the complete Git diff includes earlier consolidation and other contributors' work.

## Files Deleted
None from the pre-existing source. A temporary authoring file `dashboard/overview.blade.php` was moved into canonical `admin/home.blade.php` within the task; no second page remains.

## Implementation Details
Design contract: the Admin reads the period and money first, inspects the daily trend, then opens Sales operations. Keep espresso/cream/caramel, existing font and icon system. Reject fake progress, arbitrary growth percentages, decorative rings, large welcome artwork and copied bank branding. Four KPI cards contain real metrics with their recording basis. Side-column state bars use actual counts over period-created orders; current table statistics are explicitly separate. Mobile stacks the content; only chart/table interiors scroll. Controls retain 48px targets, visible labels/focus and native GET navigation; no new JavaScript/dependency.

Time: 7 or 30 local days including today, Asia/Ho_Chi_Minh. UTC bounds filter the stored timestamps; SQL groups UTC timestamps into UTC+7 days. Payment totals use `status=paid` and `paid_at`; completed Order values use `completed_at`; created-order counts use `ordered_at`. Averages use completed Order values/counts, truncated below one currency cent. Cash and completed totals are intentionally different measures. Refunded/failed/pending Payment rows are excluded; this is not net revenue or profit.

Existing all-time Payment duplicate verification is reused. A paid row missing `paid_at` makes period receipt statistics unavailable rather than apparently zero. Missing daily schema columns show a specific unavailable state without fabricating KPIs. Aggregates remain read-only; zero days are filled. Money sums use integer cents after validating SQL decimal strings; chart geometry alone uses floating point. Existing exact decimal-string VND partial is reused in KPI/table/tooltips. Unknown order states are disclosed.

SVG line chart: y-axis money units, daily x-axis, proportional scale, native title plus keyboard-focus tooltip, empty-period message and an expandable accessible data table. Paid/completed links retain the selected period. Invalid/array filter values fall back to 7 days and paid. No chart library or schema change.

## Tests / Checks Executed
- `php artisan test --compact --filter="Dashboard|RoleAuthorizationTest"`
- `npm run build`
- Authenticated HTTP verification against existing isolated MySQL demo at `http://127.0.0.1:8000`; no migration/seed or business writes. Login creates the usual demo session.
- Targeted Git diff/whitespace review; PHP source and rendered HTML reviewed.

## Test Results
42 passed, 1 skipped, 449 assertions. New tests cover Vietnam-midnight boundaries, distinct receipt/completion dates, excluded statuses, zero days, missing paid dates, unverified payments, read-only queries and filter/alias UI behavior. A skipped test is not a pass.

Frontend build passed. Existing warnings remain: optional fontaine package, unresolved video poster/auth image references outside this dashboard. No package was installed.

Live HTTP: paid/7 days -> 200 with 7 SVG points; completed/30 days -> 200 with 30 points; array filters -> 200 with 7-point fallback. Current app CSS served successfully. Alias resolves to `/admin`. This executes the MySQL daily-query path on existing demo data; it does not prove full MySQL boundary/precision acceptance or visual quality.

## Acceptance Criteria
- PASS: `/admin` retained; old dashboard URL is a redirect.
- PASS: period controls and period KPI definitions agree.
- PASS: verified paid/complete values are separated and zero days retained.
- PASS: Vietnam-day boundaries and invalid-filter fallback covered by tests.
- PASS: chart/table/empty/unverified states render, read-only behavior and authorization tests pass.
- PASS: CSS scope remains the dashboard; no migration, dependency, Sales/Finance write workflow changes.
- UNVERIFIED: visual layout, pointer/keyboard behavior and contrast in a real browser at 320/768/1024/1440 widths.

## Risks
- Stored timestamps must follow the project's UTC convention; no global timezone/config was changed.
- Payment paid-state totals exclude subsequently refunded records, so they are not a historical cash ledger or accounting revenue.
- Independent aggregate reads can observe changes occurring during a page load; page is an operational snapshot, not an accounting close.
- SQLite does not prove MySQL DECIMAL behavior for all sums. Invalid aggregates fail safely.

## Blockers
Established browser automation connection unavailable; not repeatedly retried without an environment change. UI acceptance remains partial for this reason.

## Assumptions
User-approved 7/30-day ranges retained. Bank references guide information hierarchy only; bank branding/assets/layout were not copied. Official reference PDF could not be opened fully by the research tool because it exceeds its fetch-size limit; no claim of pixel matching.

## Out-of-Scope Findings
Pre-existing unrelated changes, image/font build warnings and the untracked root file `last()['amount']` were left alone. RTK is prescribed but unavailable in PATH; ordinary native commands used.

## Unverified Items
Real-browser screenshots/responsive/accessibility checks; comprehensive MySQL precision and date-boundary suite; skipped check.

## Engineer Review Required
Review `/admin` at normal zoom, switch 7/30 days and paid/completed, open daily data and all-time statistics, then confirm mobile and keyboard behavior. Review current task edits separately from existing worktree changes.

## Recommended Next Task
Browser visual QA once the browser connection is restored. Do not implement other modules automatically. No commit, push or merge performed.
