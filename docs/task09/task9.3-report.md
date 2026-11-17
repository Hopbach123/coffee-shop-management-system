# Task9.3 — Table Floor Plan UI & Status Cards

## Task Status
PARTIAL — code, data reconciliation, Blade rendering, targeted tests and build complete. Actual responsive/keyboard browser acceptance remains UNVERIFIED. READY FOR CODE REVIEW, pending UI acceptance.

## Task ID
Task9.3 / Area & Table POS Management — TASK 03.

## Summary
Added a read-only floor-plan mode to the existing Admin table index, with real Area navigation, code/name search, persisted-status filtering, summary counts and responsive table cards. Existing table CRUD remains available in its original list mode. Area cards now link to the floor plan with the correct Area ID.

## Files Created
- resources/views/admin/tables/floor-plan.blade.php — floor-plan page, navigation, filters, states.
- resources/views/admin/tables/components/table-card.blade.php — read-only table article.
- resources/views/admin/tables/components/status-summary.blade.php — filtered summary.
- resources/css/admin/table-floor-plan.css — scoped styling and responsive rules.
- tests/Feature/TableFloorPlanTest.php — seven tests.
- docs/task09/task9.3-report.md — this report.

## Files Modified
- app/Http/Controllers/Admin/CafeTableController.php — read-only floor-mode branch and queries.
- app/Models/CafeTable.php — forFloorPlan filter scope in custom model.
- resources/views/admin/tables/index.blade.php — link to floor mode.
- resources/views/admin/areas/index.blade.php — add view=floor to existing Area table navigation; bind component URL once to preserve query separators.
- resources/css/app.css — import scoped floor-plan stylesheet.
- tests/Feature/AreaManagementUITest.php — update two navigation expectations for the integrated floor-plan URL.

## Files Deleted
None.

## Implementation Details
### Module Overview / Source Code Review
Inspected task9.3, task9.1 architecture, task9.2 implementation, model relationships, real migrations, CafeTableController, existing table list/tests, Admin routes/layout and existing status mapping. Repository authority documents were read during the preceding tasks in this same session and retained as context. Branch: feature/area-table-pos-ui.

At task start, task9.2 source edits and untracked architecture/task documents remained in the worktree. They were preserved. This task adds only the files/edits listed above; the pre-existing AreaController/Area model/Area stylesheet and AreaManagementTest fixture changes belong to task9.2. The app.css import and two Area navigation assertions intentionally extend that prior work. No reset, branch switch, commit, push or merge was performed.

Area schema: id, name, description, active/inactive status, soft deletes. CafeTable schema: id, area_id FK, unique table_code, optional table_name, capacity, available/occupied/maintenance status, soft deletes. Custom CafeTable::area includes trashed parents for management/history; floor-plan queries explicitly require a nondeleted parent. No generated Base classes were edited.

### Floor Plan Architecture
GET /admin/tables?view=floor enters the existing Admin table route and controller. All other existing index behavior remains unchanged. The private read method validates filters, constructs a scoped Eloquent query, aggregates counts, paginates/eager-loads Areas, loads Area navigation with counts and returns Blade. Basic read queries remain Controller → Eloquent; no service/repository/abstraction was introduced.

Blade includes separate summary and card partials. No Blade queries. No JavaScript, async fetch, polling, real-time subscription or new package. Area navigation uses ordinary links, not ARIA tabs requiring a JS keyboard model.

### Table Data Contract / Status Design
- Table identity: database id; table_code is display text, not a primary key.
- Card fields: table_code, optional table_name, capacity, related Area name and persisted status.
- Model aliases/casts are existing ones. Unknown/missing capacity displays an explicit fallback rather than fake zero.
- Persisted statuses/labels reuse CafeTableController::STATUS_LABELS: available = Bàn trống, occupied = Đang có khách, maintenance = Đang bảo trì.
- Status icon mapping: table/users/clock from the existing icon component. Border/icon colors use existing success/espresso/danger tokens, plus readable status text.
- Unknown/null table status gets the neutral unknown card and Chưa xác định label; summary shows total minus known statuses separately if needed.
- Derived POS status, reserved and waiting_payment are not defined/introduced. No Order/Payment/Reservation inspection or mutation.
- Inactive Areas remain visible for Admin observation and are explicitly labeled Ngừng hoạt động. A persisted available table is not advertised as guaranteed reservation availability.
- Nondeleted tables under deleted Areas are excluded from this view; the existing management list remains available for its historical scope. Schema requires Area FK; no fake Area is supplied for inconsistent data.

### Filtering / Search / Sorting
- Filter parameters: view=floor, area_id, status, search, page.
- area_id uses the real ID, requires an existing nondeleted Area, and rejects missing/deleted/malformed IDs through standard validation.
- status only accepts the three existing persisted values.
- search is trimmed by existing middleware, string max100, searches actual table_code OR table_name inside a grouped condition. Area/status conditions apply to both branches. Bound SQL LIKE retains conventional wildcard behavior.
- Area links preserve search/status and reset pagination. Filter submission resets pagination. Pagination preserves floor mode and all filters.
- Xóa lọc returns /admin/tables?view=floor. Quản lý bàn returns the original list.
- Stable existing sort retained: created_at DESC, id DESC, 15 tables per page. No natural-number ordering/new sort column added.
- Optional capacity filtering was omitted; capacity is displayed without introducing an additional rule.

### Statistics / Query Strategy
Summary counts match every current filter, across all pages. Only nondeleted tables with nondeleted Areas count, including inactive Areas. COUNT plus conditional SUM ensures mutually exclusive status groups without fanout joins.

Area-navigation counts are a separate, explicitly labeled scope: all nondeleted tables in that Area, regardless of search/status. Area::withCount loads navigation counts in one query. Table::with('area') eager-loads names/status for the page. No Order/Payment loads.

Measured Area/table query count is fixed at 5 for both 1 and 15 table cards: summary, paginator count, page, eager Area lookup, Area navigation/counts. Selecting an Area adds the existence-validation read. Empty pages can skip eager-loading. Counts exclude unrelated layout/sidebar queries. No production-volume latency guarantee; separate reads may see concurrent changes between statements.

### UI / Selection / States
The page has the existing Admin layout, header, back-to-Areas and management links, Area navigation with current state, filter controls, four summary cards and table grid. Name/code/Area text is escaped. Long text wraps.

No table-detail route suitable for this workflow currently exists. Cards are noninteractive article elements, do not imitate buttons and have no fake href or fake Order modal. The page explains the pending detail/Order integration. Task9.4 must supply an authorized read/detail route using the real table id; this task sends no selected-table mutation. Maintenance cards have explicit Bàn đang bảo trì text and no Order action. Existing mutation controls remain on the existing management list only.

States: no Areas, no tables, empty selected Area, no search/status results, out-of-range page, validation errors and query failure. Query failure clears cards/counts and presents unavailable/retry, never fake zero. Logs contain a static message and SQLSTATE, not exception SQL/bindings. Loading is N/A: synchronous rendering.

### Responsive / Accessibility / Security
Three-column desktop cards, two columns below1200px, one below768px. Summary four/two columns. Filters stack at768px. Area links wrap, controls have48px minimum height and visible keyboard focus; labels, semantic articles/headings/dl/nav and aria-current are present. Cards use text/icons/color together. No click handler on noninteractive cards.

Existing auth:web + role:admin middleware is reused. Guest redirects to login; Staff gets403. No role, permission, policy or middleware edits. No database writes in the display path. No new migration, schema edit or dependency.

Static HTML/CSS review completed. Browser dimensions1440/768/375px, actual overflow/touch/focus/navigation and contrast measurements remain UNVERIFIED.

## Tests / Checks Executed
Tools: Laragon PHP8.3.33, existing Node22/npm. Feature tests assert sqlite/:memory: before applying existing migrations to isolated fixtures. No live fixture inserts, live migrations or destructive database operations.

| ID | Scenario / expected result | Actual |
| --- | --- | --- |
| FP-01 | Summary/card/Area counts exclude soft deletes, include labeled inactive Area | PASS |
| FP-02 | Grouped name/code search + Area/status filters, correct Area links/current state | PASS |
| FP-03 | Stable order, 15/page, preserve mode and filters, summary spans pages | PASS |
| FP-04 | Empty states, escaping, no fake action, no write/Order/Payment/Reservation query | PASS |
| FP-05 | Guest/Staff denied, invalid/deleted Area/status/search rejected | PASS |
| FP-06 | Query count fixed at5 for1 and15 cards | PASS |
| FP-07 | Query exception produces unavailable UI, null counts, safe log | PASS |
| REG-01 | artisan test --compact --filter='TableFloorPlan\|AreaManagement\|AreaUpdate\|AreaIntegrity\|CafeTable' | 45 PASS,825 assertions |
| REG-02 | Same plus DynamicAdminSidebar\|RoleAuthorization | 65 PASS /1 pre-existing FAIL,964 assertions |
| DB-01 | Read-only MySQL controller data vs independent filtered SQL counts; render Blade against real data | PASS,8 scopes |
| ROUTE-01 | artisan route:list --path=admin/tables | PASS,existing7 routes; none added |
| PHP-01 | php -l changed controller/model/new test | PASS |
| STYLE-01 | vendor/bin/pint --test on task PHP files | PASS after local formatting |
| BUILD-01 | npm.cmd run build | PASS |
| DIFF-01 | git diff --check and manual diff review | PASS; CRLF normalization notice only |
| UI-01 | Browser1440/768/375px, keyboard/touch/overflow | NOT RUN / UNVERIFIED |

## Test Results
MySQL read-only probe invoked the real controller, independently checked each summary bucket and navigation count, and rendered Blade with real records. It used an in-memory Admin identity and an empty view error bag to supply normal rendering context without logging in/writing user data.

| Scope | Total tables |
| --- | ---: |
| All | 5 |
| available | 3 |
| occupied | 1 |
| maintenance | 1 |
| No-match search | 0 |
| Three individual Area scopes | 3,2,0 |

Initial new integration tests detected double escaping of multi-parameter component href URLs; fixed the affected links by using bound :href and reran successfully. Initial standalone MySQL rendering probe lacked the $errors normally shared by web middleware; supplied an empty ViewErrorBag in the probe and reran successfully. Neither required changes to shared components/layout.

The expanded suite still fails RoleAuthorizationTest::test_admin_can_use_both_interfaces: expected200, got403 at /staff. This is the known pre-existing Admin→Staff issue from task9.1/task9.2. No authorization repair in this scope.

Build emitted existing optional fontaine warning and unresolved /assets/video/maison-coffee-poster.jpg warning; compilation succeeded. No package installed. Full repository suite not run.

## Acceptance Criteria
| Criterion | Status | Evidence |
| --- | --- | --- |
| 1 | PASS | Task9.1/task9.2 context inspected |
| 2 | PASS | Existing migrations/models/FK/statuses |
| 3 | PASS | /admin/tables?view=floor |
| 4 | PASS | layouts.internal reused |
| 5 | PASS | DB-01 real MySQL tables |
| 6 | PASS | DB-01 real MySQL Areas |
| 7 | PASS | Grid markup/CSS and rendering tests |
| 8 | PASS | FP-01/FP-04 actual escaped codes |
| 9 | PASS | Eager Area mapping,FP-01/FP-02 |
| 10 | PASS | Capacity displayed,FP-01 |
| 11 | PASS | Persisted-status mapping,FP-01 |
| 12 | PASS | Existing3 statuses only |
| 13 | PASS | Database records only |
| 14 | PASS | Area links/filter,FP-02 |
| 15 | PASS | aria-current,FP-02 |
| 16 | PASS | Code/name search,FP-02 |
| 17 | PASS | Area ID filtering,FP-02/FP-05 |
| 18 | PASS | Existing status whitelist,FP-02/FP-05 |
| 19 | PASS | Clear link stays in floor mode |
| 20 | PASS | FP-01/FP-03/DB-01 total |
| 21 | PASS | FP-01/DB-01 status buckets |
| 22 | PASS | No fanout join,independent SQL reconciliation |
| 23 | PASS | FP-04 empty Area/database |
| 24 | PASS | FP-04 no-match/out-of-range |
| 25 | PASS | FP-05/FP-07 |
| 26 | PASS | Existing routes only,no fake links |
| 27 | PASS | Noninteractive cards with pending-integration notice per allowed fallback |
| 28 | PASS | No writes or Order query,FP-04 |
| 29 | PASS | Before/after table snapshot,FP-04 |
| 30 | PASS | Area mutation source untouched,regression |
| 31 | PASS | Existing table CRUD preserved,regression |
| 32 | PASS | Order/Payment source untouched |
| 33 | PASS | Reservation source untouched |
| 34 | PASS | Auth source untouched |
| 35 | PASS | FP-05 and existing route middleware |
| 36 | UNVERIFIED | Desktop CSS implemented; no browser review |
| 37 | UNVERIFIED | Tablet CSS implemented; no browser review |
| 38 | UNVERIFIED | Mobile CSS implemented; no browser review |
| 39 | UNVERIFIED | Wrapping/minmax safeguards; no browser overflow observation |
| 40 | PASS (markup) | Labels,semantics,focus rules,text+icon; real keyboard acceptance unverified |
| 41 | PASS | FP-06 fixed5 reads |
| 42 | PASS | No migration |
| 43 | PASS | No schema edit |
| 44 | PASS | sqlite/:memory: guards; MySQL probe read-only |
| 45 | PASS | No commit/push/merge |
| 46 | PASS | Exact observed results/limitations recorded |
| 47 | PASS | This report |
| 48 | UNVERIFIED | Data/function verified; final browser acceptance pending |
| 49 | PASS | Detail-route dependency explicitly documented |
| 50 | PASS | No Order/Payment/receipt implementation or completion claim |

## Risks
Actual browser acceptance remains outstanding. Existing Admin→Staff403 issue persists outside the Admin floor-plan path. No production-volume benchmark. Several independent count/page reads are not a realtime snapshot.

## Blockers
None for the implemented floor-plan display. Detail route/selection workflow belongs to task9.4; this task uses the permitted pending-integration fallback. Final visual acceptance requires browser review.

## Assumptions
Keep existing latest-created table ordering and original CRUD list. Floor mode observes inactive Areas but excludes deleted Areas/tables, consistent with task9.2 count scope. Navigation totals are independent of search/status, explicitly labeled.

## Out-of-Scope Findings
Known Admin→Staff authorization test failure. Existing build warnings. Derived Order/Payment/Reservation table status policy remains undefined and unused.

## Unverified Items
Browser1440/768/375px, actual keyboard/touch interaction, measured contrast/overflow, production-volume latency, full repository suite. Unknown-status fallback is implemented but real MySQL currently contains only the supported enum values; a corrupt-record browser case was not manufactured in the live database.

## Engineer Review Required
Review the scoped source diff and pre-existing task9.2 work separately. Log in as Admin and open http://127.0.0.1:8000/admin/tables?view=floor; test Area links, search/status combination, pagination, long names, responsive widths and return to Quản lý bàn. Review the chosen static-card fallback before implementing a detail route.

## Recommended Next Task
Task9.4 — Table Detail & Order Entry integration planning/implementation within its own approved scope. Do not implement automatically.

### Next Task Handoff
Selected table identity must be cafe_tables.id. No selected state is persisted or sent by this task; table cards are currently read-only. Add/reuse an authorized read/detail route only after inspecting task9.4. Keep current persisted-status source and existing mutation services; no status inference, reservation availability guarantee or Order creation is introduced here. Area entry URL contract: /admin/tables?view=floor&area_id={id}; original /admin/tables remains CRUD management.
