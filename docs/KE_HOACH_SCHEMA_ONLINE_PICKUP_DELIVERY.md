# Kế hoạch mua tại quầy, đặt hẹn và mua online

Cập nhật 26/10/2026, nhánh feature/schema-template-sync. Nội dung này thay toàn bộ đề xuất delivery phức tạp trước đây. Giữ tên file để không hỏng link, nhưng không còn nghiệp vụ giao tận nhà.

## Phạm vi chốt

| Cách mua | Người nhập | Nhận đồ | Thời gian |
| --- | --- | --- | --- |
| Tại quầy | Nhân viên qua POS | Uống tại bàn hoặc mang đi | Xử lý ngay |
| Đặt hẹn | Khách web hoặc Staff nhập hộ | Khách đến quán lấy | Giờ khách chọn, quán xác nhận |
| Online | Khách qua web | Khách đến quán lấy | Lấy sớm sau khi quán xác nhận, không hẹn giờ cụ thể |

Không shipper, địa chỉ/phí giao, COD, vùng giao, gateway/webhook, slot capacity, scheduler, engine giữ kho/mã, tự động refund hay ERP. Không bắt nhóm chốt các chính sách này để bắt đầu. Online ở đây nghĩa là đặt trên web rồi tự đến lấy.

Thanh toán dùng cash/bank_transfer hiện có. Trả trước là chuyển khoản sau khi quán nhận đơn và Staff xác nhận thực nhận. Trả trực tiếp là tiền mặt hoặc chuyển khoản tại quầy trước nhận đồ. Nút bấm/ảnh chuyển khoản của khách không tự đổi Payment sang paid.

## Luồng và trạng thái

- Tại quầy: POS chọn món/bàn hoặc mang đi → Order → pha → phục vụ/giao → thu đủ → completed.
- Đặt hẹn: chọn món, tên/SĐT, giờ nhận → pending → Staff accept/reject → tạo một Order → nhân viên cho pha theo giờ hẹn → ready → xác minh mã, thu phần chưa trả → nhận đồ → completed.
- Online: giống đặt hẹn nhưng lấy sớm, không yêu cầu giờ hẹn. Quán xác nhận khả năng thực hiện, không tự hứa thời gian chính xác.

Loại mua không phải trạng thái. Giữ Order status pending/completed/cancelled; món pending/preparing/ready/served/cancelled; Payment pending/paid/failed. Request web dùng pending/accepted/rejected/cancelled. ready chưa completed; paid chưa completed nếu chưa giao/phục vụ. refunded trong enum cũ không có nghĩa đã triển khai refund mới.

Đặt hẹn phải có giờ tương lai trong giờ phục vụ; Staff duyệt thủ công theo khả năng quán. Không scheduler/slot mới. Unpaid trước pha có thể hủy theo quyền. Đã trả tiền hoặc đã pha thì liên hệ quán xử lý thủ công, không tự hoàn tiền/tồn hoặc xóa chứng từ. Không đổi món/tổng sau paid trong MVP. Không nhận tiền yêu cầu đã rejected/cancelled.

## Schema tối thiểu đề xuất

| Thay đổi | Trường và ràng buộc chính |
| --- | --- |
| online_order_requests | id, request_code UNIQUE, purchase_type online/scheduled, customer_id nullable, contact_name/phone, pickup_at nullable, note, status, payment_preference cash/bank_transfer, accepted_by/at nullable, rejection_reason nullable, submission_key UNIQUE, tracking_token_hash UNIQUE, timestamps |
| online_order_request_items | request_id FK, variant_id FK, quantity dương, note, product_name/variant_name/unit_price snapshots, timestamps |
| online_order_request_item_toppings | request_item_id FK, topping_id FK, quantity dương, topping_name/price snapshots, UNIQUE(request_item_id,topping_id), timestamps |
| orders | purchase_type mặc định counter (counter/scheduled/online), online_request_id nullable UNIQUE FK |

Giữ order_type dine_in/takeaway cho cách phục vụ; purchase_type mô tả cách mua. counter dùng cả hai order_type, scheduled/online chỉ takeaway. orders.user_id vẫn là Staff/Admin accept tạo đơn, không sửa nullable hoặc giả tài khoản khách. Giờ hẹn/liên hệ đọc qua request, không sao chép cột cùng nghĩa sang Order. Staff nhập hộ đơn hẹn dùng cùng request workflow.

Một request liên kết tối đa một Order qua orders.online_request_id; không tạo FK ngược gây vòng. Dùng kiểu ID/FK RESTRICT theo baseline, tiền DECIMAL hiện có, CHECK lượng dương/giá không âm. Không thêm delivery_fee/currency hoặc đổi CHECK tổng tiền vì chỉ VND và không giao hàng.

Giá server tính, kiểm variant/topping active khi gửi và accept. Nếu giá/món đổi phải khách đồng ý hoặc từ chối gửi lại, không âm thầm thu thêm. Topping quantity giữ nghĩa tổng topping của dòng như logic hiện tại, form/seed phải hiển thị rõ. Token khó đoán lưu hash, không cho tra cứu chỉ bằng ID hoặc SĐT công khai.

## Logic cần hoàn thiện

- Đồng bộ quyền Admin thao tác Staff; sửa giới hạn cents theo DECIMAL(15,2): tối đa 999999999999999 cents. Tính tổng loại món cancelled và xử lý khuyến mãi sau đổi món trước paid.
- Review/merge công thức và Payment từ nhánh owner trước khi viết trùng. Không thêm engine recipe version/stock_mode/holds trong task online.
- Accept khóa request, tạo Order/items/toppings nguyên tử, UNIQUE request chống nhận hai lần. Retry lỗi không tạo Order nửa chừng.
- Pha dùng InventoryService hiện có: kiểm tồn tại preparing; thiếu thì thông báo nhân viên xử lý. Không giữ tồn khi submit/accept; accept chưa bảo đảm tồn đến giờ hẹn. Món quản lý kho thiếu công thức cần chặn theo chính sách baseline.
- Payment cash/bank khi thực nhận, khóa Order chống paid lặp, một paid/Order; bank trả trước chỉ sau accept. Không gateway/COD/refund tự động.
- Public menu/giỏ/checkout/tra cứu; Staff inbox yêu cầu, loại mua, giờ hẹn, accept/reject và tiến độ tiền/món. Dùng template hiện hữu, không đổi theme; không có màn giao vận.

## Thứ tự migration và triển khai

1. Ghi extension đơn giản vào requirements/database-design/plan trước implementation. Đây là kế hoạch, không tự sửa Frozen schema bằng tài liệu này.
2. Tạo ba bảng request/items/toppings sau catalog/customer/users. Tạo UNIQUE/FK/CHECK/index theo query thực tế; không sửa migrations cũ đã chia sẻ.
3. Thêm hai cột orders, backfill đơn baseline counter và online_request_id=NULL; nếu có dữ liệu online thử nghiệm phải phân loại trước. Không sửa ID/tiền/trạng thái cũ.
4. FormRequest/Service submit/accept/reject, Payment cơ bản và template. Controller mỏng; route/Blade không chứa business workflow.
5. Seed/verifier v2; test DB sạch và nâng cấp bản sao có dữ liệu. Không migrate:fresh coffee_shop. Backup trước nâng cấp, rollback không tự drop lịch sử khi đã có đơn mới; ưu tiên forward fix.

Chia PR/task 30–90 phút có criteria/test, không làm tất cả trong một lần. Nhánh tích hợp dev.

## Phân công

| Người | Việc trong phiên bản đơn giản |
| --- | --- |
| Hợp | Catalog/menu/công thức/kho hiện có, kiểm món bán được và seed/test tiêu hao; chưa làm reserve/release |
| Ninh | Giỏ/POS/Order, request online/hẹn, accept/reject, tiến độ pha/nhận tại quầy và tra cứu |
| Đạt | Promotion/Payment cash/bank, xác nhận trả trước/tại quán, receipt và báo cáo cơ bản; không gateway/COD/refund tự động |
| Toàn | Auth/quyền/customer/bàn/reservation và UI/components chung; đặt bàn khác hẹn lấy đồ |

Giữ owner bản phân công mới. Nhánh đề xuất: feature/online-order-requests và feature/scheduled-pickup của Ninh; feature/payment-confirmation của Đạt; các owner dùng nhánh module phù hợp. Không tạo hàng loạt nhánh trước khi chọn task.

## Seed và verifier

Không đổi SQL trong task tài liệu. Bộ v2 sau schema có mẫu cả ba cách mua, pickup hẹn/online lấy sớm, accepted/rejected/cancelled, unpaid/paid, cash/bank; multi-item/topping và nhiều kỳ báo cáo. Không seed shipper/gateway/refund hoặc bảng chưa được duyệt.

- DB trống/fixture thiếu phải FAIL; không PASS chỉ vì COUNT lỗi bằng 0. Dùng manifest file/codes và mapping fixture ổn định thay tên DEMO có thể sửa; không thêm bảng registry chỉ để seed.
- Bộ thiếu báo lỗi, không SKIPPED chỉ vì một email/marker tồn tại. Rerun không reset mật khẩu local hoặc sửa chứng từ cũ.
- Tách snapshot check ngay sau import với runtime check. Không so tiêu hao lịch sử bắt buộc với công thức đã sửa. Ledger runtime kiểm toàn chuỗi, không chỉ note DEMO.
- Kiểm request–Order một-một, scheduled có giờ/online không cần giờ, đúng takeaway, tiền và trạng thái completed. Thời gian lưu UTC/hiển thị Asia/Ho_Chi_Minh; không diễn giải lại DATETIME cũ nếu chưa xác minh. Seed menu Admin theo registry đã có.

Tests: quyền, dữ liệu sai/giá client giả, giờ hẹn, accept lặp/đồng thời, thiếu tồn, Payment lặp, giao trước khi thu đủ, token cách ly khách, fresh/upgrade giữ lịch sử, seed đủ/thiếu/rerun, UI 375/768/1440 và keyboard. Ca sai thuộc tests từ chối/rollback, không import dữ liệu vi phạm.

## Trạng thái bàn giao

SUCCESS cho rút gọn kế hoạch; chưa implementation. Files modified: file này và bảng phân công mục 7; Files created/deleted: None. Checks: đọc lại/diff/whitespace; test/build không áp dụng vì không sửa source. Risks: cần cập nhật tài liệu thẩm quyền trước code, accept chưa giữ tồn là hạn chế MVP. Blockers: None cho kế hoạch. Assumptions: online lấy tại quán, bank xác nhận thủ công. Out-of-scope: migration/code/DB runtime. Unverified: chức năng mới chưa code/test. Engineer review: duyệt luồng và chia task. Recommended next task: requirement/schema tối thiểu rồi request online; không tự triển khai.
