# Sales final QA và demo readiness — 17/11/2026

## Task Status
PARTIAL. Backend có bằng chứng kiểm tra; browser QA và cấu hình giờ quán thật chưa đạt điều kiện nghiệm thu.

## Task ID
SALES-FINAL-QA-DEMO-2026-11-17

## Summary
Giữ nhánh `feature/order-requests-scheduled-pickup`, HEAD `fb7e77fd1feee1592f7aa5a1ae20b0ec127696ed`. Source vẫn ở F:\HocTap\Nam4\MNM\DeTaiMonHoc\coffee-shop-management-system. Worktree có nhiều thay đổi từ trước, được giữ nguyên; danh sách dưới chỉ là phần bổ sung trong lượt final QA.

## Files Created
- docs/implementation/SALES_DEMO_READINESS.md
- docs/implementation/DATABASE_UPGRADE_CHECKLIST.md

## Files Modified
- resources/views/admin/product-recipes/index.blade.php: thêm link lịch sử kho đúng trang thực sự được route render; không thay đổi Recipe/Inventory workflow.
- tests/Feature/OnlinePickupSchemaMySqlTest.php: assertion liên kết công thức theo URL route thật thay nhãn cũ “Xem công thức”; giữ kiểm tra lịch sử kho.
- resources/views/staff/orders/show.blade.php: mã nhận đủ chiều rộng, nút giao dùng component hiện có và disabled khi chưa thu đủ; backend guard giữ nguyên.
- resources/css/staff/kitchen.css: grid co được dưới 320px và input mã nhận dùng design tokens.
- SALES_IMPLEMENTATION_PROGRESS.md, SALES_UI_QA_REPORT.md, SALES_CONCURRENCY_REPORT.md, PAYMENT_MYSQL_VERIFICATION.md trong thư mục này: bổ sung evidence, không xóa lịch sử QA.

## Files Deleted
None.

## Implementation Details
Link Recipe dùng `admin.ingredients.history`, chỉ hiện khi nguyên liệu chưa soft delete; endpoint vẫn bảo vệ Admin. Không tạo route/trang/CRUD trùng. Không sửa OrderService, PaymentService hoặc InventoryService trong lượt này.

Demo dùng MySQL 8.4.3 riêng port 55659, database mới `coffee_sales_demo_b7a7a76791d66d3e`, import bộ SQL hiện có: 4 sản phẩm, 12 dòng công thức, 9 Order. Các chứng từ mẫu là fixture, không phải chứng cứ thu tiền thực tế. Giờ 08:00–20:00 chỉ là fixture demo. App nhận override qua environment của tiến trình, không sửa `.env` project, không tạo account/password cứng trong production seeder. Không xuất credential trong report.

PHP demo port 62494 và MySQL QA riêng đã dừng sau kiểm tra PID/datadir. Datadir TEMP được giữ; không cung cấp link này như server đang chạy. Evidence: `storage/logs/sales-demo-environment.json`. C: chứa helper/datadir tạm; source chính vẫn ở F:.

## Tests / Checks Executed
- Default regression: `php artisan test --compact`, 467 tổng = 451 PASS, 16 SKIP, 6126 assertions; `storage/logs/sales-final-qa-regression.json`.
- MySQL targeted `OnlinePickupSchemaMySqlTest::test_workflow_screens_use_real_data_and_existing_preparation_command`: PASS, 46 assertions; `storage/logs/recipe-final-qa.json`.
- `npm run build`: PASS. Warnings optional fontaine, hai ảnh chỉ resolve runtime; chưa xác nhận ảnh bằng browser.
- Rà source/diff. Không chạy lại concurrency nặng vì các writer/locking paths không đổi. Bằng chứng cũ SalesFinanceMySqlTest 8/8 PASS, 204 assertions vẫn được giữ; không gọi đây là lần chạy mới.

## Test Results
Service/HTTP/MySQL đã có evidence counter/online/scheduled từ QA trước. Test Recipe lỗi trước đã được chạy riêng và đạt sau sửa. Không cộng lần chạy riêng để tuyên bố đã chạy lại toàn bộ bộ MySQL 16/16.

## Acceptance Criteria
| Điều kiện | Kết quả |
| --- | --- |
| Link lịch sử kho trên Recipe thật, giữ quyền endpoint | PASS qua targeted MySQL HTTP test |
| Regression và build | PASS; 16 test opt-in được SKIP trong default run |
| UI 375/768/1440, keyboard, ba flow thao tác thật | UNVERIFIED / NOT_RUN |
| Demo tách biệt, không đổi DB dự án/.env | PASS theo metadata và command scope |
| Giờ mở quán chính thức | UNVERIFIED / CONFIG BLOCKED |
| Nâng schema receipt trên DB dự án | NOT_RUN, chỉ checklist review |

## Risks
Responsive, focus, loading, ảnh runtime và hành vi thao tác người dùng chưa có screenshot/browser evidence. Fixture đủ khởi động demo không chứng minh mọi tình huống nghiệp vụ đã nghiệm thu.

## Blockers
Browser host unavailable; native Brave fallback bị Computer Use tự dừng vì không xác định được URL hiện tại đủ chắc chắn để thực thi policy. Không tiếp tục thao tác browser sau khi công cụ dừng. Giờ quán thật cần Toàn cung cấp/xác nhận.

## Assumptions
Recipe link tối thiểu thuộc phạm vi explicit của task hiện tại và owner Hợp; không mở rộng sửa nghiệp vụ Recipe.

## Out-of-Scope Findings
Các warning build nêu trên; không cài dependency hoặc thay assets ngoài task.

## Unverified Items
Browser matrix và upgrade trên bản sao DB vận hành thực tế; fresh demo migration không thay thế upgrade snapshot thật.

## Engineer Review Required
Review diff, xác nhận giờ quán, chạy checklist UI và checklist upgrade. Chưa commit/push/merge hoặc migrate DB dự án.

## Recommended Next Task
Khôi phục browser connection hoặc manual QA có evidence theo SALES_UI_QA_REPORT.md. Sau đó test restore/upgrade bản sao DB theo DATABASE_UPGRADE_CHECKLIST.md; không triển khai tự động.
