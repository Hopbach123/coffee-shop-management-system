# Coffee Shop Management System – Requirements

## Approved extension — Finance/Sales receipts (17/11/2026)

Owner cho phép tích hợp Finance cash/bank trước served, không bắt buộc paid trước preparing. Finance chỉ ghi nhận thực thu/receipt, không tự preparing/consume/completed. Cash nhận đủ tổng server và trả tiền thừa; bank nhận chính xác tổng, Staff xác minh thực nhận và nhập bank reference. Operation key có replay/payload conflict, một paid mỗi Order, bank reference không tái sử dụng tại quán. Sales giao khi paid, hoàn tất khi mọi món served; không sửa tổng sau thu. Không split/partial/gateway/COD/refund tự động. Receipt metadata lưu bằng migration mới; chưa áp dụng lên DB dự án trong task.

## Approved extension — Simple Online Pickup (26/10/2026)

Theo yêu cầu user triển khai schema trên feature/schema-template-sync và phạm vi rút gọn mục 7 của bảng phân công: hỗ trợ purchase_type counter (POS), scheduled (hẹn đến lấy), online (web lấy sớm). scheduled/online chỉ takeaway, không shipper/phí giao/gateway/COD/slot/holds/refund tự động. Khách không cần tài khoản; request lưu snapshot liên hệ, món/topping và giờ hẹn nếu scheduled. Staff/Admin duyệt tạo một Order, user_id vẫn bắt buộc là người nhận đơn; UNIQUE request trên Order chống tạo trùng. Request có pending/accepted/rejected/cancelled; giữ các trạng thái Order/món/Payment hiện có. Loại mua không phải status.

Thanh toán cash/bank_transfer; payment_timing on_receipt hoặc prepaid, prepaid chỉ bank_transfer do nhân viên xác nhận thực nhận. Hẹn có pickup_at; online lấy sớm không có giờ hẹn. Token tra cứu lưu SHA-256 hash, submission key chống gửi lặp. Snapshot giá do server tính; thay đổi giá cần khách xác nhận trong workflow sau. Extension này phê duyệt cấu trúc dữ liệu, không tuyên bố Service/UI đã triển khai. Chi tiết vật lý tại database-design.md; schema không thể thay thế transaction accept/payment, kiểm giờ mở, quyền và đối chiếu giá.

## Approved extension — Dynamic Admin Menu (20/10/2026)

Quyết định mới của user ngày 20/11/2026 thay thế điều hướng Admin database-driven: sidebar hiện dùng menu cố định tại config/admin_navigation.php, không phụ thuộc admin_menus hoặc trạng thái dữ liệu menu. Hiển thị các nhóm Tổng quan; Thực đơn & công thức; Nguyên liệu & kho; Không gian & đặt bàn; Khách hàng & ưu đãi; Tài khoản. Chỉ liên kết named route GET Admin thực sự tồn tại, không cần tham số. Size/công thức đi qua danh sách biến thể; nhập kho/kiểm kê/lịch sử đi qua danh sách nguyên liệu rồi chọn bản ghi. Không tạo màn giả hoặc link thiếu ID. Staff/POS giữ khu vực riêng; Admin chuyển qua Mở POS và quay về Quản trị. Backend auth/role/validation không đổi. Giữ dữ liệu và module CRUD menu cũ để bảo toàn công việc, nhưng không dùng nó điều khiển sidebar hiện tại.

Lịch sử trước quyết định này: Engineer đã duyệt database-driven Admin navigation với group/link tối đa 3 cấp; rules và registry vẫn áp dụng cho module menu cũ nếu truy cập trực tiếp, không còn là nguồn của sidebar ứng dụng. Không tự xóa bảng, migration, Model hoặc dữ liệu menu cũ trong task chuyển điều hướng.

## 1. Document Purpose

Tài liệu này là nguồn tham chiếu chính thức về yêu cầu nghiệp vụ và chức năng ở mức tổng quan của Coffee Shop Management System. Tài liệu xác định hệ thống phải hỗ trợ những năng lực nào, các vai trò được phê duyệt, ranh giới phạm vi và những nội dung còn cần làm rõ.

Tài liệu không mô tả thiết kế cơ sở dữ liệu, giao diện chi tiết hoặc cách hiện thực kỹ thuật. Cấu trúc kỹ thuật của hệ thống được quy định riêng trong `architecture.md`. Baseline nghiệp vụ và dữ liệu chi tiết đã được phê duyệt trong SRS v1.2 Database Frozen; khi phần mô tả tổng quan dưới đây cũ hoặc ít chi tiết hơn, SRS v1.2 và `database-design.md` đã đồng bộ là nguồn áp dụng.

## 2. Project Overview

Coffee Shop Management System là ứng dụng web phục vụ hoạt động quản lý và vận hành ban đầu của một địa điểm cà phê. Hệ thống cung cấp khu vực nội bộ cho quản trị viên và nhân viên, đồng thời cung cấp các trang công khai để khách hàng xem thông tin quán và thực đơn.

Phạm vi nghiệp vụ tập trung vào người dùng nội bộ, bàn, đặt bàn, thực đơn, đơn hàng, thanh toán, khuyến mãi, tồn kho và thông tin tổng quan quản lý.

## 3. Project Objectives

- Hỗ trợ quản trị và vận hành quán cà phê trên một hệ thống thống nhất.
- Phân định trách nhiệm ở mức tổng quan giữa Admin, Staff và Customer.
- Cung cấp thông tin trạng thái cần thiết cho hoạt động bàn, đặt bàn, đơn hàng và thanh toán.
- Quản lý dữ liệu thực đơn, khuyến mãi và tồn kho trong phạm vi được phê duyệt.
- Cung cấp thông tin tổng quan giúp Admin theo dõi hoạt động kinh doanh.
- Cung cấp trải nghiệm công khai phù hợp trên nhiều kích thước màn hình để khách hàng xem thông tin quán và thực đơn.
- Tạo nền tảng yêu cầu rõ ràng để các công việc sau phân rã thành use case và chức năng cụ thể.

## 4. System Actors

Hệ thống có đúng ba vai trò nghiệp vụ được phê duyệt: Admin, Staff và Customer.

### 4.1 Admin

Admin là quản trị viên hoặc người quản lý hệ thống. Admin có trách nhiệm ở mức tổng quan:

- Truy cập các chức năng quản trị.
- Quản lý tài khoản người dùng nội bộ và nhân viên.
- Quản lý cấu hình bàn.
- Quản lý dữ liệu thực đơn.
- Quản lý khuyến mãi.
- Theo dõi đơn hàng và thanh toán.
- Quản lý thông tin liên quan đến tồn kho.
- Xem dashboard và báo cáo tổng quan.

### 4.2 Staff

Staff là nhân viên tham gia vận hành quán. Staff có trách nhiệm ở mức tổng quan:

- Xác thực để truy cập hệ thống nội bộ.
- Xem tình trạng sẵn sàng của bàn.
- Tạo và cập nhật đơn hàng của khách.
- Thêm, cập nhật hoặc loại bỏ món trong đơn hàng.
- Hỗ trợ nghiệp vụ đặt bàn.
- Xử lý thanh toán.
- Xem thông tin vận hành có liên quan đến công việc.

Quy trình POS chi tiết và các quyền vận hành ngoài danh sách trên là TBD.

### 4.3 Customer

Customer là khách hàng của quán cà phê. Trong phiên bản đầu:

- Có thể truy cập các trang công khai mà không cần xác thực.
- Có thể xem thông tin thực đơn.
- Có thể xem thông tin giới thiệu về quán.
- Có thể tham gia quy trình đặt bàn khi chức năng này được triển khai.
- Extension được user xác nhận ngày 27/10/2026 (`CAFE-CUSTOMER-POS-WORKFLOW-002`): khách chọn món trên website và để lại tên, số điện thoại, địa chỉ, không cần đăng ký/đăng nhập; user chọn giao món đến địa chỉ khách nhập. Các thông tin này gắn với yêu cầu giao món; không tự thêm trường cá nhân khác từ dấu “...”. Khách gửi yêu cầu; nhân viên tiếp nhận mới tạo Order bằng tài khoản nội bộ thật. Không thêm role customer vào users hoặc dùng actor nội bộ giả. Giao đến địa chỉ là phạm vi mới được duyệt; phí/phạm vi giao, thao tác xác nhận giao và phương thức thu từ xa chưa được tự quyết.

Tài khoản Customer không thuộc yêu cầu bắt buộc của phiên bản đầu.

## 5. Business Domains

### 5.1 User

Mục tiêu của domain User là quản lý danh tính và quyền truy cập của người dùng nội bộ. Phạm vi gồm tài khoản Admin, tài khoản Staff, danh tính phục vụ xác thực, trạng thái tài khoản và quyền truy cập dựa trên vai trò.

### 5.2 Table

Mục tiêu của domain Table là đại diện cho bàn hoặc khu vực vật lý được quán sử dụng. Phạm vi gồm thông tin bàn, tình trạng sẵn sàng hoặc trạng thái bàn, quan hệ với khu vực quán khi phù hợp, và khả năng hỗ trợ quy trình đơn hàng hoặc đặt bàn.

### 5.3 Reservation

Mục tiêu của domain Reservation là hỗ trợ đặt bàn trước. Phạm vi gồm ghi nhận thông tin đặt bàn, thông tin liên hệ của khách, liên kết với bàn khi phù hợp và theo dõi vòng đời hoặc trạng thái đặt bàn.

Phí hủy, tiền đặt cọc, giới hạn thời gian và các chính sách đặt bàn chi tiết là TBD.

### 5.4 Menu

Mục tiêu của domain Menu là quản lý sản phẩm quán cung cấp. Phạm vi phiên bản 1 gồm danh mục, sản phẩm, biến thể/kích cỡ, topping dùng chung, tình trạng sẵn có và thông tin sản phẩm hiển thị cho người dùng. Giá bán nằm ở biến thể; sản phẩm không lưu giá trực tiếp.

### 5.5 Order

Mục tiêu của domain Order là hỗ trợ đơn `dine_in` và `takeaway`. Phạm vi gồm tạo đơn, liên kết với Staff, bàn và reservation khi phù hợp, thêm/cập nhật/hủy món, topping và ghi chú, cùng việc tính các giá trị đơn. Vòng đời Order gồm `pending`, `completed`, `cancelled`; tiến độ món nằm ở Order Item với `pending`, `preparing`, `ready`, `served`, `cancelled`. Thuế/phụ phí mặc định bằng 0 cho đến khi có cấu hình nghiệp vụ mới được duyệt.

Extension Customer đã chọn phương án A: yêu cầu public có dữ liệu header/items/toppings, Staff tiếp nhận mới tạo Order; `orders.user_id` tiếp tục bắt buộc và là người tiếp nhận thực sự. Cấu trúc ba bảng được chọn ở mức nguyên tắc; chi tiết schema, loại đơn web, chốt giá, quyền sửa/hủy, tracking, limits và retention chưa được mặc định phê duyệt. Chi tiết và trạng thái quyết định tại `docs/CUSTOMER_ORDER_CONTRACT.md`.

### 5.6 Payment

Mục tiêu của domain Payment là ghi nhận lịch sử thử thanh toán bằng `cash` hoặc `bank_transfer`, với trạng thái `pending`, `paid`, `failed`, `refunded`. Phiên bản 1 không hỗ trợ split/partial payment; một Order có thể có nhiều lần thử nhưng tối đa một Payment `paid`. Tích hợp cổng thanh toán bên ngoài không thuộc phạm vi hiện tại.

Quy tắc chung đã duyệt: Admin/Staff active xác nhận đã nhận đủ tiền thực tế trước khi ghi paid cho cash/bank_transfer; mã giao dịch chuyển khoản không bắt buộc. Không tạo mới pending/failed trong bản checkout này nhưng giữ lịch sử cũ; validation failure không tạo failed Payment. Promotion đã áp dụng giữ snapshot, kiểm tra quota và tăng lượt dùng qua public contract PromotionService khi paid. Tổng thanh toán phải lớn hơn 0, không split/partial hoặc paid trùng.

Thay đổi POS tại quán được user xác nhận ngày 27/10/2026 (`CAFE-CUSTOMER-POS-WORKFLOW-002`): chọn món → kiểm tra tổng server → nhận tiền → xác nhận nhận đủ tiền → lưu Order + món/topping + Payment paid → chuyển món pending → preparing qua InventoryService. Persistence và chuyển preparing phải atomic; lưu Order trước Payment trong cùng transaction vì FK không làm đổi thứ tự thao tác của người dùng. Sau transaction này không tự completed hoặc trả bàn sớm. Payment paid, Order status và Item status là ba chiều độc lập.

User xác nhận ngày 27/10/2026: POS tại quán nhận đủ tiền rồi mới làm món. Với web giao đến địa chỉ, câu trả lời mới nhất “cả 2” duyệt hai lựa chọn: (1) chuyển khoản trước, nhân viên xác nhận nhận tiền thật rồi mới chuẩn bị/giao; (2) COD, quán được chuẩn bị/giao trước, chỉ ghi paid sau xác nhận thu tiền khi khách nhận hàng. COD là ngoại lệ được duyệt cho web đối với rule trả trước, không thay rule POS. Gửi request, chọn phương thức hoặc Staff tiếp nhận không tự là paid; không thêm gateway hoặc xác nhận chuyển khoản tự động.

Contract ngày 26/10/2026 yêu cầu served trước paid và completed/trả bàn cùng thanh toán là baseline lịch sử, **đã được thay thế theo từng operation mới**. Không áp điều kiện served trước paid hoặc auto-completion cũ cho POS/web chuyển khoản trước; COD có nhánh thu khi nhận riêng, không coi chọn COD là paid. Compatibility với Order cũ, thời điểm completed/trả bàn và thao tác ghi thu/giao thành công cho web cần chốt riêng. DB rollback không hoàn lại tiền đã nhận ngoài thực tế: khi lỗi phải giữ cart/key hoặc trạng thái nghiệp vụ phù hợp, báo chưa hoàn tất ghi nhận và kiểm tra kết quả theo idempotency trước khi thử lại/thu thêm. Chi tiết, proposal xử lý ngoại lệ và các gate còn chờ tại `docs/PAYMENT_CHECKOUT_CONTRACT.md`.

### 5.7 Promotion

Mục tiêu của domain Promotion là hỗ trợ giảm theo phần trăm hoặc số tiền cố định, khoảng hiệu lực, giá trị đơn tối thiểu, mức giảm tối đa và giới hạn lượt dùng. Phiên bản 1 cho phép tối đa một Promotion trên mỗi Order; `used_count` chỉ tăng sau khi Payment thành công. Voucher engine ngoài baseline này cần phê duyệt riêng.

### 5.8 Inventory

Hoàn thiện kho theo yêu cầu feature/inventory-completion ngày 06/11/2026: cảnh báo trên danh sách Admin cho nguyên liệu đang dùng gồm hết tồn (0), dưới ngưỡng (0 < tồn < tối thiểu), đủ ngưỡng (tồn > 0 và >= tối thiểu). Ngừng dùng không cảnh báo, đã xóa không hiển thị; lọc kết hợp tên và phân trang. Cảnh báo không tự khóa bán/giữ kho. User giao lựa chọn chính sách thiếu công thức: để không bỏ qua tiêu hao khi chưa có stock_mode, mọi biến thể phải có ít nhất một dòng công thức hợp lệ trước khi bắt đầu pha; nếu thiếu, giữ món pending, không trừ tồn/log và báo Admin cấu hình. Chỉ pha khi đơn cha pending, actor nội bộ active, mọi nguyên liệu active/chưa xóa và đủ tồn; tồn, ledger và trạng thái món cùng transaction. Gửi lại lệnh pha đã xử lý bị từ chối nhưng không trừ lần hai. Công thức hiện hành đọc khi pha; sửa sau đó không thay lịch sử đã ghi. Không tự trừ topping, giữ kho hoặc version engine.

Theo yêu cầu hoàn thiện feature/inventory-adjustments ngày 06/11/2026: Admin đang hoạt động ghi điều chỉnh tăng/giảm, hao hụt hoặc kiểm kê một nguyên liệu đang dùng. Lý do bắt buộc tối đa 1000 ký tự; đơn vị theo Ingredient, không quy đổi. Kiểm kê nhập tồn thực tế không âm; hệ thống tính chênh lệch, không cho ghi nếu tồn/lịch sử đã đổi từ lúc mở phiếu và yêu cầu mở phiếu mới. Không chênh lệch thì thông báo không cần điều chỉnh, không tạo movement bằng 0. Admin ghi phiếu là người xác nhận điều chỉnh, chưa có quy trình đề xuất/duyệt hai người. Tồn và movement cùng transaction, chống gửi lặp; lịch sử chỉ đọc. Không thêm schema hoặc thay luồng tiêu hao.

Mục tiêu của domain Inventory là quản lý nguyên liệu, tồn hiện tại, định mức tối thiểu, công thức theo biến thể và lịch sử biến động `import`, `consume`, `adjustment`, `waste`. Tồn kho chỉ bị trừ trong transaction khi Order Item chuyển `pending` sang `preparing`; mọi thay đổi tồn phải có log và không được làm tồn âm. Nhà cung cấp, mua hàng và ERP kho nằm ngoài phạm vi.

Phạm vi rà soát nhập liệu nguyên liệu ngày 13/10/2026 theo yêu cầu user: dùng danh sách đơn vị chuẩn `g`, `kg`, `ml`, `l`, `cái` tại cấu hình ứng dụng, không thêm bảng hoặc enum database. Số lượng và giá vốn tính theo cùng đơn vị; chỉ cho đổi đơn vị khi tồn bằng 0 và chưa có công thức hoặc lịch sử kho. Không tự động quy đổi số lượng. CRUD không được sửa tồn kho. Tên bắt buộc, tối đa 255 ký tự; ngưỡng tồn và giá vốn không âm, đúng độ chính xác và giới hạn migration. Tên trùng không bị cấm bởi baseline hiện tại.

Điều chỉnh theo yêu cầu user: đơn vị nguyên liệu có thêm lựa chọn `other` (Khác). Admin nhập tên đơn vị cụ thể tối đa 50 ký tự, có ít nhất một chữ cái; cho phép chữ Unicode, số, khoảng trắng, dấu chấm, gạch ngang và `/`. Chuẩn hóa chữ thường và khoảng trắng trước khi lưu vào cột `unit` hiện có. Giá trị `other` không được lưu làm đơn vị; tên mới vẫn chịu quy tắc khóa đơn vị khi nguyên liệu đã sử dụng. Danh sách chuẩn là gợi ý lựa chọn, không còn là danh sách đóng.

Quản lý công thức theo biến thể (feature/product-recipes): Admin thêm nguyên liệu đang dùng và chưa xóa; mỗi nguyên liệu chỉ xuất hiện một lần trong một biến thể. Định lượng cho một phần lớn hơn 0, tối đa 3 chữ số thập phân theo DECIMAL(12,3); đơn vị lấy từ Ingredient. Cho phép sửa định lượng hoặc bỏ dòng cấu hình, không thay đổi tồn và lịch sử kho. Nguyên liệu đã xóa/ngừng dùng trong công thức cũ vẫn hiển thị cảnh báo để Admin xử lý; không cho thêm mới. Biến thể tạm hết vẫn được cấu hình, biến thể đã xóa không truy cập được. Sửa dòng không đổi nguyên liệu/biến thể của dòng đó.

### 5.9 Dashboard

Mục tiêu của domain Dashboard là cung cấp cái nhìn tổng quan cho quản lý. Phạm vi gồm tổng quan doanh thu, thống kê đơn hàng, chỉ số bán sản phẩm và thống kê vận hành cơ bản.

Theo yêu cầu gộp trang và thiết kế dashboard được user xác nhận ngày 20/11/2026: `/admin` là trang tổng quan duy nhất; `/admin/dashboard` chuyển về `/admin`. Kỳ chọn 7 hoặc 30 ngày gần nhất gồm hôm nay theo Asia/Ho_Chi_Minh; dữ liệu ngày giờ lưu UTC. KPI theo kỳ gồm tổng Payment đang paid theo paid_at, giá trị/số Order completed theo completed_at, số Order phát sinh theo ordered_at và giá trị trung bình trên đơn completed. Hai cách ghi nhận tiền và hoàn thành đơn được phân biệt rõ; chưa phải doanh thu thuần/lợi nhuận. Payment trùng paid/Order hoặc paid thiếu ngày nhận tiền phải cảnh báo, không trình bày số liệu thu theo kỳ như đã xác minh. Biểu đồ đường chọn tiền đã thu hoặc giá trị hoàn thành, có trục, ngày không phát sinh và bảng số liệu thay thế. Thống kê toàn bộ thời gian và tình trạng bàn hiện tại tách khỏi KPI theo kỳ. Không bổ sung workflow thanh toán/hoàn tiền, schema, thư viện chart hoặc viết dữ liệu từ dashboard.

## 6. High-Level Functional Requirements

- **FR-01 – Truy cập nội bộ:** Hệ thống phải cho phép Admin và Staff xác thực để truy cập các chức năng nội bộ phù hợp với vai trò.
- **FR-02 – Quản lý người dùng:** Hệ thống phải cho phép Admin quản lý tài khoản Admin và Staff, trạng thái tài khoản và quyền truy cập theo vai trò.
- **FR-03 – Quản lý bàn:** Hệ thống phải hỗ trợ Admin quản lý thông tin bàn và cho phép các vai trò vận hành được phê duyệt xem tình trạng bàn.
- **FR-04 – Đặt bàn:** Hệ thống phải hỗ trợ ghi nhận, liên kết và theo dõi trạng thái đặt bàn ở mức nghiệp vụ.
- **FR-05 – Quản lý thực đơn:** Hệ thống phải hỗ trợ Admin quản lý dữ liệu thực đơn và cung cấp thông tin thực đơn phù hợp cho Staff và Customer.
- **FR-06 – Vận hành đơn hàng:** Hệ thống phải cho phép Staff tạo, cập nhật và theo dõi đơn hàng, bao gồm các món thuộc đơn.
- **FR-07 – Theo dõi đơn hàng:** Hệ thống phải cho phép Admin theo dõi thông tin đơn hàng phục vụ quản lý.
- **FR-08 – Thanh toán:** Hệ thống phải cho phép Staff ghi nhận và xử lý thanh toán của đơn hàng; Admin có thể theo dõi thông tin thanh toán.
- **FR-09 – Khuyến mãi:** Hệ thống phải cho phép Admin quản lý chương trình khuyến mãi ở mức đã được phê duyệt và hỗ trợ áp dụng cho đơn đủ điều kiện.
- **FR-10 – Tồn kho:** Hệ thống phải hỗ trợ Admin quản lý thông tin nguyên liệu, nhận biết tồn kho và theo dõi biến động liên quan đến hoạt động quán.
- **FR-11 – Dashboard:** Hệ thống phải cung cấp cho Admin các chỉ số tổng quan về doanh thu, đơn hàng, sản phẩm bán ra và hoạt động cơ bản.
- **FR-12 – Trang công khai:** Hệ thống phải cho phép Customer xem thông tin quán và thực đơn mà không yêu cầu tài khoản trong phiên bản đầu.
- **FR-13 – Trải nghiệm hiển thị:** Các trang công khai phải đáp ứng phù hợp trên các kích thước màn hình phổ biến.
- **FR-14 – Tương tác cục bộ:** Hệ thống có thể hỗ trợ các tương tác phù hợp mà không yêu cầu tải lại toàn bộ trang, miễn là không làm thay đổi phạm vi nghiệp vụ được phê duyệt.
- **FR-15 – Customer gửi yêu cầu giao món (extension 27/10/2026):** Khách không cần tài khoản chọn món/topping, để lại tên/phone/địa chỉ và chọn chuyển khoản trước hoặc COD; Staff tiếp nhận tạo Order chính thức bằng actor thật để giao đến địa chỉ. Không tự ghi paid từ lựa chọn khách; chi tiết ghi nhận thu/giao, phí/phạm vi/trạng thái giao và schema còn TBD phải chốt trước implementation; không mở quyền vận hành Staff cho Customer.

## 7. Role and Responsibility Matrix

| Chức năng / Domain | Admin | Staff | Customer |
| --- | --- | --- | --- |
| Xác thực nội bộ | Có | Có | Không bắt buộc trong phiên bản đầu |
| Quản lý User | Quản lý | Không | Không |
| Xem tình trạng Table | Có | Có | TBD trong quy trình đặt bàn |
| Quản lý cấu hình Table | Quản lý | Không | Không |
| Reservation | Quản lý/giám sát và hỗ trợ vận hành như Staff | Hỗ trợ vận hành | Tham gia khi chức năng được triển khai |
| Xem Menu | Có | Có | Có |
| Quản lý Menu | Quản lý | TBD nếu có quyền giới hạn trong tương lai | Không |
| Vận hành Order nội bộ | Theo dõi và thực hiện như Staff | Thực hiện | Không trong phiên bản đầu |
| Gửi yêu cầu món qua website (extension 27/10/2026) | Tiếp nhận theo quyền nội bộ | Tiếp nhận | Guest gửi yêu cầu và chỉ xem dữ liệu được phép; không cần account |
| Payment | Theo dõi và xử lý như Staff | Xử lý | Là bên tham gia thanh toán, không vận hành hệ thống nội bộ |
| Quản lý Promotion | Quản lý | TBD | Không |
| Inventory | Quản lý | Chỉ xem/thao tác nếu được phê duyệt sau – TBD | Không |
| Dashboard | Có | TBD nếu được phê duyệt sau | Không |
| Xem trang công khai | Có | Có | Có |

Các ô TBD không cấp quyền mặc định; chúng chỉ ghi nhận điểm cần được phê duyệt trước khi phân rã thành yêu cầu chi tiết.

Quy tắc truy cập được engineer xác nhận ngày 09/10/2026: Admin có toàn bộ quyền vận hành đã được phê duyệt của Staff cùng quyền quản trị. Khu vực Admin chỉ cho Admin; khu vực Staff cho cả Admin và Staff. Staff không được truy cập khu vực Admin. Quyền truy cập không miễn trừ validation, quy tắc nghiệp vụ hoặc việc ghi nhận đúng người thực hiện; các nghiệp vụ còn TBD vẫn cần phê duyệt riêng.

## 8. In-Scope Features

- Xác thực Admin và Staff.
- Phân quyền dựa trên vai trò.
- Quản lý người dùng và nhân viên nội bộ.
- Quản lý bàn của quán.
- Quản lý đặt bàn.
- Quản lý thực đơn, danh mục và sản phẩm.
- Biến thể hoặc kích cỡ sản phẩm.
- Topping dùng chung.
- Quản lý đơn hàng.
- Ghi nhận thanh toán.
- Quản lý khuyến mãi.
- Quản lý nguyên liệu và tồn kho.
- Dashboard và báo cáo tổng quan.
- Website công khai của quán.
- Thực đơn công khai.
- Customer gửi yêu cầu giao món đến địa chỉ, không cần tài khoản; POS tại quán thu tiền trước chuẩn bị theo extension `CAFE-CUSTOMER-POS-WORKFLOW-002`; các quyết định chi tiết còn TBD không được suy đoán.
- Giao diện công khai đáp ứng nhiều kích thước màn hình.
- Tương tác không tải lại toàn trang tại các vị trí phù hợp.

## 9. Out-of-Scope Features

Các nội dung sau nằm ngoài phạm vi, trừ khi được bổ sung bằng một yêu cầu được phê duyệt trong tương lai:

- Quản lý nhiều chi nhánh.
- Hệ thống nhân sự và tiền lương đầy đủ.
- Chấm công nhân viên.
- Quy trình mua hàng từ nhà cung cấp.
- Hệ thống kế toán đầy đủ.
- Tích hợp đối tác giao vận, định tuyến/điều phối tài xế và vận hành giao nhận nâng cao. Yêu cầu giao món web đến địa chỉ khách là ngoại lệ được user duyệt ngày 27/10/2026; không tự mở rộng sang các tính năng giao vận nêu trên.
- Tích hợp sàn thương mại hoặc marketplace.
- Cổng thanh toán trực tuyến phức tạp.
- Ứng dụng di động native.
- Ứng dụng SPA tách riêng.
- Chức năng ERP đầy đủ.

## 10. Business Assumptions

- Hệ thống ban đầu phục vụ một địa điểm cà phê.
- Admin và Staff là các vai trò nội bộ có xác thực.
- Customer không bắt buộc có tài khoản trong phiên bản đầu.
- Sản phẩm là một ứng dụng web Laravel.
- MySQL là cơ sở dữ liệu quan hệ mục tiêu theo kế hoạch.
- Yêu cầu nghiệp vụ sẽ được làm rõ dần thông qua các công việc được phê duyệt trong tương lai.

## 11. Requirement Constraints

- Yêu cầu phải mô tả hệ thống cần hỗ trợ điều gì, không quy định cách viết mã hoặc cấu trúc hiện thực.
- Chỉ Admin, Staff và Customer là các vai trò chính thức trong phạm vi hiện tại.
- Chỉ User, Table, Reservation, Menu, Order, Payment, Promotion, Inventory và Dashboard là các domain chính thức trong phạm vi hiện tại.
- Chi tiết chưa được phê duyệt phải được đánh dấu TBD, không được suy đoán.
- Không được biến yêu cầu tổng quan thành thiết kế cơ sở dữ liệu, đặc tả API, thiết kế lớp, kế hoạch migration hoặc kế hoạch hiện thực.
- Customer vẫn là vai trò không bắt buộc xác thực trong phiên bản đầu cho đến khi yêu cầu được thay đổi chính thức.
- Các quyết định kỹ thuật phải tuân theo `architecture.md` và không được dùng để tự mở rộng nghiệp vụ.

## 12. Future Requirement Areas

Các nội dung sau cần công việc yêu cầu và phê duyệt riêng trước khi hiện thực:

- Quyền chi tiết của Staff ngoài các trách nhiệm vận hành đã được nêu.
- Chính sách đặt bàn, hủy đặt bàn, tiền đặt cọc và giới hạn thời gian.
- Cấu hình thuế/phụ phí, làm tròn và phân bổ giảm giá ngoài công thức baseline đã duyệt.
- Giới hạn topping theo từng product; phiên bản 1 dùng danh mục topping chung.
- Voucher engine hoặc stacking promotion ngoài giới hạn một promotion/order.
- Hoàn tiền/điều chỉnh sau bán và tích hợp thanh toán ngoài.
- Mở rộng inventory sang nhà cung cấp, mua hàng hoặc ERP.
- Chỉ số, kỳ thống kê và nội dung báo cáo cụ thể.
- Luồng Customer đặt bàn và thông tin được phép xem về tình trạng bàn.

Việc liệt kê tại đây không đưa các hành vi chưa được phê duyệt vào phạm vi hiện tại.

## 13. Requirement Change Rule

Mọi tính năng nghiệp vụ mới hoặc thay đổi hành vi có ý nghĩa phải được phê duyệt và bổ sung vào `requirements.md` trước khi tạo công việc hiện thực.

Engineer và AI Agent không được âm thầm mở rộng phạm vi nghiệp vụ. Nếu một yêu cầu công việc mâu thuẫn với `requirements.md`, người thực hiện phải:

1. Báo cáo rõ mâu thuẫn.
2. Yêu cầu hoặc ghi nhận việc phê duyệt thay đổi yêu cầu.
3. Cập nhật yêu cầu trước khi hiện thực nếu thay đổi đó làm thay đổi phạm vi hoặc hành vi đã được duyệt.

## 14. Customer/POS completion approvals — 27/10/2026

Theo task `CAFE-CUSTOMER-POS-COMPLETE-001` và các câu trả lời trực tiếp trong chat:

- Web: guest gửi yêu cầu giao món, tên/điện thoại/địa chỉ; không tài khoản và không ghi đè Customer master. Staff/Admin active tiếp nhận mới tạo một Order bằng actor thật.
- User chọn **ánh xạ giao web vào takeaway**; request liên kết giữ địa chỉ và ý nghĩa giao hàng, không thêm order_type delivery. Không được coi mọi takeaway là giao tận nơi.
- Phí giao cố định **20.000 VND mỗi yêu cầu web**; lưu snapshot `delivery_fee` riêng. Tổng Order = subtotal − discount + tax + delivery_fee; POS phí giao 0. Không đưa phí giao vào tax.
- Chốt giá/phí khi khách gửi; nếu catalog/cấu hình/giá thay đổi trước tiếp nhận, không tự đổi đơn, yêu cầu khách kiểm tra và gửi lại.
- Order từ yêu cầu web giữ nguyên cấu hình đã chốt: không cho Staff thêm/sửa/xóa món hoặc topping kể cả COD chưa thu tiền.
- Chuyển khoản trước: tiếp nhận → xác minh thực nhận đủ → paid → preparing. COD: tiếp nhận → preparing khi chưa paid → giao → khách nhận và trả đủ → paid. COD không phải enum payment_method.
- POS: review tổng server → xác nhận đã nhận đủ tiền → atomic Order/items/topping/paid/preparing. Chốt lại quote trước lưu; lệch giá phải đối soát, không âm thầm thu giá mới.
- Đã paid: chặn thêm/sửa/xóa món/topping và hủy; lifecycle vẫn tiếp tục. Đơn chỉ completed khi đã paid, có ít nhất một món served và mọi món chưa hủy đều served. Trả bàn occupied khi completed và không còn pending dine-in khác; không ghi đè maintenance.
- Giao web: mọi món ready mới bắt đầu giao; Admin/Staff xác nhận khách thực nhận mới chuyển ready → served. COD phải xác nhận thu đủ ở cùng thao tác nhận hàng; lưu thời điểm bắt đầu giao/giao thành công.
- Theo dõi web bằng session và capability khó đoán, hiệu lực 7 ngày; phone/ID/mã hiển thị không là quyền. Chưa duyệt tài khoản/khôi phục quyền qua phone.
- Đã duyệt persistence idempotency: request/items/toppings có snapshot/contact/payment intent, key hash và link Order duy nhất; POS key hash unique + payload hash trên Order. Request lặp cùng owner/key/payload trả kết quả cũ.
- Lỗi ghi nhận sau khi nhận tiền: rollback dữ liệu, giữ cart/key, thông báo kiểm tra trước khi retry; không tự hoàn tiền hoặc thu lần hai.

Retention/xóa thông tin liên hệ, rate limit nghiệp vụ, hủy/sửa public và xử lý giao thất bại còn chờ quyết định; không tự triển khai refund, logistics hoặc xóa lịch sử.

## 15. Finance completion approvals — 18/11/2026

User duyệt hoàn thiện use case D01–D04 và D06 theo phân công mục 7.4. D04 hiện hành gồm xác nhận chuyển khoản web và thu COD. Các luồng tiền mặt, chuyển khoản thủ công, COD và completion tuân thủ §14.

- Finance cung cấp confirmCash, confirmBankTransfer và settlementForOrder cho Sales. Khóa thao tác/hash payload lưu bền vững trên Payment. Retry cùng actor/key/payload trả receipt trước đó, khác nội dung bị từ chối. Tham chiếu ngân hàng mới có ràng buộc duy nhất và được đối chiếu với lịch sử.
- User duyệt migration mới thêm metadata khóa/hash và số tiền nhận/tiền thừa. Chứng từ cũ được giữ nguyên. Cash nhập tiền khách đưa tối thiểu bằng tổng đơn, tiền thừa bằng tiền nhận trừ tiền áp vào đơn. Form cũ bỏ trống tiền khách đưa nghĩa nhận đúng tổng. Chuyển khoản xác minh đúng tổng server. API tích hợp confirmBankTransfer yêu cầu mã tham chiếu, form checkout hiện hữu giữ mã tùy chọn theo §5.6.
- Các operation Finance độc lập ghi nhận paid và trả settlement. Sales điều phối chuẩn bị/completion. Luồng POS/web/COD hiện hữu giữ preconditions đã duyệt.
- D06 dành cho Admin active, lọc từ ngày đến ngày theo Asia/Ho_Chi_Minh, cộng Payment status paid theo paid_at. Hiển thị tổng, tách cash/bank_transfer, số đơn đã thu và chứng từ phân trang. Pending/failed/refunded không vào tổng này. Phát hiện nhiều paid cùng Order phải cảnh báo và ngừng xác nhận tổng. Khoản paid thiếu paid_at được báo riêng. Mặc định ngày Việt Nam hiện tại.
