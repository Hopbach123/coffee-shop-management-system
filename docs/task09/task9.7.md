```xml
<?xml version="1.0" encoding="UTF-8"?>
<prompt>
    <goal>
        TRIỂN KHAI TASK 07:
        TABLE STATUS SYNCHRONIZATION
        WITH ORDER &amp; PAYMENT.

        DỰ ÁN:
        Coffee Shop Management System.

        MODULE:
        Area &amp; Table POS Management.

        BRANCH:
        feature/area-table-pos-ui.

        THỜI GIAN:
        60-90 phút.

        CÔNG NGHỆ:
        PHP Laravel, MySQL, Blade, JavaScript
        và công nghệ thực tế đang dùng.

        MỤC TIÊU CHÍNH:

        1. Rà soát kết quả TASK 01-06.

        2. Khảo sát cách quản lý trạng thái
           bàn hiện tại trong CafeTable.

        3. Khảo sát Order Lifecycle:
           - Order được tạo.
           - Order đang phục vụ.
           - Order chờ thanh toán.
           - Order hoàn thành.
           - Order bị hủy.
           - Trạng thái khác nếu có.

        4. Khảo sát Payment Lifecycle:
           - Chưa thanh toán.
           - Đang xử lý.
           - Thanh toán thành công.
           - Thanh toán thất bại.
           - Thanh toán một phần nếu hỗ trợ.

        5. Khảo sát Reservation:
           - Đặt bàn.
           - Xác nhận đặt bàn.
           - Nhận bàn.
           - Hủy đặt bàn.
           - Hết hạn đặt bàn nếu có.

        6. Khảo sát Maintenance:
           - Bàn đang bảo trì.
           - Bàn được đưa vào sử dụng lại.

        7. Xác định nguồn dữ liệu có
           thẩm quyền cho trạng thái bàn.

        8. Thiết kế và triển khai cơ chế
           xác định Display Status
           theo Business Rules đã phê duyệt.

        9. Đồng bộ trạng thái tại:
           - Table Floor Plan.
           - Table Detail Panel.
           - Area Management Statistics.

        10. Cập nhật giao diện khi dữ liệu
            nghiệp vụ thay đổi:
            - Sau Create/Open Order.
            - Sau lưu Order Items.
            - Sau Checkout.
            - Sau xác nhận Payment.
            - Sau thay đổi Reservation.
            - Sau thay đổi Maintenance.

        11. Xử lý xung đột khi:
            - Hai nhân viên cùng thao tác.
            - Table State đã thay đổi.
            - Order đã bị hoàn tất.
            - Payment đang xử lý.
            - Có Reservation xung đột.
            - Table đang Maintenance.

        12. Không giải phóng bàn khi
            Payment chưa được xác nhận
            hoặc còn ràng buộc nghiệp vụ.

        13. Xây dựng cơ chế Refresh
            an toàn, hiệu quả và có kiểm soát.

        14. Không cập nhật database bằng
            thao tác hiển thị đơn thuần.

        15. Không viết lại Order Service,
            Payment Service hoặc
            Reservation Business Logic.

        16. Xây dựng Feature Tests,
            Conflict Tests và Regression Tests.

        KẾT QUẢ MONG MUỐN:

        Table Floor Plan hiển thị đúng
        trạng thái bàn từ Backend.

        Khi Order/Payment/Reservation thay đổi,
        giao diện cập nhật theo dữ liệu
        mới nhất đã được xác minh.

        Trạng thái trên Area Statistics,
        Table Cards và Table Detail
        phải nhất quán.

        Không phát sinh:
        - Trạng thái bàn giả.
        - Bàn trống khi còn Order hợp lệ.
        - Bàn được nhận khách khi bảo trì.
        - Bỏ qua Reservation.
        - Trạng thái Paid giả.
        - Giải phóng bàn khi mới in biên nhận.
        - Ghi đè dữ liệu do nhân viên khác sửa.

        Không tạo Migration mới.

        Không thay đổi Database Schema.

        Không sửa nghiệp vụ module khác.

        Không tự commit/push/merge.
    </goal>

    <context>
        PROJECT:
        Coffee Shop Management System.

        OWNER:
        Thành Toàn.

        BRANCH:
        feature/area-table-pos-ui.

        TECHNOLOGY:
        - Laravel.
        - MySQL.
        - Eloquent ORM.
        - Blade.
        - JavaScript.
        - Authentication/Authorization hiện có.

        TEAM RESPONSIBILITIES:

        THÀNH TOÀN:
        - Area Management.
        - Cafe Table Management.
        - Reservation Management.
        - Table Floor Plan.
        - Table Detail.
        - Table Status Presentation.
        - Frontend Integration.

        ĐẠT:
        - POS.
        - Order.
        - Order Items.
        - Payment.
        - Checkout.
        - Receipt Backend.

        NINH:
        - Catalog.
        - Product Pricing.
        - Promotion.

        HỢP:
        - Authentication.
        - Authorization.
        - Inventory.

        PROJECT ROADMAP:

        TASK 01:
        Architecture &amp; Integration Planning.

        TASK 02:
        Modern Area Management UI.

        TASK 03:
        Table Floor Plan &amp; Status Cards.

        TASK 04:
        Table Detail &amp; Order Entry.

        TASK 05:
        Order-at-Table UI Integration.

        TASK 06:
        Checkout, Receipt &amp; Printing.

        TASK 07:
        Table Status Synchronization.

        TASK 08:
        UI/UX Polish &amp; Responsive.

        TASK 09:
        Testing &amp; Final Integration.

        DATABASE:

        Các bảng có thể liên quan:

        - areas.
        - cafe_tables.
        - orders.
        - order_items.
        - payments.
        - reservations.

        Tên bảng chỉ mang tính tham khảo.

        AI Agent phải xác minh Models,
        Migrations và Relationships thực tế.

        TABLE STATUS DỰ KIẾN:

        - available.
        - occupied.
        - reserved.
        - maintenance.

        Những giá trị này chưa được
        xác minh trong prompt.

        Chỉ sử dụng Status thực tế.

        QUAN TRỌNG:

        PHÂN BIỆT HAI LOẠI TRẠNG THÁI:

        A. PERSISTED TABLE STATUS:

        Là trạng thái được lưu trong database
        hoặc được quản lý bởi Service hiện có.

        B. DERIVED DISPLAY STATUS:

        Là trạng thái phục vụ giao diện,
        được xác định từ dữ liệu Table,
        Order, Payment, Reservation
        và những quy tắc đã phê duyệt.

        Ví dụ về Display Status có thể là:

        - Trống.
        - Có khách.
        - Chờ thanh toán.
        - Đã đặt.
        - Bảo trì.
        - Không xác định.

        Đây là ví dụ, không phải
        bảng trạng thái đã thống nhất.

        Không tự thêm waiting_payment
        vào cafe_tables.status.

        Không tự sửa Table Status
        khi chỉ đọc dữ liệu.

        STATE SOURCE OF TRUTH:

        - Table/Maintenance:
          Cafe Table Backend.

        - Order:
          Order Backend của Đạt.

        - Payment:
          Payment Backend của Đạt.

        - Reservation:
          Reservation Backend hiện có.

        - Permissions:
          Authorization của Hợp.

        Nếu source code có quy tắc khác,
        phải ưu tiên kiến trúc đã được
        nhóm phê duyệt.

        Mọi quy tắc chưa rõ phải đánh dấu:
        NEEDS CONFIRMATION hoặc BLOCKED.
    </context>

    <scope>
        PHẠM VI TRIỂN KHAI:

        A. SOURCE CODE REVIEW:

        - TASK 01-06.
        - CafeTable Model.
        - Order Model.
        - Payment Model.
        - Reservation Model.
        - Table Floor Plan.
        - Area Statistics.
        - Table Detail.
        - POS/Checkout Routes.
        - Business Rules.
        - Auth Middleware.
        - Existing Tests.

        B. STATUS CONTRACT:

        - Table Identity.
        - Persisted Status.
        - Active Order Summary.
        - Payment State.
        - Reservation State.
        - Maintenance State.
        - Derived Display Status.
        - Allowed Actions.
        - Data Freshness.

        C. STATUS RESOLUTION:

        - Xác định trạng thái hợp lệ.
        - Kiểm tra các nguồn dữ liệu.
        - Xử lý trạng thái xung đột.
        - Xử lý Unknown State.
        - Không tự thay đổi dữ liệu nghiệp vụ.

        D. UI SYNCHRONIZATION:

        - Floor Plan Cards.
        - Table Detail Panel.
        - Area Status Counts.
        - Status Labels.
        - Status Icons.
        - Refresh After Action.
        - Safe Data Reload.

        E. CONFLICT HANDLING:

        - Stale Table State.
        - Active Order Conflict.
        - Payment Pending.
        - Concurrent Checkout.
        - Reservation Conflict.
        - Maintenance Conflict.
        - Backend Error.

        F. PERFORMANCE:

        - Aggregate Queries.
        - Eager Loading.
        - Avoid N+1.
        - Controlled Refresh.
        - Query Efficiency.

        G. TESTING:

        - State Resolution Tests.
        - Order Lifecycle Tests.
        - Payment Lifecycle Tests.
        - Reservation Tests.
        - Maintenance Tests.
        - Concurrent Access Tests.
        - Authorization Tests.
        - Regression.

        FILE DỰ KIẾN:

        - app/Services/
          TableStatusReadService.php

        - app/Http/Controllers/Admin/
          TableFloorPlanController.php

        - app/Http/Controllers/Admin/
          TableDetailController.php

        - resources/views/admin/tables/
          floor-plan.blade.php

        - resources/views/admin/tables/
          components/table-card.blade.php

        - resources/views/admin/areas/
          index.blade.php

        - resources/js/admin/
          table-status.js

        - tests/Feature/
          TableStatusSynchronizationTest.php

        - tests/Unit/
          TableDisplayStatusTest.php

        Đây là các file gợi ý.

        Phải kiểm tra cấu trúc thực tế.

        Không tạo Service mới nếu đã có
        Service tương ứng dùng được.

        KHÔNG TRIỂN KHAI:

        - Order Service mới.
        - Payment Service mới.
        - Reservation Logic mới.
        - Maintenance Workflow mới.
        - Table Status Database Enum mới.
        - Database Migration.
        - Database Schema Changes.
        - Payment Gateway.
        - WebSocket Infrastructure mới.
        - Background Queue Infrastructure mới.
        - Refund.
        - Chuyển/Gộp/Tách bàn.
        - Refactor toàn bộ POS.
    </scope>

    <hardRules>
        1. BẮT BUỘC khảo sát source code
           trước khi triển khai.

        2. Kiểm tra:
           - Current Branch.
           - git status.
           - git diff.
           - TASK 01 Architecture.
           - TASK 02 Area UI.
           - TASK 03 Floor Plan.
           - TASK 04 Table Detail.
           - TASK 05 Order UI.
           - TASK 06 Checkout/Receipt.
           - Table Model.
           - Order Model.
           - Payment Model.
           - Reservation Model.
           - Existing Status Services.
           - Routes.
           - Authorization.
           - Existing Tests.

        3. TRƯỚC KHI CODE:

           AI Agent phải báo cáo:
           - Table Status Schema thực tế.
           - Order Status thực tế.
           - Payment Status thực tế.
           - Reservation Status thực tế.
           - Maintenance Policy.
           - Existing State Transition Rules.
           - Existing Status Read Contract.
           - Nguồn dữ liệu có thẩm quyền.
           - Các quy tắc chưa thống nhất.
           - API/Service còn thiếu.
           - File dự kiến thay đổi.
           - Mini Plan.

        4. Nếu State Policy chưa rõ:

           - Không tự suy diễn thứ tự ưu tiên.
           - Không viết logic chuyển trạng thái
             dựa trên phỏng đoán.
           - Hoàn thành các phần đọc dữ liệu
             đã được xác minh.
           - Ghi BLOCKED cho phần phụ thuộc.

        5. Không tạo Migration.

        6. Không thay đổi Database Schema.

        7. Không tự cài Package mới.

        8. Không sửa:
           - Order Service của Đạt.
           - Payment Service của Đạt.
           - Catalog của Ninh.
           - Authentication của Hợp.
           - Authorization của Hợp.
           - Reservation Business Logic.

        9. READ MODEL VS WRITE MODEL:

           - Hiển thị trạng thái là Read Side.
           - Nghiệp vụ thay đổi trạng thái
             thuộc Backend có thẩm quyền.
           - Không UPDATE cafe_tables.status
             chỉ để thay đổi Table Card.
           - Không ghi Order/Payment
             trong Status Read Service.
           - Không dùng JavaScript
             làm nguồn trạng thái chính thức.

        10. STATUS RESOLVER:

            - Ưu tiên tái sử dụng Resolver
              đã tồn tại nếu phù hợp.
            - Chỉ sử dụng dữ liệu đã xác minh.
            - Cùng một Table phải có
              cách xác định trạng thái nhất quán.
            - Không xây dựng quy tắc khác nhau
              ở Floor Plan và Table Detail.
            - Nếu trạng thái không xác định,
              phải có fallback an toàn.

        11. STATE PRIORITY:

            Phải xác minh cách xử lý
            khi nhiều điều kiện đồng thời đúng.

            Ví dụ:
            - Có Active Order và Maintenance.
            - Có Active Order và Reservation.
            - Có Pending Payment và Reservation.
            - Có Completed Order nhưng còn
              Reservation đang hiệu lực.
            - Có nhiều Active Orders.

            Không mặc định Maintenance
            luôn thắng tất cả trạng thái
            nếu Business Rules chưa xác nhận.

            Phải ghi rõ Conflict Resolution
            đã được phê duyệt.

        12. ACTIVE ORDER:

            - Sử dụng định nghĩa từ TASK 01/04.
            - Không chọn Order bằng latest()
              khi thiếu điều kiện trạng thái.
            - Không mặc định một bàn
              chỉ có một Active Order
              nếu Backend không quy định.
            - Không tự hủy Order dư.
            - Không làm mất liên kết Order-Table.
            - Không hiển thị thông tin Order
              trái Authorization.

        13. ORDER CREATED:

            - Chỉ coi Create Order thành công
              khi Backend xác nhận.
            - Không chuyển UI sang Occupied
              khi API đang Pending.
            - Refresh trạng thái sau Success.
            - Nếu Backend trả Conflict,
              cập nhật UI theo dữ liệu mới.

        14. ORDER ITEMS UPDATED:

            - Thêm món không tự tạo
              một trạng thái bàn mới.
            - Chỉ cập nhật thông tin
              liên quan theo Contract.
            - Không tự thay đổi Order Status.
            - Sau khi lưu thành công,
              đồng bộ Order Summary nếu cần.

        15. PAYMENT PENDING:

            - Không xem Pending là Paid.
            - Không giải phóng bàn
              chỉ vì Payment Request đã gửi.
            - Không thay đổi trạng thái Order
              dựa trên thông báo UI.
            - Hiển thị tình trạng phù hợp
              nếu policy đã xác nhận.

        16. PAYMENT SUCCESS:

            - Chỉ dựa vào Payment Backend.
            - Không tin query parameter success.
            - Không tin client callback
              như xác nhận tài chính.
            - Xác minh Order Completion Policy.
            - Kiểm tra ràng buộc khác
              trước khi cho bàn Available.
            - Không tự UPDATE Table Status
              ngoài Service có thẩm quyền.

        17. PAYMENT FAILED:

            - Không chuyển Order thành Paid.
            - Không tự giải phóng bàn.
            - Không tự tạo giao dịch thay thế.
            - Không làm mất Order đang phục vụ.
            - Refresh trạng thái khi phù hợp.

        18. PARTIAL PAYMENT:

            - Chỉ áp dụng khi Backend hỗ trợ.
            - Không tự coi thanh toán một phần
              là hoàn tất.
            - Không tự tính Outstanding Amount.
            - Không tự thay đổi Table Status.
            - Ghi NOT APPLICABLE nếu
              nghiệp vụ không hỗ trợ.

        19. ORDER CANCELLED:

            - Không mặc định Cancel
              đồng nghĩa Table Available.
            - Kiểm tra Order khác.
            - Kiểm tra Reservation.
            - Kiểm tra Maintenance.
            - Kiểm tra Cleanup Policy nếu có.
            - Chỉ cập nhật UI theo dữ liệu
              và quy tắc đã xác minh.

        20. TABLE RELEASE POLICY:

            Phải xác minh đủ các điều kiện:

            - Order đã kết thúc hợp lệ?
            - Payment đã hoàn tất nếu bắt buộc?
            - Có Active Order khác không?
            - Có Reservation còn hiệu lực không?
            - Có Maintenance Lock không?
            - Có bước dọn bàn thủ công không?

            Nếu thiếu bất kỳ quy tắc
            quan trọng nào, báo cáo BLOCKED
            cho cơ chế tự giải phóng bàn.

        21. RESERVATION:

            - Đọc Reservation State thật.
            - Kiểm tra quan hệ Table.
            - Kiểm tra thời điểm hiệu lực
              theo nghiệp vụ có sẵn.
            - Không tự xác nhận đặt bàn.
            - Không tự hủy Reservation.
            - Không tự chuyển Reserved
              sang Occupied khi chỉ xem UI.
            - Không bỏ qua xung đột đặt bàn.

        22. MAINTENANCE:

            - Tái sử dụng Maintenance Status.
            - Không tự tạo trạng thái mới.
            - Không tự mở khóa Maintenance.
            - Không cho phép UI giả định
              bàn đang bảo trì có thể nhận Order.
            - Backend phải kiểm tra điều kiện
              trước khi thực hiện thao tác ghi.

        23. CONSISTENT UI STATUS:

            Floor Plan, Table Detail
            và Area Statistics phải sử dụng
            cùng nguồn dữ liệu/logic đã duyệt.

            Không được có tình huống:
            - Card báo Trống.
            - Detail báo Có khách.
            - Area Summary đếm sai nhóm.

            Phải xác định phạm vi và thời điểm
            dữ liệu được làm mới.

        24. STATUS COUNT:

            - Đếm theo cùng Status Contract.
            - Không đếm trùng Table.
            - Không trộn Persisted Status
              và Derived Status trong cùng
              nhóm thống kê không có giải thích.
            - Unknown State phải xử lý riêng.
            - Tổng đếm phải nhất quán
              với phạm vi dữ liệu được hiển thị.
            - Không hiển thị số 0 giả
              khi nguồn dữ liệu lỗi.

        25. REFRESH STRATEGY:

            Ưu tiên:
            - Refresh sau Action thành công.
            - Refresh khi quay lại Floor Plan.
            - Refresh khi mở Table Detail.
            - Refresh khi người dùng chủ động
              yêu cầu làm mới.

            Nếu dự án đã có Events/Broadcast:
            - Có thể tái sử dụng Contract thật.

            Nếu không:
            - Không tự xây dựng WebSocket.
            - Không tự tạo Queue Infrastructure.
            - Chỉ cân nhắc Polling có giới hạn
              khi đã thống nhất tần suất
              và có nhu cầu thực tế.

        26. REFRESH SAFETY:

            - Không tạo Payment Request
              khi chỉ Refresh.
            - Không tạo Order khi Refresh.
            - Không UPDATE Table khi Refresh.
            - Không để response cũ ghi đè
              UI mới hơn mà không kiểm soát.
            - Không gây request lặp vô hạn.
            - Có trạng thái Loading/Refreshing.
            - Có Timestamp nếu Contract hỗ trợ.

        27. CONCURRENCY:

            Phải phân tích:

            - Hai nhân viên cùng mở một Table.
            - Hai nhân viên cùng tạo Order.
            - Một người Checkout
              khi người khác thêm món.
            - Một người đổi Maintenance
              khi người khác chọn bàn.
            - Reservation thay đổi khi
              Order đang được tạo.

            Frontend:
            - Không tự quyết định tính hợp lệ
              dựa trên state cũ.
            - Hiển thị Conflict.
            - Refresh dữ liệu khi cần.

            Backend:
            - Là nguồn có thẩm quyền.
            - Kiểm tra điều kiện trước ghi.
            - Sử dụng concurrency controls
              đã có trong nghiệp vụ.

            Nếu Backend thiếu kiểm soát,
            tạo yêu cầu phối hợp với Owner.

        28. STALE STATE:

            - Không tiếp tục hiển thị
              trạng thái cũ như chắc chắn đúng
              sau khi phát hiện Conflict.
            - Không ép chuyển trạng thái.
            - Không retry thao tác ghi
              một cách không kiểm soát.
            - Cho phép Refresh an toàn.
            - Nếu nguồn dữ liệu unavailable,
              hiển thị cảnh báo rõ ràng.

        29. TRANSACTION BOUNDARY:

            - Không tự tạo transaction
              thay cho Order/Payment Backend.
            - Không ghi đồng thời nhiều bảng
              từ Controller UI.
            - Các cập nhật nghiệp vụ liên quan
              phải do Service Owner xử lý.
            - Nếu Contract chưa bảo đảm
              atomicity, báo cáo dependency.

        30. AUTHORIZATION:

            - Chỉ người có quyền mới được
              xem trạng thái theo phạm vi.
            - Kiểm tra quyền xem Order Summary.
            - Kiểm tra quyền xem Payment Info.
            - Không lộ dữ liệu bàn/đơn
              ngoài phạm vi được phép.
            - Không sửa Middleware/Policy.
            - Không tự tạo Permission mới.

        31. SECURITY:

            - Validate Table ID.
            - Validate Area ID nếu có.
            - Kiểm tra Order-Table Relationship.
            - Không cho IDOR.
            - Không lộ dữ liệu thanh toán nhạy cảm.
            - Escape dữ liệu Blade.
            - Không hiển thị SQL Error thô.
            - Refresh Endpoint dùng GET
              chỉ khi thực sự read-only.
            - Không dùng GET để thay đổi trạng thái.

        32. PERFORMANCE:

            - Tránh N+1 Queries.
            - Không query riêng từng Order
              cho từng Table nếu có thể tối ưu.
            - Ưu tiên aggregate/eager loading
              phù hợp với schema.
            - Không tải toàn bộ Payment History
              chỉ để xác định Display Status.
            - Không tạo nhiều request
              trùng lặp khi mở Floor Plan.
            - Không tự thêm Cache dùng chung
              gây rò dữ liệu giữa người dùng.

        33. UX DESIGN:

            - Status Icon rõ ràng.
            - Status Label tiếng Việt.
            - Màu sắc nhất quán.
            - Có dấu hiệu Refreshing.
            - Có thông báo Conflict.
            - Có thông báo Offline/Error
              nếu phù hợp.
            - Không chỉ dùng màu sắc
              để biểu diễn trạng thái.
            - Không dùng animation giả
              làm người dùng tưởng
              Backend đã cập nhật thành công.

        34. RESPONSIVE:

            - Desktop Floor Plan.
            - Tablet Floor Plan.
            - Mobile Floor Plan.
            - Table Detail Panel.
            - Không làm vỡ Layout.
            - Không che Status quan trọng.
            - Button Refresh dễ thao tác.

        35. DATABASE SAFETY:

            - Không chạy migrate:fresh
              trên database dùng chung.
            - Không chạy db:wipe.
            - Không truncate dữ liệu.
            - Không thay đổi Production Data.
            - Tests ghi dữ liệu phải dùng
              database testing độc lập.
            - Payment Events phải được Mock/Fake
              hoặc dùng Sandbox phù hợp.

        36. GIT SAFETY:

            - Không tự động commit.
            - Không tự động push.
            - Không tự động merge.
            - Không ghi đè code chưa commit
              của thành viên khác.
            - Không đổi branch khi
              Working Tree chưa an toàn.

        37. TEST INTEGRITY:

            - PASS khi chạy test thành công.
            - FAIL khi kết quả không đúng.
            - BLOCKED khi thiếu Contract.
            - NOT RUN khi chưa thực hiện.
            - NOT APPLICABLE khi chức năng
              không được thiết kế hỗ trợ.
            - Không báo đã test Realtime
              nếu chưa có Realtime Backend.
            - Không giả kết quả Payment Success.

        38. SCOPE BOUNDARY:

            - Không sửa Order Business Logic.
            - Không sửa Payment Business Logic.
            - Không sửa Reservation Business Logic.
            - Không thêm trạng thái database.
            - Không tạo WebSocket mới.
            - Không xây dựng Refund.
            - Không xây dựng Checkout mới.
            - Không triển khai ngoài TASK 07.
    </hardRules>

    <tasks>
        <taskUnit id="01">
            <name>
                Khảo sát Status Contracts
                và lập State Matrix
            </name>

            <estimatedTime>
                10-15 phút.
            </estimatedTime>

            <steps>
                - Kiểm tra Branch/git status.
                - Đọc TASK 01-06.
                - Kiểm tra Table Model.
                - Kiểm tra Order Model.
                - Kiểm tra Payment Model.
                - Kiểm tra Reservation Model.
                - Kiểm tra State Services.
                - Kiểm tra Floor Plan.
                - Kiểm tra Table Detail.
                - Kiểm tra Area Statistics.
                - Kiểm tra Routes/Authorization.

                - Xác định:
                  + Persisted Table Status.
                  + Active Order Definition.
                  + Payment State Contract.
                  + Reservation State Contract.
                  + Maintenance Rules.
                  + Table Release Rules.
                  + Status Display Priority.
                  + Refresh Mechanism.

                - Lập State Transition Matrix.

                - Ghi rõ VERIFIED/PROPOSED/
                  BLOCKED cho từng quy tắc.

                - Trình bày Mini Plan.
            </steps>
        </taskUnit>

        <taskUnit id="02">
            <name>
                Xây dựng Status Read Model
                và Display Status Resolution
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. DATA SOURCE:

                - Tái sử dụng Status Service
                  hiện có nếu phù hợp.

                - Đọc Table Status.

                - Đọc Active Order Summary
                  theo Contract thật.

                - Đọc Payment State
                  cần thiết nếu được phép.

                - Đọc Reservation State.

                - Đọc Maintenance State.

                B. STATUS RESOLUTION:

                - Áp dụng các quy tắc
                  đã được xác minh.

                - Không tự suy diễn
                  Priority chưa được duyệt.

                - Xử lý trạng thái:
                  + Available.
                  + Occupied.
                  + Awaiting Payment nếu có.
                  + Reserved.
                  + Maintenance.
                  + Unknown/Conflict.

                - Không bắt buộc dùng
                  các trạng thái gợi ý này
                  khi schema/policy khác.

                C. RESPONSE CONTRACT:

                Mỗi Table nên cung cấp
                dữ liệu hiển thị phù hợp:

                - Table ID.
                - Area ID.
                - Persisted Status.
                - Display Status.
                - Status Label.
                - Allowed Actions nếu có.
                - Relevant Order Reference
                  khi có quyền.
                - Updated At nếu hỗ trợ.

                D. DATA INTEGRITY:

                - Không UPDATE Table.
                - Không UPDATE Order.
                - Không UPDATE Payment.
                - Không UPDATE Reservation.
                - Không tạo Status Record mới.
                - Không thay đổi Schema.

                E. PERFORMANCE:

                - Tránh N+1.
                - Tối ưu dữ liệu nhiều bàn.
                - Không tải dữ liệu thừa.

                F. TESTS:

                - Status Resolver.
                - Order Active/Closed.
                - Payment Pending/Success.
                - Reservation Active.
                - Maintenance.
                - Unknown/Conflict.
                - No Database Writes.
            </steps>
        </taskUnit>

        <taskUnit id="03">
            <name>
                Đồng bộ Floor Plan,
                Table Detail và Area Statistics
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. FLOOR PLAN:

                - Sử dụng Status Contract chung.
                - Render Status Cards.
                - Render Icon/Label đúng.
                - Refresh trạng thái phù hợp.
                - Không tạo dữ liệu giả.

                B. TABLE DETAIL:

                - Đọc lại Table Status.
                - Đồng bộ Active Order Summary.
                - Hiển thị Conflict nếu có.
                - Không thực hiện Action trái quyền.
                - Không ghi Database khi Refresh.

                C. AREA STATISTICS:

                - Dùng cùng Status Definition.
                - Đếm Table theo khu vực.
                - Không đếm trùng.
                - Xử lý Unknown State.
                - Không trộn nhóm đếm khác nghĩa.

                D. REFRESH:

                - Sau Create/Open Order
                  thành công và có xác nhận.

                - Sau Order Items Save
                  nếu trạng thái có thể thay đổi.

                - Sau Payment Confirmation.

                - Khi quay lại Floor Plan.

                - Khi mở Table Detail.

                - Sau Reservation/Maintenance
                  được xác nhận và có cơ chế
                  làm mới phù hợp.

                E. CONFLICT UI:

                - Hiển thị thông báo
                  dữ liệu đã thay đổi.

                - Cho phép Refresh.

                - Không sử dụng response cũ
                  để ghi đè UI mới hơn.

                - Không retry thao tác
                  thanh toán hoặc tạo Order
                  một cách tự động.

                F. TESTS:

                - Floor Plan Consistency.
                - Table Detail Consistency.
                - Area Count Consistency.
                - Refresh After Actions.
                - Backend Unavailable.
                - Stale UI.
                - Unauthorized Data.
            </steps>
        </taskUnit>

        <taskUnit id="04">
            <name>
                Kiểm thử Conflict, Performance
                và Regression
            </name>

            <estimatedTime>
                15-20 phút.
            </estimatedTime>

            <steps>
                A. ORDER TESTS:

                - No Active Order.
                - One Active Order.
                - Multiple Active Orders.
                - Create Order Success.
                - Create Order Failed.
                - Order Completed.
                - Order Cancelled.

                B. PAYMENT TESTS:

                - Payment Pending.
                - Payment Success.
                - Payment Failed.
                - Partial Payment nếu có.
                - Concurrent Checkout.
                - Payment Result Unknown.

                C. RESERVATION TESTS:

                - Reservation Active.
                - Reservation Cancelled.
                - Reservation Conflict.
                - Reservation với Active Order.

                D. MAINTENANCE TESTS:

                - Table Maintenance.
                - Maintenance với Active Order.
                - Maintenance với Reservation.
                - Reopen Table nếu policy hỗ trợ.

                E. CONCURRENCY TESTS:

                - Two Staff Open Same Table.
                - Order Updated Concurrently.
                - Payment Completed Concurrently.
                - Stale Table State.
                - Refresh Response Out of Order.

                F. PERFORMANCE:

                - Query Count nếu đo được.
                - N+1 Review.
                - Refresh Frequency.
                - Duplicate Query Review.
                - Area Statistics Accuracy.

                G. REGRESSION:

                - TASK 02 Area UI.
                - TASK 03 Floor Plan.
                - TASK 04 Table Detail.
                - TASK 05 Order UI.
                - TASK 06 Checkout.
                - Existing Cafe Table CRUD.
                - Reservation.
                - Authentication/Authorization.

                H. FINAL CHECKS:

                - Chạy Feature Tests.
                - Chạy Unit Tests.
                - Kiểm tra route:list.
                - Kiểm tra PHP Syntax.
                - Kiểm tra Blade/JavaScript.
                - Kiểm tra git status/git diff.
                - Kiểm tra file ngoài Scope.

                - Ghi PASS/FAIL/BLOCKED/
                  NOT RUN/NOT APPLICABLE.

                - Xuất Final Report.
                - Không commit/push/merge.
            </steps>
        </taskUnit>
    </tasks>

    <acceptanceCriteria>
        1. Đã khảo sát TASK 01-06.

        2. Xác minh Table Status Schema.

        3. Xác minh Order Lifecycle.

        4. Xác minh Payment Lifecycle.

        5. Xác minh Reservation Lifecycle.

        6. Xác minh Maintenance Rules.

        7. Có State Transition Matrix.

        8. Có quy tắc xác định Display Status
           đã được kiểm chứng hoặc phê duyệt.

        9. Không tự tạo Status Enum mới.

        10. Không tự thêm Database Column.

        11. Floor Plan dùng Status Contract
            đã được xác minh.

        12. Table Detail dùng cùng
            Status Definition.

        13. Area Statistics có cách đếm
            nhất quán với Display Status.

        14. Không đếm trùng Table.

        15. Không tạo trạng thái giả.

        16. Order Created chỉ được phản ánh
            sau xác nhận Backend.

        17. Payment Pending không bị coi Paid.

        18. Payment Failed không giải phóng bàn.

        19. Payment Success được xác minh
            từ Backend.

        20. Không tự giải phóng bàn
            trái Table Release Policy.

        21. Reservation được kiểm tra.

        22. Maintenance được kiểm tra.

        23. Xử lý Multiple Active Orders
            theo Business Rules.

        24. Có xử lý Unknown/Conflict State.

        25. Có Refresh khi quay lại Floor Plan
            theo phương án được thiết kế.

        26. Có Refresh Table Detail phù hợp.

        27. Không gửi request ghi dữ liệu
            khi chỉ Refresh trạng thái.

        28. Không tự tạo Order trong Refresh.

        29. Không tự tạo Payment trong Refresh.

        30. Không tự UPDATE Table Status
            từ UI Read Operation.

        31. Có xử lý Stale State.

        32. Có xử lý Concurrent Access
            trong phạm vi Contract hiện có.

        33. Không tự sửa Order/Payment Backend.

        34. Có kiểm tra Authentication.

        35. Có kiểm tra Authorization.

        36. Không lộ dữ liệu Order/Payment
            ngoài phạm vi cho phép.

        37. Không có N+1 Query nghiêm trọng.

        38. Không tạo Polling vô hạn.

        39. Có Loading/Error/Conflict States.

        40. Responsive không bị phá vỡ.

        41. Không làm hỏng TASK 02-06.

        42. Không sửa Reservation Logic.

        43. Không sửa Authentication/Authorization.

        44. Không thay đổi Database Schema.

        45. Không tạo Migration.

        46. Tests dùng môi trường độc lập.

        47. Không tự động commit/push/merge.

        48. Không báo PASS khi chưa chạy test.

        49. Nếu thiếu State Policy hoặc API,
            ghi BLOCKED và nêu Owner cần xử lý.

        50. AI Agent phải xuất FINAL REPORT:

            A. MODULE OVERVIEW
            - Chức năng hoàn thành.
            - Chức năng chưa hoàn thành.
            - Blocker còn tồn tại.

            B. STATUS ARCHITECTURE
            - Persisted Table Status.
            - Derived Display Status.
            - Status Source of Truth.
            - Status Mapping.
            - Status Priority.
            - Unknown State Handling.

            C. STATE TRANSITION MATRIX
            - Event.
            - Current State.
            - Conditions.
            - Expected Display State.
            - Backend Owner.
            - Verification Status.

            D. ORDER INTEGRATION
            - Active Order Contract.
            - Create Order Result.
            - Order Completion.
            - Order Cancellation.
            - Multiple Active Orders.
            - Dependencies.

            E. PAYMENT INTEGRATION
            - Payment Status Contract.
            - Pending.
            - Success.
            - Failed.
            - Partial nếu có.
            - Backend Confirmation.
            - Table Release Dependencies.

            F. RESERVATION &amp; MAINTENANCE
            - Reservation State.
            - Reservation Conflict.
            - Maintenance State.
            - Status Priority.
            - Unsupported Scenarios.

            G. STATUS SYNCHRONIZATION
            - Floor Plan.
            - Table Detail.
            - Area Statistics.
            - Refresh Strategy.
            - Consistency Checks.
            - Loading/Error Handling.

            H. CONCURRENCY
            - Two Staff Access.
            - Stale State Handling.
            - Concurrent Order Change.
            - Concurrent Payment.
            - Frontend Conflict Handling.
            - Backend Limitations.

            I. PERFORMANCE
            - Query Count nếu đo được.
            - N+1 Analysis.
            - Data Fetch Strategy.
            - Refresh Frequency.
            - Performance Limitations.

            J. SECURITY
            - Authentication.
            - Authorization.
            - Table/Order Identity.
            - Sensitive Data Protection.
            - Safe Read Endpoints.
            - IDOR Prevention.

            K. UI/UX
            - Status Icons.
            - Status Labels.
            - Floor Plan Updates.
            - Table Detail Updates.
            - Area Summary.
            - Refresh Indicators.
            - Conflict/Error States.

            L. FILE CHANGES
            - File tạo mới.
            - File chỉnh sửa.
            - Mục đích thay đổi.
            - Xác nhận không sửa ngoài Scope.

            M. ROUTES/CONTRACTS
            - HTTP Method.
            - Route Name/Path.
            - Controller/Service.
            - Request/Response.
            - Authorization.
            - Status Source.

            N. TEST RESULTS
            - Test ID.
            - Scenario.
            - Expected Result.
            - Actual Result.
            - PASS/FAIL/BLOCKED/NOT RUN.
            - Test Commands.
            - Test Database.
            - Evidence nếu có.

            O. REGRESSION
            - TASK 02.
            - TASK 03.
            - TASK 04.
            - TASK 05.
            - TASK 06.
            - Cafe Table CRUD.
            - Reservation.
            - Authentication/Authorization.

            P. NEXT TASK HANDOFF
            - Status Data Contract.
            - UI State Components.
            - Refresh Strategy.
            - Responsive Issues.
            - Dependencies cho TASK 08.
            - Test Scenarios cho TASK 09.

            Q. FINAL STATUS
            - READY FOR REVIEW
              hoặc NOT READY FOR REVIEW.
            - Blocker còn lại.
            - Rủi ro nghiệp vụ.
            - Những việc cần xác nhận.

        51. Chỉ đánh dấu TASK 07 hoàn thành
            khi Status Resolution,
            UI Synchronization và Conflict
            Handling hoạt động theo các
            Business Rules đã xác minh.

        52. Nếu chưa có API hoặc Contract
            để xác định trạng thái chính xác,
            không được tự suy diễn
            rồi tuyên bố hoàn thành.

        53. Không tuyên bố đã triển khai
            hệ thống Realtime/WebSocket
            nếu chỉ sử dụng Refresh.

        54. Không tuyên bố toàn bộ
            Area &amp; Table POS hoàn thành
            trước TASK 09.
    </acceptanceCriteria>
</prompt>
```