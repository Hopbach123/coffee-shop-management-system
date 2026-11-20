# Customer Order — contract để engineer review

## Quyết định hiện hành — CAFE-CUSTOMER-POS-COMPLETE-001, 27/10/2026

Mục này ghi các câu trả lời trực tiếp mới nhất, ưu tiên hơn proposal/lịch sử bên dưới. Phân công hiệu lực: Ninh sở hữu Cart/POS/Order/request/giao/tracking; Đạt Payment/Promotion/xác nhận chuyển khoản/thu COD; Hợp Inventory; Toàn Identity/Customer/Table/UI chung, theo mục 7 của `D:\nam 4 HK1\MNM\Đồ án\coffee-shop-team-work-assignment.md`.

- A/guest vẫn giữ: request trước Order, actor thật tiếp nhận, không Customer account hoặc link master bằng phone tự khai.
- User chọn ánh xạ giao web vào **takeaway**; request relation giữ fulfillment/address. Không thêm enum delivery.
- Đã duyệt ba bảng request/items/toppings với contact/address/payment intent, snapshot, idempotency hashes và unique Order link. Migration mới, không sửa lịch sử.
- Phí giao cố định 20000.00 VND/request, snapshot riêng trên request và Order; total = subtotal − discount + tax + delivery_fee. POS fee 0. Đã duyệt thay CHECK tương ứng.
- Chốt giá/phí lúc gửi; catalog/cấu hình/giá đổi trước accept thì từ chối tiếp nhận và yêu cầu khách kiểm tra/gửi lại. Đã duyệt khóa sửa item/topping trên Order web kể cả COD chưa thu.
- Tracking capability gắn server session, không phone/ID/code làm quyền; 7 ngày, không token thô trong URL/log. Không tự gia hạn khi GET.
- COD được preparing trước paid; bank_transfer_prepaid phải xác minh thực nhận trước preparing. Tất cả consumption qua InventoryService.
- Mọi món ready mới bắt đầu giao; Staff/Admin xác nhận khách thực nhận → ready sang served; COD đồng thời xác nhận thu đủ để ghi paid. Lưu dispatched_at/delivered_at, không shipper role.
- Completed khi có paid, ít nhất một served và mọi món chưa hủy served; request giao không trả bàn vì mapped takeaway.

Chưa duyệt tự động xóa/redact contact, rate limit nghiệp vụ, hủy/sửa public, khôi phục phiên, refund hoặc xử lý giao thất bại. Không triển khai các phần đó bằng assumption. Các gate D/P bên dưới phải được đọc cùng các quyết định đã được cập nhật tại mục này.

- Task cha: `CAFE-CUSTOMER-ORDER-001`.
- Change request hiện tại: `CAFE-CUSTOMER-POS-WORKFLOW-002`, giai đoạn DOC, cập nhật ngày 27/10/2026.
- Giai đoạn hiện tại: `DOC-001`, chỉ tài liệu.
- Ngày rà soát: 26/10/2026.
- Trạng thái: **DUYỆT MỘT PHẦN — A VÀ GUEST ĐÃ DUYỆT; CÒN CHỜ RULE / CHƯA TRIỂN KHAI BACKEND**.
- Branch quan sát: `feature/payment-management`; không checkout hoặc tạo branch trong giai đoạn tài liệu.
- Owner Order/POS/Payment: Đạt, theo `D:\nam 4 HK1\MNM\Đồ án\BangPhanCongMNM.docx`.

## 1. Phạm vi đã được yêu cầu và thẩm quyền

User đã yêu cầu mở rộng: khách hàng đặt món trên website công khai, chọn biến thể/số lượng/topping, dùng giỏ hàng, gửi đơn, xem xác nhận/trạng thái của mình; kết nối với Staff để tiếp nhận, pha chế, trừ kho và thanh toán. Đây là **Customer**, không phải tài khoản nội bộ `User`.

Yêu cầu trên duyệt mục tiêu mở rộng và công việc lập contract. Ngày 26/10/2026, user trả lời “Chọn A — yêu cầu chờ Staff tiếp nhận (đề xuất)” cho câu hỏi mô tả request trước Order, thêm 3 bảng request/items/toppings và giữ orders.user_id bắt buộc. Ngày 27/10/2026, user xác nhận khách web không cần tạo tài khoản, chỉ để lại thông tin khi đặt, và giao task WORKFLOW-002 cập nhật contract. Đã duyệt A và guest; không tự suy ra mọi cột, loại đơn web, giá, quyền hủy, tracking, giới hạn hoặc retention đã được duyệt. Mọi nội dung còn mang nhãn **ĐỀ XUẤT** phải được engineer chấp nhận trước task tương ứng; các câu hỏi cũ chỉ được ghi nhận theo phần đã có câu trả lời.

Baseline trước extension giới hạn Customer ở trang công khai/menu và Reservation khi được triển khai. Bảng phân công giao Đạt POS/Order/Payment; Public Menu của Ninh là read-only. Task WORKFLOW-002 cập nhật requirements/plan/design với phần mở rộng đã duyệt và gate còn thiếu, không coi Customer Order có sẵn trong baseline cũ hoặc đã được triển khai runtime.

`AGENTS.md` §2/§23, `requirements.md` §13 và `plan.md` §9/§11 yêu cầu giải quyết quyết định còn thiếu trước implementation. Tài liệu này không ghi đè Frozen Design, không cập nhật yêu cầu thành đã duyệt và không tạo migration.

## 2. Readiness gate — evidence từ source

| Kiểm tra | Kết quả quan sát | Hệ quả |
| --- | --- | --- |
| Git | Branch `feature/payment-management`; 4 tracked files modified và 6 untracked files Payment trước task | Chỉ tạo file này; giữ nguyên toàn bộ work có sẵn; branch implementation cần được chuẩn bị riêng sau review |
| Public routes | `routes/web.php`: `/`, `/menu`, search và product detail; không có cart/submission/tracking Order | Chưa có Customer Order workflow |
| Creator | Migration `2026_08_16_000016_create_orders_table.php`: `user_id` NOT NULL, FK `users.id`, RESTRICT | Không thể dùng guest làm actor hiện tại hoặc truyền creator null mà không thay thiết kế |
| Customer identity | Migration customers không có password; `app/Models/Customer.php` là Eloquent Model; users chỉ có admin/staff | Customer không phải tài khoản xác thực; nhập phone không chứng minh danh tính |
| Order workflow | `OrderService::create/createWithItems/addItem/addItemTopping/transitionItemStatus/cancel` đã có; actor kiểm tra staff active | Tái sử dụng khi Staff thực sự tiếp nhận; không mở workflow nội bộ cho guest |
| POS | `PosController` dùng draft `pos_cart`, gọi `createWithItems`, redirect Order Detail | Giỏ Customer phải tách khỏi POS |
| Inventory | `InventoryService::startPreparing` lock item/ingredient, consume ledger tại pending → preparing | Gửi yêu cầu/tiếp nhận không được trừ kho |
| Payment | Worktree có `PaymentService::recordPaid`; source còn served-before-paid và completed/trả bàn ngay, chưa hỗ trợ POS trả trước WORKFLOW-002 | Source cần task riêng sau chốt gate mới; không coi gửi request web là paid hoặc tự kế thừa POS trả trước |
| Promotion | Public methods `lockPromotionForPayment(Order)` và `consumeUsageAfterPaid(Order)` tồn tại | Không viết lại eligibility/usage trong Public Controller |
| Pickup scaffold | Có `PickupRequest`, `WorkspaceController::pickups/updatePickup`, Staff routes/view; không tìm thấy migration tạo `pickup_requests` trong repository | Không coi đây là module hoàn chỉnh hoặc bằng chứng bảng tồn tại ở local; updatePickup hiện chỉ đổi status, không tạo Order |
| Authorization | Requirements cho Admin hoạt động như Staff; Order/POS/Inventory và Order FormRequests còn staff-only, một số test cũng chặn Admin | Dependency sửa quyền cần task riêng trước nghiệm thu Staff/Admin tiếp nhận; không sửa ở DOC-001 |
| Test environment | `phpunit.xml`: SQLite `:memory:`, session/cache array; các feature test có fixtures riêng | Không chứng minh MySQL concurrency/schema production; chưa chạy test application trong DOC-001 |

Nguồn đối chiếu: các file trên; `database-design.md` §§6.13–6.18, 15–17; `docs/Rp-CAU_TRUC_CSDL_TU_MIGRATION.md`; `docs/REPORT_ROLE_UI_WORKFLOW.md`; toàn bộ 7 Markdown trong docs trước task và Word phân công. Các báo cáo tháng 9/đầu tháng 10 là snapshot cũ; tên cột/status sai khác phải đối chiếu migration, không copy lại vào implementation.

## 3. So sánh hai phương án — đã chọn A

| Nội dung | A — Yêu cầu public, Staff tiếp nhận tạo Order | B — Tạo Order ngay từ Customer |
| --- | --- | --- |
| Thời điểm có Order | Sau khi Staff chấp nhận; trước đó là yêu cầu | Ngay khi khách gửi |
| Creator | `orders.user_id` là Staff/Admin thực sự tiếp nhận | Phải duyệt thay creator contract, ví dụ nullable internal creator + source/acceptance metadata; không dùng tài khoản giả |
| Schema | Thêm persistence cho request/items/toppings và liên kết Order; giữ orders.user_id NOT NULL | Migration tiếp theo thay nullability/audit metadata; vẫn cần dữ liệu guest ownership/contact/idempotency |
| Snapshot | Request lưu snapshot khách đã xác nhận; Order lưu snapshot khi tiếp nhận theo chính sách giá được duyệt | Snapshot bán hàng xuất hiện trước Staff tiếp nhận; phải quy định điều chỉnh/từ chối |
| Tái sử dụng service | Staff gọi public operations OrderService trong outer transaction; không bypass actor | Cần operation public riêng có authorization/input boundary khác; không thể gọi createWithItems hiện tại bằng guest |
| History/reporting | Request chưa tiếp nhận không lẫn sales Order; giữ request → Order để truy vết | Order pending có thể gồm yêu cầu chưa được quán nhận; dashboard, pending queries và completion cần audit |
| Quyền | Guest chỉ cart/submit/xem phiếu của mình; tiếp nhận và lifecycle nội bộ | Phải tách quyền tạo public, tiếp nhận, vận hành và xem đơn ngay trong Order |
| Chi phí/rủi ro | Thêm bảng và Staff inbox; phải xử lý giá thay đổi trước tiếp nhận | Thay đổi contract Frozen và nhiều query/service/audit assumptions đang dùng |
| Rollback | Không xóa request/Order lịch sử; rollback schema sau khi có dữ liệu cần backup và kế hoạch riêng | Không thể chuyển user_id trở lại NOT NULL khi còn Order guest nếu chưa có kế hoạch dữ liệu |

**ĐÃ DUYỆT: phương án A**, theo câu trả lời trực tiếp của user ngày 26/10/2026. Request là yêu cầu mua hàng; chỉ sau Staff tiếp nhận mới có Order chính thức, với creator là tài khoản thật. Phương án B chỉ được giữ để tham khảo so sánh, không triển khai. Nội dung UI đề xuất: “Đã gửi yêu cầu — chờ quán tiếp nhận”.

Không tự nối vào `PickupRequest` chỉ vì tên tương tự. Nếu chọn tái sử dụng scaffold này thay tên mới, phải review ownership, contract status/items và migration trước; không giữ hai inbox song song.

## 4. Bộ quyết định nghiệp vụ — guest và A đã duyệt, các rule khác còn gate

| ID | Cần chốt | ĐỀ XUẤT bản đầu | Trạng thái |
| --- | --- | --- | --- |
| D01 | Danh tính | Guest không cần account; không có đăng ký/OTP/password Customer; không dùng actor Staff/Admin giả | ĐÃ DUYỆT 27/10/2026 |
| D02 | Loại đơn | Giao món web đến địa chỉ khách nhập; user chọn trực tiếp ngày 27/10. Không dùng đề xuất nhận tại quán trước đó. Mapping vào Order.order_type chỉ có dine_in/takeaway cần thiết kế riêng, không tự thêm enum delivery | ĐÃ DUYỆT giao đến địa chỉ; schema/operation giao còn review |
| D03 | Tiếp nhận/creator | Phương án A; request trước Order, Staff tiếp nhận mới tạo Order; giữ user_id bắt buộc và ba bảng request/items/toppings | ĐÃ DUYỆT NGUYÊN TẮC 26/10/2026; chi tiết schema vẫn review trước migration |
| D04 | Giá | Chốt snapshot quote tại lúc khách gửi; chỉ tiếp nhận nếu giá và cấu hình bán vẫn khớp. Nếu đổi: không tạo Order, báo khách kiểm tra menu và gửi yêu cầu mới | CHỜ DUYỆT |
| D05 | Sửa/hủy | Cho sửa cart trước gửi; không có thao tác sửa/hủy public sau gửi ở bản đầu; hướng dẫn liên hệ quán. Staff từ chối request chưa nhận hoặc hủy Order theo rule nội bộ hiện có | CHỜ DUYỆT |
| D06 | Theo dõi | Cùng browser/session với capability ngẫu nhiên, quyền xem tối đa 7 ngày và mất khi session hết; không tra cứu bằng phone/code, chưa có phục hồi hoặc xem cross-device | CHỜ DUYỆT |
| D07 | Liên hệ | User xác nhận tên, số điện thoại, địa chỉ giao món ngày 27/10. Lưu snapshot cùng request; không tự thêm email/CCCD/birthday từ dấu “...”. Đề xuất validation: trim; tên 1–255 ký tự, phone tối đa 20 theo pattern StoreCustomerRequest hiện có, địa chỉ giao bắt buộc tối đa 500, note tối đa 500 | ĐÃ DUYỆT ba trường và mục đích giao món; chi tiết validation/length còn review |
| D08 | Customer master | Không tự tạo/link/update Customer từ phone public. Request giữ contact snapshot; Order.customer_id để null bản đầu. Không ghi đè hồ sơ đã có dù trùng phone | CHỜ DUYỆT |
| D09 | Limits/idempotency | 20 dòng/cart, 20 món/dòng, 10 loại topping/dòng, 20 đơn vị/topping/dòng; 5 submit/phút/session và 20/phút/IP; CSRF, idempotency per submission | CHỜ DUYỆT |
| D10 | TTL/lưu thông tin | Request chưa nhận có hiệu lực 30 phút; quyền xem public 7 ngày. Đề xuất ẩn danh contact snapshot sau 30 ngày từ lúc request bị từ chối/hết hạn hoặc Order kết thúc, không xóa lịch sử bán hàng | CHỜ DUYỆT |
| D11 | Thu tiền | User chọn “cả 2” ngày 27/10: chuyển khoản trước (Staff xác nhận thực nhận rồi mới chuẩn bị) hoặc COD (chuẩn bị/giao trước, ghi paid khi xác nhận thu đủ lúc nhận hàng). Gửi request/Staff tiếp nhận/chọn COD không tự paid; không gateway | ĐÃ DUYỆT hai lựa chọn và ngoại lệ COD; payment intent/chi tiết giao-thu còn review |
| D12 | Thay đổi sau tiếp nhận | Bản đầu không cho Customer hoặc Staff sửa món/topping/quantity hay áp Promotion làm đổi quote của Order đến từ request. Staff vẫn chuyển lifecycle, thu tiền và hủy toàn Order theo rule hiện có. Nếu cần đổi nội dung: liên hệ khách, hủy khi còn đủ điều kiện và khách gửi yêu cầu mới; workflow xác nhận điều chỉnh riêng để sau | CHỜ DUYỆT |

Topping quantity được đề xuất độc lập với item quantity để tương thích công thức nội bộ. Dòng cart chỉ gộp khi variant, note sau chuẩn hóa và cấu hình topping khớp; việc gộp phải giữ nguyên tổng quantity topping đã chọn, không nhân ngầm theo số ly. UI cần ghi rõ topping áp dụng cho cả dòng. Contract cart phải có ví dụ số cụ thể và test trước triển khai.

Các con số D06/D09/D10 là đề xuất sản phẩm, không phải hạn mức có sẵn trong schema. Không tự áp dụng rate limit/expiry hoặc xóa/ẩn danh dữ liệu trước khi duyệt. Retention cần owner Customer và người phụ trách vận hành xác nhận; cập nhật timestamp/actor nội bộ không được che mất lịch sử xử lý.

## 5. Chi tiết migration proposal cho A — CHƯA tạo migration

Cấu trúc ba bảng mới đã được user chọn cùng A; giữ nguyên orders.user_id và các bảng Frozen. Các tên cột, type, constraint, index và chính sách lưu dưới đây vẫn là proposal cần review sau khi chốt rule; chưa được tạo hoặc chạy migration. Không lấy extension admin_menus làm quyền tự thêm bảng khác.

### 5.1 customer_order_requests

| Cột đề xuất | Type/constraint dự kiến | Ý nghĩa |
| --- | --- | --- |
| id | BIGINT UNSIGNED PK AUTO_INCREMENT | ID nội bộ, không dùng làm quyền public |
| reference | CHAR(26) NOT NULL UNIQUE, ULID | Mã hiển thị; không phải secret |
| status | VARCHAR(20) NOT NULL DEFAULT pending; CHECK pending/accepted/rejected | State request; hết hạn được suy ra từ expires_at, không thêm status Order |
| customer_name / customer_phone | VARCHAR(255) / VARCHAR(20) NOT NULL | Contact snapshot, không FK/unique identity |
| customer_address | VARCHAR(500) NOT NULL — chi tiết type/length proposal còn review | Địa chỉ giao món snapshot theo quyết định user; không đổi customers hoặc nhét địa chỉ vào Order.note |
| note | VARCHAR(500) NULL | Ghi chú public |
| payment_option | VARCHAR(30) NOT NULL; CHECK bank_transfer_prepaid/cod — chi tiết proposal cần review | Lựa chọn đã duyệt của khách, không phải Payment.status hoặc bằng chứng đã thu; không thêm cod vào enum payments.payment_method |
| quoted_subtotal | DECIMAL(15,2) NOT NULL, CHECK >= 0 | Tổng server của request, chưa có Promotion/tax |
| submission_key_hash | CHAR(64) NOT NULL UNIQUE | SHA-256 của key ngẫu nhiên server cấp, gắn draft owner; không key thuần do client tự chọn |
| payload_hash | CHAR(64) NOT NULL | Canonical payload fingerprint để phát hiện key dùng với payload khác |
| access_token_hash | CHAR(64) NOT NULL UNIQUE | Hash capability CSPRNG tối thiểu 256 bit; token thật giữ trong server session, không trả Model/token hash ra public |
| access_expires_at / expires_at | DATETIME NOT NULL | TTL xem và TTL tiếp nhận tách biệt |
| order_id | BIGINT UNSIGNED NULL UNIQUE FK orders.id RESTRICT/NO ACTION | Liên kết sau tiếp nhận; một Order không dùng cho hai request |
| handled_by | BIGINT UNSIGNED NULL FK users.id RESTRICT/NO ACTION | Staff/Admin đã tiếp nhận hoặc từ chối |
| handled_at | DATETIME NULL | Thời điểm xử lý |
| rejection_note | VARCHAR(500) NULL | Chỉ reason đã duyệt an toàn mới hiển thị public; không lộ ghi chú nội bộ |
| contact_redacted_at | DATETIME NULL | Dấu retention nếu D10 được duyệt |
| created_at / updated_at | Laravel timestamps | Lịch sử ghi nhận |

Index đề xuất `(status, created_at)`, `expires_at` và các FK chưa được cover. Request giao đến địa chỉ không cần table_id, reservation_id, user_id hoặc payment fields. Application kiểm tra accepted phải có order_id/handled_by/handled_at; rejected không có order_id. Không soft delete và không cascade lịch sử. Không tự map delivery thành Order.takeaway hoặc thêm enum: giữ service type + fulfillment metadata riêng hoặc migration mở rộng order_type là hai phương án cần review.

### 5.2 customer_order_request_items

- `id` BIGINT UNSIGNED PK; `request_id` FK NOT NULL → requests, RESTRICT/NO ACTION; index request_id.
- `variant_id` FK NOT NULL → product_variants, RESTRICT/NO ACTION. Product được suy ra từ variant, không nhận product_id từ guest.
- Snapshot: `product_name` VARCHAR(255), `variant_name` VARCHAR(50) nullable; `quantity` INT > 0; `unit_price` DECIMAL(12,2) >= 0; `topping_amount` DECIMAL(12,2) >= 0; `subtotal` DECIMAL(15,2) >= 0; `note` VARCHAR(500) nullable; Laravel timestamps.
- Không unique request_id+variant_id vì cùng variant có thể có note/topping khác nhau.

### 5.3 customer_order_request_item_toppings

- `id` BIGINT UNSIGNED PK; `request_item_id` và `topping_id` NOT NULL FK RESTRICT/NO ACTION.
- `topping_name` VARCHAR(255) NOT NULL; `price` DECIMAL(12,2) >= 0; `quantity` INT > 0; timestamps.
- UNIQUE `(request_item_id, topping_id)`; index topping_id.
- Tổng = price × quantity, không thêm row subtotal; giữ cùng ý nghĩa với order_item_toppings.

Không JSON blob tiền/items để né ràng buộc hoặc snapshot policy. `orders.user_id` tiếp tục NOT NULL; `orders.customer_id` null theo D08; creator không bị đổi thành guest/Customer. Không thu thập IP vĩnh viễn trong request chỉ để rate limit; counter cache/session đủ cho đề xuất ban đầu.

Migration task cần cập nhật thiết kế được duyệt trước, chạy trên database test riêng. Rollback kỹ thuật tạo bảng có thể drop dữ liệu extension: sau có request/Order thật phải backup và duyệt kế hoạch, không chạy down trên dữ liệu cần giữ. Retention chỉ redact contact theo rule được duyệt, không xóa Order/items/payment/ledger.

Phương án B nếu được chọn phải có schema proposal riêng: internal creator nullable, source + acceptance actor/time, contact ownership và idempotency; audit model creator null, report/dashboard pending, Service và test. Không được chỉ đổi nullable rồi mở endpoint.

## 6. Workflow đề xuất cho A

### 6.1 Cart và gửi yêu cầu

1. Draft server session dùng namespace `customer_order_cart`, độc lập `pos_cart`; không lưu secret vào localStorage/cookie đọc bằng JavaScript.
2. Khách chọn variant, quantities, topping và note. Server kiểm tra category active, product/variant/topping available, chưa soft-delete; tiền tính bằng integer minor units/decimal strings, kiểm tra overflow schema.
3. Review hiển thị giá server hiện tại. Server lưu quote/version, submission key và capability ngẫu nhiên trong draft server session trước POST; capability không gửi thành field client. POST không tin giá client. Nếu giá thay đổi giữa review và submit, trả review mới để khách xác nhận, không silently thu giá mới.
4. POST submit có CSRF và server-issued submission key gắn session/draft. Trong transaction tạo header/items/toppings và snapshots; không tạo Order/Payment/ledger hoặc sửa bàn. Chỉ clear draft khi commit thành công.
5. Cùng key và payload phải trả cùng request; payload khác bị từ chối. Quyền xem kết quả vẫn cần capability đúng session, không phải chỉ biết key. UNIQUE ở DB + xử lý collision đảm bảo qua nhiều workers; session lock đơn thuần không đủ.
6. Sau commit giữ capability và mapping submission → reference trong server session, chỉ xóa dữ liệu cart. Nếu session write sau commit thất bại, draft của bước review vẫn giữ capability/submission key; retry cùng owner có thể tìm lại request, so hash và hoàn tất mapping. Không phục hồi token thô từ hash, không cấp quyền cho session khác chỉ vì biết submission key. Test cả trường hợp session write failure.

### 6.2 Staff tiếp nhận hoặc từ chối

- Chỉ actor nội bộ active đúng quyền đã duyệt; không mở Staff routes cho public.
- Outer transaction lock request trước; kiểm tra pending, chưa quá expires_at, chưa có order_id. Tiếp nhận lặp trả Order đã liên kết cho actor hợp lệ, không tạo mới. Tiếp nhận/từ chối đồng thời phải tuần tự hóa trên request.
- Lock/recheck Catalog theo thứ tự nhất quán với OrderService hiện có; so khớp quote về giá, product/variant/topping identity và tên snapshot. Có mismatch/không bán: không tạo Order; trả lỗi cần khách xem lại và gửi request mới. Không tự thay giá, bỏ món hoặc topping.
- Khi đủ điều kiện: gọi `OrderService::create`, `addItem` cho từng dòng có note, `addItemTopping` cho cấu hình dòng; tất cả trong cùng outer transaction. Không dùng createWithItems hiện tại làm mất note/topping vì contract hiện chỉ variant/quantity.
- Order từ request giao đến địa chỉ có table_id null, creator = actor thật; mapping order_type cần chốt, không mặc định takeaway. Kiểm chứng tổng và snapshots khớp quote trước commit; cập nhật request.accepted/order_id/handled_by/handled_at. Nếu bước nào lỗi, rollback cả Order/items/toppings/request, giữ request chưa được nhận.
- Không persist trực tiếp Order trong Public Controller và không copy calculation. Nếu muốn workflow orchestration Service mới, giới hạn ở request → Order, tái sử dụng OrderService; không làm Service generic.
- Request rejected giữ snapshot, actor/time và reason an toàn; không tạo Order. Request hết hạn không thể tiếp nhận; GET chỉ hiển thị hết hạn, không tự ghi database.
- Sau tiếp nhận, nhánh chuyển khoản trước chỉ preparing sau xác nhận thực nhận/paid; nhánh COD được preparing/giao trước paid và thu khi khách nhận. Đây là hai lựa chọn user vừa duyệt, không dùng cờ bypass tự do từ client. Cả hai vẫn pending → preparing qua InventoryService, không consume ngoài workflow hoặc lần hai lúc thu. Request vừa gửi không Order/paid/consume; tiếp nhận hoặc chọn COD không mặc định đã trả tiền. Giao/thu/completion và lỗi giao cần contract chi tiết trước integration.
- Nếu D12 được duyệt, Order có liên kết request phải được backend chặn các thao tác làm đổi quote qua mọi endpoint hiện có, không chỉ ẩn form; Order do POS tạo vẫn giữ hành vi cũ. Chưa duyệt D12 thì phần này BLOCKED, không tự chọn một rule.

### 6.3 Khách xem xác nhận/trạng thái

- GET theo reference, kiểm tra capability trong server session với stored hash và access_expires_at; không chỉ dựa reference/id/phone. Request session A không xem được của B. Reference sai hoặc không quyền dùng response thống nhất để tránh enumeration.
- Token không nằm trong URL, analytics, log hoặc referrer; session driver phải giữ server-side, cấu hình production HTTPS/Secure/HttpOnly/SameSite phù hợp. Không thêm cơ chế public account recovery chưa duyệt.
- Public view chỉ allowlist: mã phiếu, món/topping snapshot, tổng đã xác nhận, thời điểm gửi và trạng thái xử lý/Order được phép. Không trả toàn Model, staff IDs, contact người khác, ledger, token hash hoặc bank transaction reference.
- Phân biệt “Chờ tiếp nhận”, “Đã tiếp nhận”, “Bị từ chối”, “Hết hạn” với tiến độ từng món và trạng thái thanh toán. Order pending không đồng nghĩa mọi món đều pending. Không tự suy ra completed khi tất cả món served.
- GET xem/cart/checkout không tạo Order/Payment, không trừ kho, không tự gia hạn access token. Mất session hoặc hết TTL: hướng dẫn liên hệ quán; không có endpoint tra cứu bằng phone đơn thuần.

## 7. UI và boundaries

Reuse public `layouts.public`, components và tokens/styles hiện có. Public không dùng layout Staff/POS. Dự kiến thêm chọn variant/topping vào menu detail, cart, review/contact, xác nhận/theo dõi; Staff inbox dùng internal layout và link Order Detail sau tiếp nhận. Dùng form submit/redirect, CSRF, validation, keyboard/focus rõ; 1440/768/375, không overflow.

Giao món đến địa chỉ khách đã được duyệt, thay thế đề xuất pickup-only trước đó. Chưa duyệt đối tác vận chuyển, điều phối tài xế, tracking GPS, phí/vùng giao, trạng thái giao hoặc cách ánh xạ served/completed. Không gateway, QR giả paid, đặt giờ, account Customer, loyalty, refund, split payment, public Promotion hoặc AJAX trong bản đầu. Không sửa nội bộ Inventory/Promotion/Reservation/Dashboard. Không tự xử lý tồn thấp tại submit như bảo đảm giữ hàng; chỉ Inventory lúc preparing quyết định đủ tồn.

## 8. Task breakdown sau khi duyệt

Các ID dưới đây là charter đề xuất trong phạm vi Customer Order, chưa cập nhật roadmap thành đã duyệt. Task vượt 90 phút phải tách tiếp trước implementation.

| Task | Dependency | Scope / output | Acceptance Criteria và Test Plan | Ước lượng |
| --- | --- | --- | --- | --- |
| DOC-001 (hiện tại) | Task user | Chỉ file contract này | Có evidence, A/B, 10+ quyết định, schema proposal, risks và charter; static review/diff | 60–90 phút |
| DOC-002 | D01/A đã duyệt; các gate liên quan tiếp tục review | Cập nhật requirements/design/plan đúng quyết định WORKFLOW-002; chỉ tài liệu | Traceable approval, không suy rộng rule POS sang web; rule/schema còn thiếu được liệt kê rõ | 30–60 phút |
| DB-001 | DOC-002 | Migration mới theo proposal được duyệt, không sửa lịch sử cũ | Constraints/index/FK/precision; migrate/rollback trên DB test riêng; không đụng local data | 60–90 phút |
| MODEL-001 | DB-001 | Custom Models/casts/relationships theo architecture, không tự sửa generated Base | Relationship/snapshot/cast tests; truy vết request → Order và preserve history | 30–60 phút |
| BE-001 | DOC-002, Catalog | Public session cart + FormRequest/Service nếu phù hợp | Session isolation; add/edit/remove; canonical line identity; chính xác topping independent; tampering/limits tests | 60–90 phút |
| BE-002 | BE-001, MODEL-001 | Submit/review quote, transaction, idempotency, quyền xem | Catalog đổi giá/status; rollback; duplicate same/different payload; ownership/token expiry; không Order/stock/payment side effects | Tách submit và tracking thành task con nếu >90 phút |
| ACCESS-FIX-001 | Requirement quyền nội bộ đã duyệt | Task riêng chỉnh mismatch Admin/Staff ở Order/POS/Inventory actor checks; owner Inventory review | Admin/Staff active thành công, inactive/guest bị chặn; actor audit chính xác; không đổi thuật toán stock | 60–90 phút |
| BE-003 | BE-002, ACCESS-FIX-001, Order/Inventory/Payment regression | Staff accept/reject orchestrator; không đổi service owner khác | Một request tối đa một Order; mismatch quote chặn; accept/reject race; đầy đủ note/topping; rollback mọi bước | 60–90 phút, tách thêm nếu cần |
| FE-001 | BE-001/002 | Public menu/cart/review/tracking Blade, không redesign | Browser render, error/empty/out-of-stock; keyboard; 1440/768/375; build | Tách cart và tracking thành 2 task 60–90 phút nếu cần |
| FE-002 | BE-003 | Staff inbox/tiếp nhận/từ chối/link Order | Backend auth/ownership; CSRF; form error; internal style; browser/build | 60–90 phút |
| TEST-001 | BE/FE accepted | Cross-module feature/manual checklist | Guest → request → Staff → served → paid; isolation, rollback, limits, duplicated submit; MySQL concurrency riêng | Tách automated và manual thành 2 task nếu >90 phút |
| OPS-001 | D10 duyệt, schema/flow accepted | Retention/expiry operation và hướng dẫn vận hành | Không tiếp nhận request hết hạn; token TTL; redaction đúng thời điểm, không xóa lịch sử; test job/command nếu có | 30–90 phút |

Không bắt đầu BE-001 chỉ vì cart dùng session và chưa cần migration: D01 và D02 đã duyệt mục tiêu, nhưng D04/D09, cấu trúc topping và schema phục vụ giao món vẫn là dependency tương ứng. Roadmap namespace WORKFLOW-002 trong plan.md là phần phân rã mới nhất; bảng trên giữ charter ban đầu để trace, không tự thực thi cả hai bộ task.

## 9. Verification / manual acceptance plan cho implementation

Hiện DOC-001 chỉ static review, không chạy application tests/build vì không đổi PHP/Blade/CSS/JS/schema. Không báo application PASS từ tài liệu. Trước test tương lai phải kiểm tra phpunit.xml và target DB, không migrate:fresh local.

1. Guest A thêm variant/topping/note; B và POS Staff không thấy cart của A. Hai dòng cùng variant khác topping/note giữ cấu hình đúng.
2. Thay price hoặc availability bằng thao tác Admin hợp lệ giữa review/submit: yêu cầu xác nhận lại hoặc chặn, không silently đổi quote. Không dùng SQL thủ công để inject lỗi.
3. Submit hợp lệ: một request đầy đủ, chưa có Order/Payment/ledger/table change; clear cart sau commit, thấy thông báo chờ nhận.
4. Repeat cùng key/payload: cùng request; khác payload: lỗi; không lộ request khi thiếu quyền. Session write failure phải có test phục hồi đúng owner.
5. B thử reference/ID của A, phone trùng, token thiếu/sai/hết TTL: không thấy dữ liệu A. Contact snapshot không ghi đè customers.
6. Staff active tiếp nhận hợp lệ: đúng một Order liên kết request giao đến địa chỉ, có đầy đủ note/topping/giá, contact/address snapshot và đúng creator; order_type theo thiết kế mapping được duyệt, không mặc định takeaway. Admin theo quyền đã duyệt; guest/inactive không tiếp nhận. Test thiếu/địa chỉ quá dài và privacy theo validation/ownership được chốt.
7. Catalog đổi trước tiếp nhận, request hết hạn/bị từ chối: không có Order một phần; Staff có hướng dẫn liên hệ khách. Hai actor accept/reject đồng thời không tạo kết quả mâu thuẫn.
8. Chuẩn bị món qua Inventory rồi ready/served; kiểm tra stock/ledger actor đúng một lần; thiếu stock rollback. Customer không chuyển trạng thái món.
9. Thu cash/bank_transfer tại quán theo contract đã duyệt: Order completed đúng, repeat không có paid/usage thứ hai. Public không xác nhận nhận tiền.
10. Rollback do từng lỗi storage/Order/item/topping/link được test bằng model events/test doubles trong database test, không phá local data.
11. Kiểm tra UI empty/error/long note/mobile/keyboard và public không hiển thị dữ liệu Staff/Payment nhạy cảm.
12. Kiểm tra retention/expiry bằng test clock, không chờ hoặc tự xóa dữ liệu local. Nếu retention chưa có hoặc chưa duyệt, nghiệm thu tính năng còn PARTIAL.

Lệnh dự kiến: targeted CustomerOrder tests theo tên được tạo; `php artisan test --filter=PublicMenu`, `--filter=StaffOrder`, `--filter=Pos`, `--filter=InventoryServiceTest`, `--filter=Payment`, `--filter=PromotionService`; `php artisan route:list`; `npm.cmd run build` khi đổi frontend; `php artisan test`; `git diff --check`. PHP local: `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`.

SQLite sequential tests không chứng minh deadlock/concurrency/MySQL CHECK behavior. Cần database MySQL test riêng với connection độc lập cho duplicate submit và accept/reject race; không dùng database đang demo.

## 10. Risks, blockers và điều kiện mở implementation

- **Blocker nghiệp vụ:** A/ba bảng nguyên tắc, guest, tên/phone/địa chỉ, giao đến địa chỉ và cả chuyển khoản trước/COD đã duyệt (D01/D02/D03/D07/D11). Chi tiết validation/schema, phí/vùng giao, giao thành công/completion, người/thao tác xác nhận thu và giao thất bại còn gate tương ứng. COD là ngoại lệ đã duyệt cho chuẩn bị trước paid; không tự thêm gateway.
- **Blocker quyền:** Admin vẫn bị một số guard Order/Inventory chặn dù requirements cho phép; cần ACCESS-FIX-001, không sửa ngầm ở task này.
- **Git:** worktree Payment chưa commit; contract mới không chồng file. Không commit chung Customer/Payment nếu engineer chưa review phạm vi.
- **Frozen schema:** cần change request/document update trước DB-001. Migration history và số bảng trong báo cáo cũ không tự cập nhật.
- **Price/inventory:** quote không giữ giá/hàng đến khi Staff nhận; cần thông điệp rõ để tránh khách hiểu đã có Order/stock reservation. D12 cần bảo vệ agreement khi Staff sửa sau nhận.
- **Privacy:** phone không xác thực, bearer capability mất có thể mất quyền xem; TTL/retention/session driver cần duyệt và test; không phục hồi bằng phone tự khai.
- **Legacy scaffold:** PickupRequest thiếu migration và chưa chuyển Order; cần chọn thay thế/tái sử dụng ở DOC-002, không thêm hai workflow song song.
- **Out of scope:** các reports cũ dùng field/status không còn khớp; không sửa hàng loạt. Root documents tham chiếu docs/admin-menu-architecture.md nhưng file không có trong 7 Markdown quan sát; không ảnh hưởng DOC-001.
- **Unverified:** schema vật lý/local DB, full suite hiện tại, frontend runtime, MySQL concurrency, delivery/receipt thực tế; không được gán PASS.

## 11. Engineer decision record

Phê duyệt được ghi nhận đúng phạm vi câu trả lời trực tiếp của user, không suy rộng sang các câu hỏi chưa có đáp án.

| Nhóm cần duyệt | Nội dung | Quyết định |
| --- | --- | --- |
| Luồng và schema | Phương án A; request trước Order; 3 bảng request/items/toppings; giữ orders.user_id bắt buộc; creator thật khi tiếp nhận | User chọn A ngày 26/10/2026; chi tiết migration cần review, chưa triển khai |
| Guest và thông tin liên hệ | Khách web không đăng ký/đăng nhập, không role Customer trong users; gửi tên, số điện thoại và địa chỉ giao món cùng yêu cầu | ĐÃ DUYỆT ngày 27/10/2026; validation/length vẫn review |
| Giao đến địa chỉ | User trả lời “Giao món đến địa chỉ khách nhập” | ĐÃ DUYỆT ngày 27/10/2026; không đồng nghĩa duyệt phí/vùng/đối tác vận chuyển hoặc enum delivery |
| Hai lựa chọn thanh toán web | User trả lời “cả 2” cho chuyển khoản trước và COD; COD thu khi nhận hàng, là ngoại lệ rule trả trước riêng cho web | ĐÃ DUYỆT ngày 27/10/2026; POS tại quán vẫn trả trước; chi tiết giao/thu/payment intent còn review |
| Hành vi mua hàng còn lại | D04/D05/D07 chi tiết/D08/D11/D12 và thiết kế giao món; thời điểm giá, contact linkage, sửa/hủy, thanh toán và completion | TBD theo đúng phạm vi còn thiếu |
| Bảo vệ/vận hành | D06/D09/D10; guest capability, idempotency, limits, expiry và retention | TBD |

**Recommended next task:** engineer review contract này; chỉ sau duyệt mới thực hiện DOC-002, rồi các task nhỏ theo dependency. Không tự triển khai backend/route/UI/migration từ DOC-001.

## 12. Change record — CAFE-CUSTOMER-POS-WORKFLOW-002, 27/10/2026

- Đã cập nhật D01 và phần được duyệt của D07; giữ phê duyệt A/ba bảng nguyên tắc. Không cấp account, không auto-link/ghi đè Customer theo phone, không mở Staff endpoints.
- User bổ sung tên/số điện thoại/địa chỉ cho web và chọn giao đến địa chỉ đó. Đã cập nhật D02/D07 và proposal customer_address trong request header; chưa tạo migration. Không thu thêm thông tin cá nhân chưa được yêu cầu hoặc tự thêm các tính năng giao vận khác.
- POS tại quán nhận tiền/xác nhận đủ → atomic tạo Order/Payment và preparing. Web chọn chuyển khoản trước hoặc COD theo xác nhận mới nhất “cả 2”; COD được làm/giao trước và thu khi nhận, thay rule trả trước riêng cho nhánh này. Request web không tự paid/preparing; chi tiết thao tác giao/thu vẫn cần thiết kế.
- Completion, dine-in release, thanh toán Order đã tồn tại, thu tiền web sau tiếp nhận, xử lý tiền đã nhận khi lỗi và idempotency persistence đều có gate cụ thể P01–P07. Customer limits/TTL/retention vẫn chưa duyệt, không tự áp các số của proposal.
- Thay đổi ở giai đoạn DOC chỉ chạm CUSTOMER_ORDER_CONTRACT.md, PAYMENT_CHECKOUT_CONTRACT.md, requirements.md, database-design.md và plan.md. Không migration/model/controller/route/UI runtime, không commit/push/merge.
- Chưa có manual UI Customer để test; test plan ở mục 9 là acceptance tương lai, không phải kết quả test đã chạy. Các ràng buộc actor/FK/decimal/immutable ledger vẫn giữ nguyên.
