# Bạch Ngọc Hợp — Theo dõi report và tiến độ use case

Cập nhật 06/11/2026. Đối chiếu bảng phân công mục 7 (phạm vi rút gọn). Đây là chỉ mục report, không thay báo cáo nghiệm thu từng nhánh. Có code hoặc test đạt không đồng nghĩa đã được engineer nghiệm thu/merge.

## Công việc đã có report

| Use case / công việc | Report | Tình trạng cần lưu ý |
| --- | --- | --- |
| H01–H02: danh mục, sản phẩm, ảnh, size/giá, topping, menu khách | [Catalog](feature-product-catalog__report.md), [sửa CI Catalog](fix-product-catalog-ci__report.md) | Đã triển khai; report Catalog ban đầu chỉ rà tĩnh, report CI sau đó ghi kiểm thử thực tế. Responsive/keyboard chưa đủ bằng chứng. |
| H03: quản lý nguyên liệu, đơn vị, validation và cảnh báo tồn | [Ingredient management](feature-ingredient-management__report.md), [Inventory completion](feature-inventory-completion__report.md) | Đã có baseline và cảnh báo/lọc hết/dưới/đủ ngưỡng; còn nghiệm thu UI. |
| H04: quản lý công thức theo biến thể | [Product recipes](feature-product-recipes__report.md), [Inventory completion](feature-inventory-completion__report.md) | Có CRUD/validation/tests; đã chốt và kiểm thử chặn pha khi công thức rỗng. Còn nghiệm thu UI. |
| H05: nhập kho và lịch sử | [Inventory transactions](feature-inventory-transactions__report.md) | Đã triển khai/test; sau thời điểm report đã vào dev qua PR #33, baseline e6e926e7 dùng cho adjustments. Các dòng “merge chưa commit” trong report nhập kho là lịch sử tại thời điểm viết. |
| H05: tăng/giảm, hao hụt và kiểm kê | [Inventory adjustments](feature-inventory-adjustments__report.md) | Đã triển khai và vào baseline qua PR #34 / feac9ce2. Report cũ ghi chưa commit là lịch sử. Còn nghiệm thu responsive/keyboard. |
| H06: kiểm tồn/consume khi preparing | [Inventory completion](feature-inventory-completion__report.md) | Đã harden quyền/parent/recipe/rollback/chống lặp và test MySQL tranh tồn. Còn nghiệm thu UI/tích hợp Sales với Ninh. |

Các công việc hỗ trợ trước đây cũng có report: [Auth login](feature-auth-login__report.md), [Role authorization](feature-role-authorization__report.md), [SQL seed](seed-data-sql__report.md), [Schema online pickup](schema-counter-scheduled-online__report.md), [SQL online pickup v2](seed-online-pickup-v2__report.md), [Rà soát/tổ chức UI](ui-workflow-audit-implementation__report.md). Ownership hiện hành của Auth/User thuộc Toàn theo phân công mới; không cộng chúng thành nhiệm vụ H01–H06 mới.

## Nhánh đang hoàn thiện và phần còn lại

`feature/inventory-completion`, baseline feac9ce2: đã triển khai cảnh báo tồn H03 và harden tiêu hao H04/H06. User giao chọn phương án thiếu recipe, đã chốt chặn pha và ghi requirement. Xem [report](feature-inventory-completion__report.md) và [kế hoạch/hướng dẫn](../../docs/KE_HOACH_VA_HUONG_DAN_INVENTORY_COMPLETION.md).

Kết quả: 415 passed/8 skipped/5895 assertions; MySQL riêng 3 tests/84 assertions; build đạt. Chưa commit/push tại thời điểm report. Còn nghiệm thu responsive/keyboard và phối hợp Ninh luồng Sales pha → ready/served trên UI. Test chuỗi kho đã đạt không thay thế nghiệm thu toàn bộ hệ thống. Không triển khai reserve/release, shipper hoặc recipe version engine theo mục 7.

## Quy ước cập nhật report theo yêu cầu user

Mọi công việc triển khai sau này phải có report trong `reports/task-reports/`, cập nhật ngay cùng thay đổi của nhánh. Report cần có: baseline/nhánh, phạm vi, file tạo/sửa/xóa, logic, test/build đã thực sự chạy, AC, giới hạn/rủi ro và phần chưa xác minh. Phân biệt kết quả lịch sử với kết quả mới, kế hoạch với implementation; không đánh dấu Done nếu còn AC bắt buộc chưa xác minh. Cập nhật chỉ mục này khi có nhánh/report mới. Không tự commit/push chỉ vì đã viết report.
