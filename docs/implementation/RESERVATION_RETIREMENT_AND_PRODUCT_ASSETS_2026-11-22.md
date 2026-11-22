# Reservation Retirement and Product Seed Assets — 22/11/2026

## Task Status

PARTIAL — source, routes, navigation, seed references, focused tests, and frontend build are complete. Browser visual verification and importing the SQL fixture were not run in this task.

## Summary

The active table-reservation workflow has been retired. The application now uses tables only for dine-in POS operations. Existing reservation schema and nullable historical `orders.reservation_id` are intentionally retained to avoid destructive changes to historical data.

Four separate product photographs were generated and stored in the project. The seed maps each stable demo product to its own local image path.

## Files Created

- `public/images/products/demo-latte-v2.png`
- `public/images/products/demo-ca-phe-sua-v2.png`
- `public/images/products/demo-tra-sua-v2.png`
- `public/images/products/demo-tra-tam-het-v2.png`
- `tests/Feature/ReservationRetirementTest.php`

## Files Modified

- `routes/web.php`, `routes/admin.php`, `routes/staff.php`
- `config/admin_navigation.php`, `config/admin_menu.php`
- Public, Admin, Staff layout/navigation views and homepage
- `app/Http/Controllers/Staff/WorkspaceController.php`
- `app/Services/TableStatusReadService.php`, `app/Services/CafeTableManagementService.php`
- `database/seed_data.sql`
- `requirements.md`, `plan.md`
- Focused navigation/worker tests

## Files Deleted

Reservation-only controllers, requests, services, Blade views, and feature/concurrency tests. The database migration and historical Eloquent models remain.

## Verification

- `php artisan test tests/Feature/AuthenticationTest.php tests/Feature/FixedAdminSidebarTest.php tests/Feature/ReservationRetirementTest.php`: 23 passed, 269 assertions.
- Static check confirmed no reservation route remains in `routes/web.php`, `routes/admin.php`, or `routes/staff.php`.
- Static check confirmed `seed_data.sql` has no reservation fixture or demo reservation ID.
- `npm.cmd run build`: passed. Existing unresolved runtime-asset warnings remain for `/assets/video/maison-coffee-poster.jpg` and `/images/auth-bg-golden.jpg`.
- PHP lint passed for the two changed table-status services.

## Risks and Unverified Items

- Existing databases retain historic reservation records and old `orders.reservation_id` values by design; no migration or destructive data cleanup was run.
- Seed SQL was not imported in this task, so MySQL import behavior remains unverified.
- Browser checks were not run, so final image cropping and all responsive menus need one manual acceptance pass after the updated seed is imported.
- Topping and ingredient tables do not have an image column in the current approved schema; no unsupported image mapping was invented.
