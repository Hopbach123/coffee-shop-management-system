# Promotion, Checkout, Payment and Staff Approval

**Task ID:** `CAFE-PROMO-CHECKOUT-001`  
**Status:** PARTIAL — implementation and targeted automated checks are complete; MySQL and browser acceptance remain unverified.

## Implemented flow

1. An active, in-window, non-exhausted promotion is available at `/promotions`. The guest can copy its code or save it in the current browser session; neither action changes `used_count`.
2. The public delivery checkout accepts at most one session-held code. `CustomerOrderService` calls `PromotionService` to calculate the server-side discount, then renders subtotal, discount, delivery fee and final total.
3. Submission stores `promotion_code` and `promotion_discount` on `customer_order_requests`; the amount check is now `subtotal - discount + delivery_fee`.
4. Staff acceptance rebuilds the catalog quote and rechecks the promotion. A changed/expired/ineligible code blocks acceptance instead of silently changing the customer's total. A valid code is applied through the existing `PromotionService`, which creates the immutable `order_promotions` snapshot.
5. Payment remains owned by `PaymentService`: quota is locked and consumed only with the first successful payment. Prepaid transfer still requires actual Staff confirmation before preparation; COD remains unpaid until confirmed collection at delivery. Completion remains governed by the existing paid + served rules.

## Files created

- `app/Http/Controllers/Public/PromotionController.php`
- `resources/views/public/promotions/index.blade.php`
- `database/migrations/2026_11_22_000001_add_promotion_snapshot_to_customer_order_requests.php`
- `docs/implementation/PROMOTION_CHECKOUT_STAFF_APPROVAL_2026-11-22.md`

## Files modified

- `app/Services/PromotionService.php`
- `app/Services/CustomerOrderService.php`
- `app/Http/Controllers/Public/CustomerOrderController.php`
- `app/Models/CustomerOrderRequest.php`
- `routes/web.php`
- `resources/views/public/orders/create.blade.php`
- `resources/views/public/orders/show.blade.php`
- `resources/views/staff/customer-orders/index.blade.php`
- `resources/views/staff/customer-orders/show.blade.php`
- `resources/css/public/storefront.css`
- `tests/Feature/CustomerOrderWorkflowTest.php`
- `requirements.md`
- `plan.md`

## Checks executed

- PHP lint: controllers, services and new migration — passed.
- Route discovery for customer checkout/Staff request routes — passed.
- `php artisan test tests/Feature/CustomerOrderWorkflowTest.php tests/Feature/PromotionServiceTest.php tests/Feature/PaymentWorkflowTest.php` — **60 passed, 614 assertions**.
- Focused payment/completion regression — **15 passed, 1 MySQL-only test skipped**.
- MySQL-focused QA command — **3 skipped** because the disposable MySQL QA environment was not available; no project database was migrated.
- `npm.cmd run build` — passed. Vite reports pre-existing unresolved runtime asset warnings for `/assets/video/maison-coffee-poster.jpg` and `/images/auth-bg-golden.jpg`.
- Browser review — blocked because the Computer Use native browser pipe was unavailable. No browser claim is made.

## Acceptance trace

| Requirement | Evidence | Status |
| --- | --- | --- |
| Public browse/copy/session-save | `PromotionController`, public view, Customer Order feature test | PASS (automated) |
| One backend-validated code and final amount | `CustomerOrderService::review/submit`, checkout view | PASS (automated) |
| Expiry, minimum, capped percent, quota | existing `PromotionService` suite plus new checkout path | PASS (automated) |
| Changed promotion blocks Staff acceptance | `CustomerOrderService::accept`, feature test | PASS (automated) |
| Last-use race / one paid usage | existing `PromotionService` and payment transaction tests | PASS (SQLite); MySQL concurrency unverified |
| Bank/COD/duplicate/completion controls | existing Payment and Customer Order workflow tests | PASS (automated) |
| Dashboard figures | dashboard MySQL regression was skipped because the disposable MySQL QA environment is unavailable | UNVERIFIED |
| Real browser flow | native browser pipe unavailable | BLOCKED |

## Constraints and follow-up

- The change deliberately preserves the separate legacy pickup checkout and the existing Staff payment history screen. No old route/table was removed.
- Session save is MVP-only and is not an account-backed coupon wallet.
- Staff receipt confirmation is still separately audited by the existing payment form/service; no payment is created merely by accepting an order or selecting a method.
- Before presentation, apply the migration only to the intended development database after backup, then run the public promotion → checkout → Staff transfer/COD flows in a real browser and the disposable MySQL concurrency QA.
