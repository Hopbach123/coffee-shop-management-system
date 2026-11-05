# Bạch Ngọc Hợp — Theo dõi report và tiến độ use case

Cập nhật 06/11/2026. Đối chiếu bảng phân công mục 7 (phạm vi rút gọn). Đây là chỉ mục report, không thay báo cáo nghiệm thu từng nhánh. Có code hoặc test đạt không đồng nghĩa đã được engineer nghiệm thu/merge.

## Công việc đã có report

| Use case / công việc | Report | Tình trạng cần lưu ý |
| --- | --- | --- |
| H01–H02: danh mục, sản phẩm, ảnh, size/giá, topping, menu khách | [Catalog](feature-product-catalog__report.md), [sửa CI Catalog](fix-product-catalog-ci__report.md) | Đã triển khai; report Catalog ban đầu chỉ rà tĩnh, report CI sau đó ghi kiểm thử thực tế. Responsive/keyboard chưa đủ bằng chứng. |
| H03: quản lý nguyên liệu, đơn vị và validation | [Ingredient management](feature-ingredient-management__report.md) | Đã có chức năng baseline; cảnh báo tồn thấp là phần còn lại. |
| H04: quản lý công thức theo biến thể | [Product recipes](feature-product-recipes__report.md) | Có CRUD/validation/tests; report có đính chính bàn giao commit c9468123 và PR #32. Còn nghiệm thu UI và rà rule công thức rỗng trong luồng pha. |
| H05: nhập kho và lịch sử | [Inventory transactions](feature-inventory-transactions__report.md) | Đã triển khai/test; sau thời điểm report đã vào dev qua PR #33, baseline e6e926e7 dùng cho adjustments. Các dòng “merge chưa commit” trong report nhập kho là lịch sử tại thời điểm viết. |
| H05: tăng/giảm, hao hụt và kiểm kê | [Inventory adjustments](feature-inventory-adjustments__report.md) | Đã triển khai; full suite 408 passed/7 skipped, MySQL riêng 2 tests/50 assertions, build đạt. Chưa nghiệm thu responsive/keyboard; thay đổi hiện chưa commit tại lần kiểm tra này. |
| H06: kiểm tồn/consume khi preparing | [Inventory transactions](feature-inventory-transactions__report.md) | Đã có consume từ baseline và được giữ khi tích hợp; chưa coi report nhập kho là nghiệm thu đầy đủ H06. Cần rà/test tập trung luồng tiêu hao. |

Các công việc hỗ trợ trước đây cũng có report: [Auth login](feature-auth-login__report.md), [Role authorization](feature-role-authorization__report.md), [SQL seed](seed-data-sql__report.md), [Schema online pickup](schema-counter-scheduled-online__report.md), [SQL online pickup v2](seed-online-pickup-v2__report.md), [Rà soát/tổ chức UI](ui-workflow-audit-implementation__report.md). Ownership hiện hành của Auth/User thuộc Toàn theo phân công mới; không cộng chúng thành nhiệm vụ H01–H06 mới.

## Công việc tiếp theo đã thống nhất, chưa triển khai

Gộp hai nhóm còn lại vào nhánh dự kiến **feature/inventory-completion**:

1. H03: hiển thị/lọc nguyên liệu dưới ngưỡng và hết tồn, empty state phù hợp.
2. H04/H06: rà rule thiếu công thức; kiểm tồn khi pha, chống tiêu hao lặp/âm, rollback và tranh tồn.
3. Nghiệm thu tích hợp nhập → pha → tiêu hao → hao hụt/kiểm kê → lịch sử; phối hợp Ninh cho điểm giao Sales.

Đây mới là thống nhất phạm vi trong hội thoại, chưa tạo nhánh, chưa sửa source cho phần này và chưa có kết quả test. Trước triển khai phải đối chiếu requirements/code hiện có, chốt rule công thức rỗng, ghi task/AC và report riêng; không mặc định luôn chặn mọi món không có recipe khi chưa chốt chính sách. Không triển khai reserve/release, shipper hay recipe version engine theo mục 7.

## Quy ước cập nhật report theo yêu cầu user

Mọi công việc triển khai sau này phải có report trong `reports/task-reports/`, cập nhật ngay cùng thay đổi của nhánh. Report cần có: baseline/nhánh, phạm vi, file tạo/sửa/xóa, logic, test/build đã thực sự chạy, AC, giới hạn/rủi ro và phần chưa xác minh. Phân biệt kết quả lịch sử với kết quả mới, kế hoạch với implementation; không đánh dấu Done nếu còn AC bắt buộc chưa xác minh. Cập nhật chỉ mục này khi có nhánh/report mới. Không tự commit/push chỉ vì đã viết report.
