# Task 9.2 — Modern Area Management UI

## Task Status
PARTIAL — implementation, targeted regression tests, MySQL read-only reconciliation and build complete. Browser acceptance at 1440/768/375px remains UNVERIFIED. READY FOR CODE REVIEW; not final UI acceptance.

## Task ID
Task9.2 / Area & Table POS Management — TASK 02.

## Summary
Replaced the Area listing table with responsive cards, filtered overview metrics and name/status filters. Reused the existing internal layout, buttons, CRUD endpoints and Admin middleware. View tables opens the existing Admin table list filtered by area_id.

## Files Created
- resources/css/admin/areas.css — scoped grid, filters, metrics, card presentation and responsive rules.
- tests/Feature/AreaManagementUITest.php — six tests for data, rendering, filters, security, errors and bounded query count.
- docs/task09/task9.2-report.md — this report.

## Files Modified
- app/Models/Area.php — reusable Eloquent scopes for filters and live-table counts.
- app/Http/Controllers/Admin/AreaController.php — index read queries, filter validation and unavailable state.
- resources/views/admin/areas/index.blade.php — new overview UI.
- resources/css/app.css — import scoped Area stylesheet.
- tests/Feature/AreaManagementTest.php — include existing cafe_tables migration in isolated fixtures because Area overview now reads table counts. Existing assertions retained.

## Files Deleted
None.

## Implementation Details
### Source Code Review / Module Overview
Current Area CRUD was already implemented, including status change and soft delete. AreaManagementService still rejects deletion when any linked table exists, including a soft-deleted table. None of these mutation actions or policies changed. Area extends the custom/generated model strategy; additions are only in the custom model. CafeTable uses SoftDeletes. Area::cafeTables uses cafe_tables.area_id. Generated Base classes were not modified.

Read task9.2, repository authority documents and task9.1 architecture results; inspected controllers, models, migrations, routes, internal layout, design tokens/components and Area tests before editing. Pre-code findings and mini plan were reported in commentary. Pre-existing untracked work: docs/area-table-pos-architecture.md and docs/task09/ (task prompts and task9.1 report). These files were preserved. No overlapping pre-existing source changes were present.

### Area Statistics / Data Contract
- Scope: matching nondeleted Areas, across all pages. With no status filter, both active and inactive Areas are included.
- Tables: only nondeleted cafe_tables linked to matching Areas. Tables belonging to deleted Areas are excluded.
- Metrics: areas_count, tables_count, available_count, occupied_count, maintenance_count. Count aliases are available on each paginated Area; summary contains the aggregate equivalents.
- No Area code exists. No reserved or waiting_payment metric is inferred.
- Unknown table statuses, if encountered, appear separately as total minus known mutually exclusive statuses. There is no attempt to recategorize them.
- withCount conditional subqueries avoid joins to Orders/Payments/Reservations and avoid count multiplication. Summary aggregates the filtered counted-Area subquery, independently of pagination.
- Sort remains created_at descending then id descending; 15 Areas/page. Search and status query parameters survive pagination.
- Search retains the existing SQL LIKE behavior; it is not a new exact or literal-substring policy.

### UI Implementation / Security
Header and Create action; five summary metrics; labeled search and active/inactive filter; cards with escaped name/description, Area status, total/status table counts, created date and existing actions. No fake links. Xem bàn uses admin.tables.index with area_id. Form actions retain CSRF tokens, HTTP methods and confirmation text. Delete policy is explained on each card; backend remains authoritative for soft-deleted linked tables and concurrent changes. Index remains Admin-only; Staff gets 403 and Guest redirects to login.

Layout already renders flash and validation messages, so the new page relies on those instead of duplicating messages. Empty database, empty Area, no filter results and out-of-range page states are covered. Query failure clears both summary/list and displays unavailable/retry instead of zeros. Log records a static message and SQLSTATE only, without exception SQL/bindings/search text. Loading state is N/A: synchronous server rendering, no async fetch introduced.

### Responsive Review / Accessibility
Scoped CSS reuses inherited Sneat/coffee theme tokens, internal cards and public buttons. Desktop grid: three columns; <=1200px two; <=768px one. Summary uses five/three/two columns. Mobile filters stack; minmax(0,1fr), wrapping actions and overflow-wrap accommodate narrow widths/long names. New controls have 48px touch height and visible focus. Search/filter labels, semantic headings/articles/dl, text plus symbols for table statuses, and contextual accessible action names are present.

Static CSS/HTML review completed. Actual browser layout, keyboard navigation and overflow at 1440/768/375px were not observed; these items remain UNVERIFIED, not PASS.

### Performance
Measured Area/table query count stays at 3 with 1 card and with 15 cards: summary, paginator count, paginated card query. This excludes unrelated layout/sidebar queries. No per-card/per-table queries, cache, new dependency or loading of Order/Payment data. Correlated counts still depend on database size; production-volume latency/EXPLAIN was not benchmarked. Separate read statements can reflect a concurrent change between summary and card reads; no realtime consistency guarantee is introduced.

## Tests / Checks Executed
PHP executable: D:/Applications/laragon/bin/php/php-8.3.33-Win32-vs16-x64/php.exe.
Node/npm: existing Laragon node-v22 toolchain.

| Test ID | Scenario / command | Expected | Actual |
| --- | --- | --- | --- |
| UI-01 | AreaManagementUITest counts/filter test | Known fixture counts, exclude soft deletes, summary across pages | PASS |
| UI-02 | Card/escaping/navigation/empty test | Escaped data, valid actions/table links, no read writes | PASS |
| UI-03 | Pagination test | Stable sort, preserve both filters, global filtered total | PASS |
| UI-04 | Validation/Auth test | Invalid input rejected, Guest redirect, Staff 403 | PASS |
| UI-05 | Query-count test | Fixed three Area/table reads as card count grows | PASS |
| UI-06 | Simulated query exception | Unavailable UI, null metrics, no sensitive SQL/log values | PASS |
| REG-01 | artisan test --compact --filter='AreaManagement\|AreaUpdate\|AreaIntegrity\|CafeTable' | Area/Table regressions pass | 38 PASS, 718 assertions |
| REG-02 | Same filter plus DynamicAdminSidebar\|RoleAuthorization | Layout/navigation/Auth regression | 58 PASS / 1 pre-existing FAIL, 857 assertions |
| DB-01 | Read-only local MySQL probe, invokes AreaController::index and compares against independent filtered table queries | Summary/cards match live records | PASS for all, active, inactive, no-match scopes |
| ROUTE-01 | artisan route:list --path=admin/areas | Existing seven routes | PASS |
| PHP-01 | php -l on changed PHP classes/new test | Valid syntax | PASS |
| STYLE-01 | Pint on changed PHP files | Local formatting | Applied import/line-ending/blank-line fixes |
| BUILD-01 | npm.cmd run build | Assets compile | PASS |
| DIFF-01 | git diff --check and changed-file review | No whitespace errors/unrelated changes | PASS; Git prints CRLF normalization notice |
| BROWSER-01 | 1440/768/375px, overflow, keyboard/action interaction | Correct responsive UI | NOT RUN / UNVERIFIED |

## Test Results
SQLite feature tests enforce sqlite/:memory: before running existing migrations into isolated fixtures. No live database migration, insert, update or delete was executed for testing.

MySQL read-only reconciliation on local connection:

| Scope | Areas | Tables | Available | Occupied | Maintenance |
| --- | ---: | ---: | ---: | ---: | ---: |
| All | 3 | 5 | 3 | 1 | 1 |
| Active | 1 | 2 | 2 | 0 | 0 |
| Inactive | 2 | 3 | 1 | 1 | 1 |
| No-match search | 0 | 0 | 0 | 0 | 0 |

Counts compared independently for each summary and each returned card. HTTP Blade rendering was tested with isolated SQLite data; a browser against live MySQL was not used. Initial new-test failure came from comparing a just-created model without hydrated database defaults; corrected by loading the original snapshot before comparison, retaining the read-only invariant assertion.

Build warnings: optional fontaine missing for optimized font fallbacks, and existing unresolved /assets/video/maison-coffee-poster.jpg path. Build exits successfully; no dependency was installed.

## Acceptance Criteria
| Criterion | Result | Evidence |
| --- | --- | --- |
| 1 | PASS | Task9.1/source review above |
| 2 | PASS | Custom Area model scopes |
| 3 | PASS | Existing CafeTable relationship with SoftDeletes |
| 4 | PASS | No schema changes in diff |
| 5 | PASS | New cards/filter/summary rendered in UI tests |
| 6 | PASS (structural) | Existing layouts.internal/theme/components reused; visual acceptance below |
| 7 | PASS (data) | Real MySQL counts/card data read successfully; browser integration unverified |
| 8 | PASS | Escaped actual Area names, UI-02 |
| 9 | PASS | UI-01, DB-01 Area totals |
| 10 | PASS | UI-01, DB-01 table totals |
| 11 | PASS | UI-01 per-Area counts, DB-01 independent comparison |
| 12 | PASS | Migration enum and conditional counts |
| 13 | PASS | Model/database data only |
| 14 | PASS | Relationship subqueries, no fanout joins |
| 15 | PASS | UI-06 unavailable state |
| 16 | PASS | Search tests and no-match state |
| 17 | PASS | Active/inactive filter tests |
| 18 | PASS | UI-02 empty database |
| 19 | PASS | UI-02 empty Area |
| 20 | PASS | Existing Create route and regression tests |
| 21 | PASS | Existing Edit route and regression tests |
| 22 | PASS | Existing Delete service/forms; linked/history refusal regressions |
| 23 | PASS | Existing admin.tables.index?area_id link |
| 24 | PASS | No routes added |
| 25 | PASS | UI-04/UI-06 plus existing flash/validation rendering |
| 26 | UNVERIFIED | Desktop CSS implemented; browser not run |
| 27 | UNVERIFIED | Tablet CSS implemented; browser not run |
| 28 | UNVERIFIED | Mobile CSS implemented; browser not run |
| 29 | UNVERIFIED | Static overflow protections; browser not run |
| 30 | PASS (markup) | Semantic labels, focus rules, text statuses; actual keyboard review unverified |
| 31 | PASS | UI-05 bounded query count |
| 32 | PASS | Area mutations unchanged, regression tests |
| 33 | PASS | Cafe Table source unchanged, regression tests |
| 34 | PASS | Order/Payment unchanged |
| 35 | PASS | Auth source/middleware unchanged |
| 36 | PASS | No migration created |
| 37 | PASS | No commit/push/merge |
| 38 | PASS | Six new feature tests |
| 39 | PASS | Exact results and limitations recorded |
| 40 | PASS | Required existing navigation/data available; floor-plan dependency documented |
| 41 | PASS | This report |
| 42 | UNVERIFIED | MySQL/statistics verified; final browser acceptance and known global Auth failure remain |
| 43 | PASS | No floor plan or POS claimed/implemented |

## Risks
- Responsive/keyboard acceptance requires actual browser review.
- Production data-volume query latency has not been benchmarked.
- Existing Admin→Staff 403 issue remains outside this task; it does not block the Area Admin route.

## Blockers
None for code implementation. Final visual acceptance remains pending environment/manual browser verification.

## Assumptions
Overview metrics follow the current filters across all pages, explicitly described in UI. This is a display scope only; no business policy changed. Existing latest-created ordering retained.

## Out-of-Scope Findings
RoleAuthorizationTest::test_admin_can_use_both_interfaces expects 200 but gets 403 at /staff; already documented in task9.1. No authorization repair attempted. Existing build warnings listed above.

## Unverified Items
Actual browser rendering/interactions at requested widths, real keyboard traversal, live-MySQL HTTP/browser rendering, production-volume performance, and the full repository suite (only relevant regression selection was run). Unknown table-status branch was statically reviewed; current MySQL enum contains only the three supported statuses.

## Engineer Review Required
Review changed files, scoped statistics, action labels and MySQL evidence. Log in as Admin and open http://127.0.0.1:8000/admin/areas; verify widths 1440/768/375, long text, filters, pagination and existing CRUD actions with disposable records. Inspect the known authorization failure separately. Code ready for review; overall task remains PARTIAL pending browser acceptance.

## Recommended Next Task
Task9.3 — Table Floor Plan & Status Cards, after review. Do not implement automatically.

### Next Task Handoff
Reuse Area filter/count scopes and scoped card patterns where appropriate. Existing navigation contract is GET /admin/tables?area_id={id}; future floor-plan integration must keep Admin authorization and existing table policies. Only persisted available/occupied/maintenance are currently verified; no reserved/Order-derived status contract is introduced by task9.2.
