# Dashboard consolidation

## Task Status
PARTIAL — implementation and targeted checks passed; visual browser QA unverified.

## Task ID
ADMIN-DASHBOARD-ONE-2026-11-20

## Summary
Corrected after user clarification: keep `/admin` as the canonical dashboard with the original welcome/overview/recent-order composition. `/admin/dashboard` redirects to `/admin`; its old view is only a compatibility include. Daily chart and all-time statistics are integrated into admin.home. Added a 7/30-day completed-order value chart with zero days, totals and an accessible detail table. Existing all-time Order, paid Payment and Table statistics remain separate.

## Files Created
This report.

## Files Modified
DashboardController.php; RevenueStatisticsService.php; admin/dashboard/index.blade.php; admin/dashboard.css; DashboardOrderStatisticsTest.php; DashboardIntegrationTest.php; DashboardTableStatisticsTest.php; RoleAuthorizationTest.php. These files include pre-existing edits; this task only adds consolidation, daily chart and corresponding assertions.

## Files Deleted
None.

## Implementation Details
Read-only aggregates, no schema or database mutations. Completion date determines chart buckets in application timezone. Chart values represent completed Order values, not paid cash receipts, net revenue or profit. Unsupported/array period inputs fall back to 7 days. Missing chart columns show an unavailable message. Query failures use the existing 503 view. No dependencies added. No business write workflows changed.

## Tests / Checks Executed
`php artisan test --compact --filter="Dashboard|RoleAuthorizationTest"`; `npm run build`; Git diff review/check.

## Test Results
37 passed, 1 skipped, 341 assertions. Frontend build passed with existing optional fontaine and unresolved image warnings. Whole-worktree diff check reports whitespace in pre-existing unrelated kitchen/public/online-request files; left unchanged.

## Acceptance Criteria
PASS: one served dashboard; 7/30-day chart; zero days; completion-date boundaries; cancelled orders excluded; invalid filter fallback; unchanged paid aggregate and role guards; no database writes.
UNVERIFIED: actual browser appearance/responsive behavior.

## Risks
Application timezone governs daily buckets; not an accounting revenue report. Existing user changes elsewhere remain present.

## Blockers
Browser access unavailable in the established session; not retried without an environment change.

## Assumptions
Retain the existing completed-order interpretation of the older visual dashboard, with an explicit label distinct from paid receipts.

## Out-of-Scope Findings
Existing build image warnings and unrelated whitespace issues.

## Unverified Items
Browser visual QA, real MySQL daily aggregate execution and skipped check.

## Engineer Review Required
Review merged dashboard and money labels at normal browser zoom before UI acceptance.

## Recommended Next Task
Browser visual QA at desktop/mobile widths. Do not implement automatically.

## User correction
The previous choice of canonical endpoint was incorrect. Restored admin.home, integrated the daily chart and statistics there, kept recent orders and welcome section. No source or user data deleted.
