# Task Report

## Task Status

SUCCESS — phạm vi rà soát và chỉnh tài liệu phân công; không phải nghiệm thu Customer/POS runtime.

## Task ID

CAFE-TEAM-DOC-001 (mã ghi nhận cho yêu cầu rà soát tài liệu trực tiếp trong chat ngày 27/10/2026).

## Summary

Đã đọc bản phân công mới trong Đồ án, đối chiếu requirements và quyết định user đã duyệt. Giữ owner mới Hợp/Ninh/Đạt/Toàn; sửa phần phạm vi hiệu lực đang bỏ delivery/COD và cho POS pha trước thu tiền. Phân biệt rõ roadmap lịch sử, yêu cầu hiện hành và schema/business decisions còn chờ.

## Files Created

- reports/task-reports/CAFE-TEAM-DOC-001__report.md.

## Files Modified

- D:/nam 4 HK1/MNM/Đồ án/coffee-shop-team-work-assignment.md.

Không sửa bản cùng tên trong Downloads. Không sửa requirements.md, contracts, source hoặc work Payment/Cart có sẵn.

## Files Deleted

None.

## Implementation Details

- Cập nhật ngày rà soát và hướng dẫn đọc: mục 7 hiệu lực; mục 2–3 là roadmap, mục 5–6 là thiết kế lịch sử.
- Giữ phân công mới: Hợp Catalog/Inventory; Ninh Cart/POS/Order/Customer requests; Đạt Promotion/Payment/COD/báo cáo tiền; Toàn Identity/Customer/Table/UI chung.
- POS: xác nhận đủ tiền trước khi atomic tạo Order/Item/topping/paid/preparing, không auto-complete/trả bàn sớm.
- Web chuyển khoản: guest gửi request, Staff accept tạo một Order thật, xác minh tiền mới paid/chuẩn bị. COD được chuẩn bị/giao trước paid và chỉ ghi paid khi xác nhận thu đủ lúc khách nhận.
- Giữ phương án request-before-Order và orders.user_id là nhân viên thật; không chuyển sang auto-create Order từ guest chỉ vì ví dụ thương hiệu.
- Ghi riêng Order, Item, Payment và request/delivery status còn review; không tự thêm COD vào payment_method hoặc coi delivery là takeaway.
- Đánh dấu gateway/refund/hold kho-mã/slot-scheduler/điều phối shipper là ngoài scope hoặc chờ duyệt, không tự phát sinh interface/Service/migration.
- Sửa nhánh tích hợp thành dev, ghi cần re-estimate điểm roadmap; chỉ ghi kế hoạch bàn giao code đã có, không xóa/viết lại theo owner mới.
- Nêu đường dẫn KE_HOACH_SCHEMA_ONLINE_PICKUP_DELIVERY.md không tồn tại trong checkout được quan sát; không dùng làm chứng nhận schema/code.
- Bổ sung trình tự nghiệm thu cart, review schema, submit/accept/tracking, Payment operations, completion và regression.

## Tests / Checks Executed

- Đọc toàn bộ bản phân công 216 dòng trước sửa, so với bản Downloads bằng SHA256: hai bản ban đầu giống nhau.
- Đối chiếu AGENTS.md, requirements.md, architecture.md, phần roadmap/gate liên quan trong plan.md, CUSTOMER_ORDER_CONTRACT.md và PAYMENT_CHECKOUT_CONTRACT.md; source/status đã quan sát trong các task trước được ghi rõ là snapshot.
- Kiểm tra bản sau sửa: ba luồng POS/web chuyển khoản/web COD, bốn owner, creator thật, không completed sớm, schema gate và nhánh dev.
- Kiểm tra cấu trúc bảng Markdown: không có bảng lệch số cột.
- Git status trước/sau và git diff --check trong repository.
- Không chạy PHP tests hoặc build: chỉ thay tài liệu phân công ngoài repo và report, không thay application code.

## Test Results

- PASS: kiểm tra nội dung, ownership, ba luồng và Markdown tables.
- PASS: git diff --check; tập file source dirty/untracked có sẵn giữ nguyên, chỉ thêm report này trong repo.
- PASS: hash bản Downloads vẫn 07242B6E2C59A3610642EFA060645305F7C65D3172D7CC9CC4FF531B3A09E335.
- Hash bản Đồ án sau sửa: 2F99D5F14C7B030C4542AB89BC814F8DC4B185B8DA92F28E582B1F683B175235.
- Application tests/build: không áp dụng cho lần sửa tài liệu này; không tuyên bố suite runtime PASS.

## Acceptance Criteria

- PASS — đọc đúng bản trong Đồ án và phân biệt bản Downloads đang mở.
- PASS — giữ owner theo phân công mới, không trả về Word cũ.
- PASS — luồng hiệu lực khớp requirements về guest/delivery/chuyển khoản/COD/POS trả trước.
- PASS — không biến đề xuất schema/gateway/refund/hold thành rule đã duyệt.
- PASS — không sửa source, requirements, local DB hoặc công việc đang có; không commit/push/merge.
- PASS — có file tài liệu cập nhật để engineer review và phần next task chưa triển khai.

## Risks

- Mục 2–6 giữ lịch sử/roadmap để không mất bối cảnh; người triển khai phải đọc mục 7 và thẩm quyền tài liệu trước khi chọn task.
- Tài liệu module còn nhắc owner từ Word cũ; cần task bàn giao/cập nhật metadata riêng. Lần sửa này không chỉnh contracts đang dirty.
- Bản Downloads không tự đồng bộ; mở đúng bản trong Đồ án để xem thay đổi.

## Blockers

None cho rà soát tài liệu. Mapping delivery, schema chi tiết, completion/trả bàn, phí/vùng giao và các gate khác vẫn chặn implementation phần phụ thuộc như đã ghi trong mục 7.

## Assumptions

Hiểu yêu cầu chỉnh bản phân công cho đúng file yêu cầu là đồng bộ với requirements.md và các quyết định đã duyệt; không đổi requirements để loại COD/giao hàng mà chưa có chỉ dẫn hủy phạm vi đó.

## Out-of-Scope Findings

Contracts cũ còn owner Đạt cho Order/POS; Payment hiện còn operation served-before-paid; cart không có guest submit/delivery workflow. Không sửa runtime hoặc metadata đó trong task này.

## Unverified Items

None đối với kiểm tra tài liệu. Các nhánh/file/schema đề xuất trong roadmap không được kiểm chứng tồn tại ngoài checkout hiện tại; không tuyên bố tính năng đã triển khai.

## Engineer Review Required

Review mục 7, xác nhận bàn giao ownership và re-estimate từng gói. Bản mới chỉ chuẩn hóa phân công/phạm vi, không duyệt thay engineer các quyết định TBD.

## Recommended Next Task

Bàn giao Cart/Order/POS cho Ninh và rà metadata owner trong contracts; chốt schema/fulfillment trước task gửi yêu cầu khách. Không tự triển khai.
