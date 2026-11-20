# Ninh Integration 03 — 17/11/2026

## Task Status
**PARTIAL / FINANCE BLOCKED / MYSQL NOT_RUN / UI NOT_RUN.** Không nghiệm thu end-to-end N01–N06.

## Task ID
NINH-INTEGRATION-03

## Git branch/status
Nhánh thực tế `feature/order-requests-scheduled-pickup`; HEAD `fb7e77fd1feee1592f7aa5a1ae20b0ec127696ed`.
Worktree có thay đổi từ Integration 01/02; đã được owner cho phép dùng làm nền. Không switch/reset/stash/clean/commit/push/merge. Không coi toàn bộ diff với HEAD là thay đổi mới của lượt 03.

## Existing code reused
Models request/Order/Payment; Cart line signature; OrderService create/createWithItems/addItem/accept/transition/complete; InventoryService::startPreparing; ScheduledPickupService; routes và layout/component hiện có. Không sửa InventoryService, Payment business logic hoặc migration.

## Files Created
- `app/Services/OnlineRequestSubmissionService.php` — transaction request/items/toppings, unique-key conflict/replay validation.
- `docs/implementation/FINANCE_CONTRACT_REQUIRED.md`.
- Report này.

## Files Modified — riêng lượt 03
- `app/Http/Controllers/Public/CartController.php`: intent/quote lifecycle, delegate request persistence, invalid options, invalid cart recovery.
- `app/Http/Controllers/Staff/PosController.php`: server intent/replay calling Sales command.
- `app/Services/OrderService.php`: paid addItem guard, POS once command, category guard, exact settlement completion.
- `app/Models/Order.php`: so đủ tiền bằng integer cents, không float.
- `resources/views/public/cart/{checkout,index}.blade.php`: hidden intent, bỏ fixed two-column inline grid, giữ/xóa giỏ không hợp lệ.
- `resources/views/staff/home.blade.php`: hidden POS intent.
- `tests/Feature/OnlineOrderNinhHopWorkflowTest.php`: thêm 7 ca và cập nhật re-confirm bằng checkout quote mới.
- `tests/Feature/PosOrderWorkflowTest.php`: session fixture có intent giống giao diện thật.
- `NINH_CODE_REVIEW.md`, `NINH_HOP_PROGRESS.md`, `NINH_HOP_FINAL_REPORT.md`: thêm liên kết cập nhật trạng thái, không xóa báo cáo cũ.

## Files Deleted
None.

## Fixed bugs / implementation
1. **Request idempotency:** intent gồm key và random tracking token được tạo phía server và giữ trong session. Cart mutation xoay intent, checkout cùng ý định giữ key; form gửi key để phát hiện tab cũ. Retry sau thành công dùng quote đóng băng còn trong session; không tạo key ngẫu nhiên mới mỗi POST. Service dùng UNIQUE submission_key làm trọng tài DB; transaction rollback items/toppings khi insert thua tranh chấp, đọc request thắng và kiểm payload. Cùng key khác liên hệ/loại mua/giờ nhận/payment preference/note/items/toppings bị từ chối. Replay không sửa request đã accepted/rejected. Mất session thì không truy cập lại bằng key đơn lẻ.
2. **Quote:** lưu snapshot từng dòng tại GET checkout; so lại với Catalog server khi submit, kể cả hai giá đổi ngược chiều mà tổng không đổi. Checkbox frontend không vượt được stale quote: phải mở checkout để xem báo giá mới. Topping ngừng bán bị chặn ngay tại add; món/topping mất khả dụng sau đó không bị âm thầm bỏ khỏi request. Giỏ lỗi được giữ, có action xóa dòng; không redirect loop. Các giá frontend không được lưu.
3. **POS replay:** server operation key; createPosOnce khóa actor rồi kiểm order_code xác định từ actor/key. Order code UNIQUE; replay trả đúng Order, payload khác bị từ chối. add/remove POS xoay key và bỏ receipt cũ; checkout thành công giữ items phục vụ retry. Không tạo bảng hoặc cột idempotency mới. Direct HTTP phải có session intent từ POS/add, không phát sinh key mới tại mỗi POST.
4. **Paid guard:** addItem đã kiểm payment paid dưới Order lock như các đường edit/remove hiện có; không cho sửa tổng sau paid. Category inactive cũng bị chặn khi thêm món từ Sales. Snapshot giá/món của dòng cũ không bị cập nhật theo catalog mới.
5. **Settlement:** Order::isPaidInFull so cents nguyên và giảm remaining để tránh cộng overflow; complete khóa/tải paid rows rồi gọi cùng read rule. Tách được một cent ở giá trị vượt độ chính xác float. Không đổi Payment status, không fake writer; recordPayment vẫn fail closed.
6. **N05 HTTP:** test pending→preparing→ready→served với Finance fixture, sai mã, thiếu tiền, guest, sai transition, served replay, nhiều item và complete guard. Chỉ item được thao tác đổi state; ledger consume vẫn một dòng. Không có StockMovement Model mới.

## API / UI
Giữ route names/methods; thêm hidden submission_key vào checkout/POS, validation string length cho input. Không tin key client tự tạo: phải khớp session intent. Checkout dùng commerce-layout đã có thay fixed width 340px; không đổi shared CSS. UI N05 vẫn dùng actions của Integration 02, test gọi HTTP thật chứ không chỉ kiểm chuỗi Blade.

## Tests / Checks Executed
- PHP 8.3.30 Laragon; SQLite `:memory:` theo phpunit.xml. Không migrate:fresh, không ghi DB dự án.
- Targeted rộng: **59 tests PASS, 486 assertions**, log `storage/logs/ninh03-targeted.json`.
- Targeted cuối Cart/POS/fulfillment: **28 tests PASS, 206 assertions**, `storage/logs/ninh03-targeted-final.json` (gồm recovery giỏ invalid).
- Full cuối: **448 total = 440 passed + 8 skipped**, 6049 assertions, 0 fail/error, ~29.3s, exit 0; `storage/logs/ninh03-regression.json`.
- Full đầu phát hiện 2 fixture POS tạo giỏ trực tiếp không có intent; đã bổ sung session intent, giữ nguyên test rollback table/variant. Rerun sau sửa này và recovery UI có lý do, không thay rule runtime để test xanh.
- Pint trên danh sách PHP thay đổi: PASS. `git diff --check`: PASS, chỉ cảnh báo CRLF/LF.
- `npm run build`: PASS; cảnh báo cũ fontaine optional và hai ảnh runtime vẫn còn. Build không là bằng chứng browser.

## New tests
Trong OnlineOrderNinhHopWorkflowTest: submit retry + changed payload; rotate intent/merge same signature; same-total/different-line prices; unavailable topping/product + recovery UI; handover HTTP/payment/code/replay/other item/paid edit; duplicate POS operation; exact one-cent settlement. Existing accept replay/no consume/thiếu tồn và completion tests tiếp tục chạy.
MySQL submit/accept đa process **chưa được chứng minh**. Test replay SQLite không phải concurrency test. Chưa bổ sung runner đa process riêng cho hai command mới; đây là coverage còn lại, không gọi PASS.

## Test Results / Acceptance Criteria
| UC | Before | Change | Test Evidence | Status |
| --- | --- | --- | --- | --- |
| N01 | Random submission mỗi POST, chỉ so tổng | Stable intent, transaction/replay payload, quote từng dòng, invalid options/recovery | HTTP retry/rotate/equal-total/availability | PASS phạm vi targeted; concurrency MySQL UNVERIFIED |
| N02 | addItem sau paid chưa chặn, POS retry chưa có | Paid guard, actor-lock POS intent, category guard | Add/update/remove after paid, POS replay, POS rollback fixture | PASS targeted; Reservation integration chưa chứng minh |
| N03 | Accept request lock + unique có sẵn | Tận dụng, không consume accept | Existing accept replay/no consume | MYSQL concurrent accept NOT_RUN |
| N04 | Service/config từ lượt 02 | Giữ nguyên timezone/accept recheck | ScheduledPickupTimeTest trong suite | Giờ quán vẫn BLOCKED chờ Toàn |
| N05 | Chưa có test mã nhận HTTP | Thêm HTTP lifecycle/payment/code/replay/other item | Test HTTP + ledger assertions | PASS targeted; MySQL tranh tồn/browser NOT_RUN |
| N06 | Float settlement/read writer thiếu | Exact read, giữ fail-closed, contract bàn giao | Large-value cent, complete guards, writer blocked | FINANCE BLOCKED |

## MySQL verification
**NOT_RUN**: chưa có dedicated database được xác minh/cấu hình. Kiểm tra listener local không thấy :3306/:8000 tại lúc rà; không suy ra mọi port MySQL đều ngừng. Không gọi migrate:status trên DB dự án chỉ để có kết quả. Migrate:status lần này NOT_RUN vì chưa xác nhận target riêng.

8 skipped tách rõ:
| Test class | Số | ENV / điều kiện |
| --- | ---: | --- |
| OnlinePickupSchemaMySqlTest | 3 | ONLINE_SCHEMA_MYSQL_TEST=1; tự tạo/drop duy nhất online_schema_test_<random> |
| InventoryImportConcurrencyMySqlTest | 3 | INVENTORY_IMPORT_MYSQL_TEST=1; tự tạo/drop duy nhất inventory_import_test_<random> |
| ReservationConcurrencyTest | 1 | RESERVATION_TEST_MYSQL_DATABASE=coffee_reservation_test_<suffix>, DB riêng rỗng |
| DashboardRevenueMySqlTest | 1 | DASHBOARD_TEST_MYSQL_DATABASE=coffee_dashboard_test_<suffix>, DB riêng rỗng |

Chỉ sau khi xác minh account/host MySQL test, quyền tạo/drop prefix và không target DB dự án:
```powershell
# Dùng PHP Laragon hoặc php đã nằm trong PATH; không copy credential vào report.
$env:ONLINE_SCHEMA_MYSQL_TEST = '1'
php artisan test --filter=OnlinePickupSchemaMySqlTest
$env:INVENTORY_IMPORT_MYSQL_TEST = '1'
php artisan test --filter=InventoryImportConcurrencyMySqlTest
```
Hai nhóm còn lại cần host/port/database/username/password qua RESERVATION_TEST_MYSQL_* và DASHBOARD_TEST_MYSQL_*; dùng đúng suffix test và DB rỗng, không chia sẻ DB có dữ liệu. Sau cấu hình connection test riêng, xác minh database name thực tế rồi mới `php artisan migrate:status --database=<connection-test>`; không dùng default mysql khi chưa xác minh. Không chạy migrate:fresh. Các test hiện có không thay thế runner mới cho concurrent submit/accept; cần thêm/run khi môi trường bàn giao.

## Browser verification
**UI NOT_RUN** ở 375/768/1440 và keyboard. Đã gọi inventory computer-use; browser access host báo unavailable. Native Edge/Brave có trong inventory nhưng chưa có ứng dụng/test server chạy ở port dự kiến và chưa có dữ liệu/Finance để demo an toàn end-to-end. Không tuyên bố browser không tồn tại hoặc UI PASS. Chưa tương tác/ghi đơn vào DB local qua browser. Tất cả trang Menu/Cart/Checkout/POS/inbox/queue/detail/tracking vẫn cần browser QA theo checklist prompt.

## Finance contract status
**BLOCKED**, không tìm thấy PaymentService thực tế. Xem FINANCE_CONTRACT_REQUIRED.md: cash/bank confirm, paid amount/reference, exact strings, idempotency/conflict và lock settlement. Không thêm interface/adapter giả hoặc insert Payment vào DB thực. Fixture chỉ trong tests.

## Risks / Remaining blockers
- Chưa có multi-process submit/accept proof MySQL; unique-key và actor/request locks mới là evidence từ source/SQLite replay.
- Giờ quán chờ Toàn xác nhận, không fallback 07–22. Scheduled sẽ fail rõ nếu config chưa có.
- Reservation→Order chưa có contract tích hợp được chứng minh trong lượt này; cần phối hợp Toàn, không invent link/seat logic.
- Session intent hiện hành là phạm vi replay; tab cũ sau cart mutation bị từ chối. Không hứa khôi phục intent khi session mất hoặc mở cùng lúc nhiều checkout độc lập.
- POS code dùng prefix POS- và hash intent thay mã random ORD- cho command mới; engineer cần review quy ước hiển thị.
- Read settlement tạm cần Finance cùng tuân thủ khóa Order; chưa chứng minh tranh chấp Finance/Sales ngoài phạm vi writer.

## Assumptions
Mục 7 hiệu lực, shared session backend/DB transaction Laravel. Không thêm shipper/gateway/slot/holds/refund. Tiền VND decimal 2 theo baseline.

## Out-of-Scope Findings
None mới ngoài các dependency Finance/Toàn và coverage trên.

## Engineer Review Required / Next actions
Review diff incremental của lượt 03, cung cấp Finance writer và giờ quán; bàn giao dedicated MySQL để bổ sung/chạy concurrent submit/accept và 8 skipped; dựng môi trường local an toàn rồi browser 375/768/1440. Chưa commit/push/merge. Không gọi SUCCESS cho toàn bộ N01–N06 khi các mục này còn thiếu.
