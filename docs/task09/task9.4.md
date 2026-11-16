```xml
<?xml version="1.0" encoding="UTF-8"?>
<prompt>
    <goal>
        Xây dựng Table Detail &amp; Order Entry
        cho Coffee Shop Management System.

        TASK:
        04 - Table Detail &amp; Order Entry.

        THỜI GIAN:
        60-90 phút.

        MODULE:
        Area &amp; Table POS Management.

        BRANCH:
        feature/area-table-pos-ui.

        MỤC TIÊU CHÍNH:

        1. Khảo sát kết quả TASK 01-03.

        2. Tích hợp hành động chọn bàn
           từ Table Floor Plan hiện tại.

        3. Khi người dùng nhấn vào bàn,
           hiển thị Table Detail Panel
           hoặc trang chi tiết phù hợp.

        4. Hiển thị thông tin bàn:
           - Mã hoặc số bàn.
           - Khu vực.
           - Sức chứa.
           - Trạng thái hiện tại.
           - Đơn hàng liên quan nếu có.

        5. Kiểm tra xem bàn đang có
           Order chưa hoàn tất hay không.

        6. Nếu có Order đang phục vụ:
           - Xác định đúng Order.
           - Hiển thị thông tin tóm tắt.
           - Cho phép tiếp tục Order
             nếu người dùng có quyền.
           - Điều hướng đến POS hiện có.

        7. Nếu bàn có thể nhận Order mới:
           - Hiển thị hành động Tạo Order.
           - Kiểm tra điều kiện nghiệp vụ.
           - Gọi Controller/Service/API
             của POS hiện có.
           - Nhận Order ID thực tế.
           - Điều hướng đến Order hợp lệ.

        8. Kiểm soát trường hợp:
           - Bàn trống.
           - Bàn đang phục vụ.
           - Bàn đã đặt trước.
           - Bàn bảo trì.
           - Đơn chờ thanh toán.
           - Không có Order.
           - Nhiều Order chưa hoàn tất.
           - Order đã hoàn thành hoặc hủy.

        9. Ngăn việc tạo Order trùng
           do Double Click hoặc
           thao tác đồng thời.

        10. Tái sử dụng Authentication
            và Authorization hiện tại.

        11. Xây dựng giao diện tương thích
            với Table Floor Plan của TASK 03.

        12. Xử lý Loading, Empty,
            Error và Conflict States.

        13. Kiểm thử:
            - Table Selection.
            - Table Detail.
            - Active Order Lookup.
            - Create Order Entry.
            - Continue Order.
            - Authorization.
            - Concurrency.
            - Navigation.

        14. Không triển khai lại
            nghiệp vụ Order Management.

        15. Không triển khai Product Selection,
            Checkout hoặc Payment trong TASK này.

        KẾT QUẢ MONG MUỐN:

        Nhân viên có quyền nhấn vào bàn
        và xem được thông tin chi tiết.

        Hệ thống nhận diện đúng Order
        đang phục vụ dựa trên backend.

        Bàn đủ điều kiện có thể tạo Order
        thông qua POS Service đã tồn tại.

        Bàn có Order hợp lệ có thể tiếp tục
        làm việc với Order đó.

        Không tạo Order trùng.

        Không thay đổi trạng thái bàn
        chỉ vì người dùng mở chi tiết.

        Không chỉnh sửa Database Schema.

        Không ảnh hưởng module khác.
    </goal>

    <context>
        PROJECT:
        Coffee Shop Management System.

        TECHNOLOGY:
        - PHP Laravel.
        - MySQL.
        - Laravel MVC.
        - Eloquent ORM.
        - Blade Template.
        - JavaScript.
        - Authentication/Authorization hiện có.

        OWNER:
        Thành Toàn.

        MODULE:
        Area &amp; Table POS Management.

        BRANCH:
        feature/area-table-pos-ui.

        TEAM OWNERSHIP:

        THÀNH TOÀN:
        - Area Management.
        - Cafe Table Management.
        - Reservation Management.
        - Dashboard.
        - Table Floor Plan UI.
        - Table Detail UI.
        - POS Frontend Integration.

        ĐẠT:
        - POS.
        - Order Management.
        - Order Items.
        - Payment Management.
        - Receipt Backend.

        NINH:
        - Catalog.
        - Promotion.

        HỢP:
        - Authentication.
        - Authorization.
        - Inventory.

        BUSINESS REQUIREMENTS:

        Xây dựng hệ thống quản lý bàn
        với trải nghiệm POS chuyên nghiệp.

        User Workflow:

        1. Chọn khu vực.
        2. Xem sơ đồ bàn.
        3. Nhấn vào bàn.
        4. Xem chi tiết bàn.
        5. Kiểm tra Order hiện tại.
        6. Tạo hoặc tiếp tục Order.
        7. Chuyển sang giao diện gọi món.

        UI lấy cảm hứng từ trải nghiệm
        của hệ thống Sapo FnB,
        nhưng phải có thiết kế riêng.

        PREVIOUS TASKS:

        TASK 01:
        Architecture &amp; Integration Planning.

        TASK 02:
        Modern Area Management UI.

        TASK 03:
        Table Floor Plan &amp; Status Cards.

        CURRENT TASK:

        TASK 04:
        Table Detail &amp; Order Entry.

        NEXT TASKS:

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

        Các bảng dự kiến:
        - areas.
        - cafe_tables.
        - orders.
        - order_items.
        - reservations.

        Tên bảng chỉ mang tính tham khảo.

        Phải xác minh từ Migration/Model.

        TABLE STATUS DỰ KIẾN:

        - available.
        - occupied.
        - reserved.
        - maintenance.

        Chỉ sử dụng trạng thái thực tế.

        ORDER STATUS:

        Không giả định giá trị enum.

        AI Agent phải xác minh trạng thái
        Order được xem là đang phục vụ
        từ source code và tài liệu TASK 01.

        ACTIVE ORDER:

        Là Order được nghiệp vụ hiện tại
        xác định là chưa kết thúc
        và đang liên kết hợp lệ với bàn.

        Không mặc định mọi Order chưa thanh toán
        đều là Active Order.

        Không tự định nghĩa một bàn
        chỉ có tối đa một Active Order
        nếu hệ thống chưa quy định.

        ORDER SOURCE OF TRUTH:

        Phải sử dụng Controller, Service
        hoặc API đã được Đạt triển khai.

        Nếu tính năng Create/Open Order
        chưa tồn tại, báo cáo MISSING/BLOCKED.

        Không tự tạo Order Model hoặc
        sửa Order Service thay thế.

        ORDER ENTRY:

        Trong TASK 04, Order Entry nghĩa là
        xác định và mở đúng luồng POS
        cho bàn đã chọn.

        Giao diện chọn món, thay đổi số lượng
        và lưu Order Items thuộc TASK 05.

        SOURCE CODE thực tế là căn cứ cuối cùng.
    </context>

    <scope>
        PHẠM VI ĐƯỢC PHÉP:

        1. SOURCE CODE REVIEW:

           - Table Floor Plan.
           - Area Model.
           - CafeTable Model.
           - Table Controller.
           - Order Model.
           - Order Routes.
           - Order Services.
           - Reservation Rules.
           - Auth Middleware.
           - Existing Tests.
           - TASK 01 Integration Contract.

        2. TABLE DETAIL:

           - Selected Table.
           - Table Code.
           - Area Name.
           - Capacity.
           - Current Status.
           - Active Order Summary.
           - Available Actions.

        3. ACTIVE ORDER LOOKUP:

           - Xác định Order liên kết Table.
           - Xác định Order Status hợp lệ.
           - Chọn đúng Order theo policy.
           - Kiểm tra nhiều Active Orders.
           - Kiểm tra Order không tồn tại.

        4. CREATE ORDER ENTRY:

           - Hiển thị hành động tạo Order.
           - Gọi POS Backend hiện có.
           - Validate Table ID.
           - Validate Authorization.
           - Nhận Order ID thực tế.
           - Điều hướng đến Order/POS.

        5. CONTINUE ORDER ENTRY:

           - Mở đúng Order đã tồn tại.
           - Tái sử dụng Order Route.
           - Không tạo Order mới.
           - Không thay đổi Order Items.

        6. UI INTEGRATION:

           - Click Table Card.
           - Table Detail Panel.
           - Loading State.
           - Error State.
           - Conflict State.
           - Responsive.
           - Keyboard Accessibility.

        7. TESTING:

           - Table Detail Tests.
           - Order Lookup Tests.
           - Create/Open Tests.
           - Authorization Tests.
           - Conflict Tests.
           - Regression Tests.

        FILE DỰ KIẾN:

        - app/Models/Area.php

        - app/Models/CafeTable.php

        - app/Http/Controllers/Admin/
          TableFloorPlanController.php

        - app/Http/Controllers/Admin/
          TableDetailController.php

        - app/Services/
          TableOrderIntegrationService.php

        - resources/views/admin/tables/
          floor-plan.blade.php

        - resources/views/admin/tables/
          detail.blade.php

        - resources/views/admin/tables/
          components/table-detail-panel.blade.php

        - resources/js/admin/
          table-detail.js

        - routes/web.php

        - tests/Feature/
          TableDetailOrderEntryTest.php

        Tên file chỉ là đề xuất.

        Không tạo file trùng.

        Không tạo Service trung gian
        nếu việc gọi Service/Route hiện có
        đã đáp ứng yêu cầu.

        KHÔNG TRIỂN KHAI:

        - Product Catalog UI.
        - Order Items CRUD mới.
        - Discount Logic.
        - Tax Calculation mới.
        - Checkout.
        - Payment Processing.
        - Receipt Printing.
        - Table Release Logic.
        - Table Status Sync mới.
        - Reservation Confirmation mới.
        - Database Migration.
        - Schema Modification.
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
           - TASK 01-03.
           - CafeTable Model.
           - Area Model.
           - Order Model.
           - Order Relationships.
           - Order Routes.
           - Order Services.
           - Reservation Rules.
           - Existing POS UI.
           - Auth Middleware/Policies.
           - Existing Tests.

        3. TRƯỚC KHI CODE:

           AI Agent phải báo cáo:
           - Table Data Contract.
           - Table Detail Route hiện có.
           - Order-Table Relationship.
           - Active Order Definition.
           - Create Order Contract.
           - Continue Order Contract.
           - Authorization Rules.
           - Existing/Partial/Missing APIs.
           - Rủi ro tạo Order trùng.
           - File dự kiến thay đổi.
           - Mini Plan.

        4. Không tự tạo Migration.

        5. Không sửa Database Schema.

        6. Không tự cài package mới.

        7. Không chỉnh sửa Order Service
           hoặc Payment Service của Đạt.

        8. Không chỉnh sửa Catalog của Ninh.

        9. Không sửa Authentication
           hoặc Authorization của Hợp.

        10. Không chỉnh sửa Reservation
            Business Logic để cho phép
            nhận Order trái quy tắc.

        11. TABLE SELECTION:

            - Sử dụng Table ID thực tế.
            - Không dùng mã bàn làm ID
              khi mã có thể trùng giữa Area.
            - Không thay đổi Table Status
              khi nhấn chọn bàn.
            - Không tạo Order từ sự kiện
              chỉ dùng để mở Table Detail.
            - Không tạo request ghi dữ liệu
              khi chỉ xem chi tiết.

        12. TABLE DETAIL:

            - Truy xuất bàn từ database.
            - Kiểm tra bàn tồn tại.
            - Kiểm tra quyền xem bàn.
            - Hiển thị đúng Area.
            - Hiển thị đúng Capacity.
            - Hiển thị Status từ nguồn hợp lệ.
            - Không hiển thị dữ liệu giả.
            - Không mở đơn thuộc bàn khác.

        13. ACTIVE ORDER LOOKUP:

            - Không tự giả định Order Status.
            - Chỉ chọn Order theo policy
              đã xác nhận.
            - Không dùng latest() đơn thuần
              để kết luận Order đang phục vụ.
            - Kiểm tra liên kết Order-Table.
            - Kiểm tra quyền xem Order.
            - Không hiển thị thông tin Order
              trái quyền truy cập.

        14. MULTIPLE ACTIVE ORDERS:

            - Nếu nghiệp vụ cho phép nhiều
              Active Orders trên một bàn,
              hiển thị lựa chọn phù hợp
              hoặc dùng cơ chế có sẵn.
            - Nếu nghiệp vụ chỉ cho phép một,
              phải kiểm tra ràng buộc thực tế.
            - Nếu phát hiện nhiều Order trái policy,
              hiển thị Conflict State.
            - Không tự chọn một Order bất kỳ.
            - Không tự xóa Order dư.

        15. CREATE ORDER:

            - Chỉ gọi Backend được Đạt cung cấp.
            - Chỉ tạo khi Table đủ điều kiện.
            - Chỉ tạo khi người dùng có quyền.
            - Không tự insert vào bảng orders
              từ Controller UI.
            - Không bỏ qua Validation nghiệp vụ.
            - Không tự gán tổng tiền.
            - Không tự cập nhật Payment Status.
            - Không tạo Order bằng GET request.

        16. CREATE ORDER RESPONSE:

            - Chỉ điều hướng khi Backend
              xác nhận tạo Order thành công.
            - Order ID phải có thật.
            - Order phải gắn với Table
              theo quan hệ đã duyệt.
            - Không báo thành công
              khi Backend trả lỗi.
            - Không điều hướng đến Order
              không thuộc phạm vi được phép.

        17. CONTINUE ORDER:

            - Phải mở đúng Order hiện tại.
            - Không tạo Order mới.
            - Không tự sao chép Order.
            - Không sửa Order Items
              trước khi người dùng thao tác.
            - Nếu Order đã hoàn tất
              trong lúc giao diện mở,
              phải xử lý trạng thái lỗi thời.

        18. TABLE ELIGIBILITY:

            Phải xác minh nghiệp vụ:

            - Bàn Available có được tạo Order?
            - Bàn Occupied mở Order nào?
            - Bàn Reserved có được nhận khách?
            - Bàn Maintenance có bị chặn?
            - Bàn chờ thanh toán
              có được thêm Order mới?

            Không tự phê duyệt các trường hợp
            chưa được nhóm thống nhất.

            Nếu thiếu chính sách:
            Báo cáo BLOCKED cho thao tác tương ứng.

        19. RESERVATION INTEGRATION:

            - Kiểm tra Reservation hiện tại.
            - Không tự nhận bàn đã đặt.
            - Không tự hủy Reservation.
            - Không tự chuyển Reserved
              sang Occupied khi chỉ chọn bàn.
            - Không bỏ qua Reservation Conflict.

        20. TABLE STATUS:

            - Chỉ đọc trạng thái khi mở Detail.
            - Không tự đổi available/occupied.
            - Nếu Create Order thành công,
              trạng thái phải được xác định
              theo nghiệp vụ backend hiện có.
            - TASK 07 xử lý đồng bộ trạng thái.
            - Không tự sửa Table Status
              để làm giao diện khớp Order.

        21. CONCURRENCY:

            - Hai nhân viên có thể chọn
              cùng một Table.
            - Hai request có thể cùng tạo Order.
            - Double Click có thể tạo hai request.
            - Dữ liệu Table/Order có thể lỗi thời.

            Frontend cần:
            - Disabled/Loading State.
            - Chặn submit lặp khi đang xử lý.
            - Không tự gửi lại request POST
              một cách thiếu kiểm soát.

            Backend phải chịu trách nhiệm
            bảo đảm tính toàn vẹn nghiệp vụ.

            Nếu Backend chưa hỗ trợ chống
            Duplicate Order/Idempotency:
            - Không khẳng định đã an toàn.
            - Ghi blocker cần gửi cho Đạt.
            - Không tự sửa Order Service.

        22. STALE STATE:

            - Khi mở Detail, đọc trạng thái
              mới nhất có thể theo thiết kế.
            - Khi thực hiện Create/Continue,
              Backend phải kiểm tra điều kiện.
            - Nếu Table đã thay đổi trạng thái,
              không ép tạo Order.
            - Hiển thị thông báo Conflict.
            - Cho phép Refresh dữ liệu.

        23. AUTHORIZATION:

            - Kiểm tra quyền xem Table Detail.
            - Kiểm tra quyền xem Order.
            - Kiểm tra quyền tạo Order.
            - Kiểm tra quyền tiếp tục Order.
            - Không chỉ ẩn Button ở UI.
            - Không mở quyền mới.
            - Không sửa Policy/Middleware.

        24. SECURITY:

            - Không cho xem Order bàn khác
              bằng cách sửa Table ID trên URL.
            - Không tin vào Table ID từ client.
            - Xác minh Order-Table Relationship.
            - Không nhận Order ID tùy ý
              mà không kiểm tra quyền.
            - CSRF Protection với request ghi.
            - Escape dữ liệu trong Blade.
            - Không hiển thị SQL Error thô.

        25. HTTP METHODS:

            - GET chỉ dùng đọc Table Detail.
            - POST dùng cho Create Order
              nếu Contract quy định.
            - Continue Order có thể là GET
              nếu chỉ điều hướng đọc dữ liệu.
            - Không dùng GET để tạo Order.
            - Tuân thủ Route hiện có.

        26. UI DESIGN:

            - Giao diện hiện đại.
            - Thống nhất Floor Plan TASK 03.
            - Table Detail dễ đọc.
            - Table Code nổi bật.
            - Area/Capacity rõ ràng.
            - Status có Icon và Label.
            - Order Summary ngắn gọn.
            - Primary Action rõ ràng.
            - Không hiển thị thao tác giả.

        27. TABLE DETAIL PANEL:

            Gợi ý hiển thị:

            HEADER:
            - Table Code.
            - Area Name.
            - Current Status.

            BODY:
            - Capacity.
            - Current Order Info.
            - Order Status.
            - Order Summary nếu có.

            FOOTER:
            - Tạo Order nếu đủ điều kiện.
            - Tiếp tục Order nếu hợp lệ.
            - Đóng Panel.
            - Refresh nếu cần.

            Không hiển thị Checkout
            chưa được triển khai.

        28. RESPONSIVE:

            Desktop:
            - Detail Panel bên cạnh sơ đồ
              nếu layout phù hợp.

            Tablet:
            - Drawer hoặc Detail Page.

            Mobile:
            - Full-screen Detail/Drawer
              dễ thao tác.

            - Không tràn ngang.
            - Button dễ chạm.
            - Không che nội dung chính.
            - Có cách đóng và quay lại.

        29. ACCESSIBILITY:

            - Button có nhãn.
            - Keyboard Focus rõ ràng.
            - Có aria-label khi cần.
            - Panel có tiêu đề.
            - Có thể đóng bằng bàn phím
              nếu dùng Dialog/Drawer.
            - Không chỉ dùng màu sắc
              để thể hiện trạng thái.

        30. LOADING/ERROR STATES:

            - Loading Table Detail.
            - Table Not Found.
            - Unauthorized.
            - No Active Order.
            - Active Order Found.
            - Multiple Active Orders.
            - Backend Unavailable.
            - Conflict.
            - Create Order Failed.
            - Continue Order Failed.

            Không sử dụng dữ liệu giả
            để lấp trạng thái lỗi.

        31. PERFORMANCE:

            - Tránh N+1 Query.
            - Không tải toàn bộ Order History
              nếu chỉ cần Active Order.
            - Không tải Order Items quá mức
              cho Detail Summary.
            - Không query Payment không cần.
            - Tận dụng Service/Query hiện có.
            - Không polling liên tục
              khi chưa có yêu cầu.

        32. DATABASE SAFETY:

            - Không chạy migrate:fresh
              trên database dùng chung.
            - Không chạy db:wipe.
            - Không truncate dữ liệu.
            - Không dùng production data
              để thử tạo Order.
            - Tests ghi dữ liệu chỉ sử dụng
              database testing độc lập.
            - Không sửa dữ liệu nghiệp vụ
              bằng thao tác thử thủ công
              ngoài môi trường an toàn.

        33. GIT SAFETY:

            - Không tự động commit.
            - Không tự động push.
            - Không tự động merge.
            - Không ghi đè thay đổi
              chưa commit của thành viên khác.
            - Không chuyển branch
              nếu working tree chưa an toàn.

        34. TEST INTEGRITY:

            - Không sửa test để che lỗi.
            - Không báo PASS khi chưa chạy.
            - Ghi FAIL khi kiểm thử thất bại.
            - Ghi BLOCKED khi thiếu API.
            - Ghi NOT RUN khi chưa kiểm thử.
            - Ghi NOT APPLICABLE nếu không áp dụng.

        35. SCOPE BOUNDARY:

            - Không tạo Product Selection UI.
            - Không tạo Order Items Editor.
            - Không xây dựng Payment.
            - Không xây dựng Receipt.
            - Không tự tạo Table Status Sync.
            - Không sửa Order Business Logic.
            - Không mở rộng ngoài TASK 04.
    </hardRules>

    <tasks>
        <taskUnit id="01">
            <name>
                Khảo sát Table-Order Integration
                và lập Mini Plan
            </name>

            <estimatedTime>
                10-15 phút.
            </estimatedTime>

            <steps>
                - Kiểm tra Branch/git status.
                - Đọc TASK 01 Architecture.
                - Đọc TASK 02 Area UI.
                - Đọc TASK 03 Floor Plan.
                - Kiểm tra CafeTable Model.
                - Kiểm tra Order Model.
                - Kiểm tra Order-Table Relationship.
                - Kiểm tra Order Status.
                - Kiểm tra POS Controller/Service.
                - Kiểm tra POS Routes.
                - Kiểm tra Authorization.
                - Kiểm tra Reservation Rules.

                - Xác nhận:
                  + Table Detail Contract.
                  + Active Order Contract.
                  + Create Order Contract.
                  + Continue Order Contract.
                  + Table Eligibility Rules.
                  + Concurrency Protection.

                - Báo cáo:
                  EXISTING/PARTIAL/MISSING/BLOCKED.

                - Liệt kê file dự kiến thay đổi.

                - Trình bày Mini Plan
                  trước khi code.
            </steps>
        </taskUnit>

        <taskUnit id="02">
            <name>
                Xây dựng Table Detail
                và Active Order Lookup
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. TABLE DETAIL BACKEND:

                - Tạo hoặc tái sử dụng
                  Read Controller phù hợp.

                - Truy xuất Table theo ID.

                - Kiểm tra Table tồn tại.

                - Kiểm tra Authorization.

                - Lấy Area, Capacity, Status.

                - Không thay đổi Database.

                B. ORDER LOOKUP:

                - Tái sử dụng Service/Query
                  xác định Active Order.

                - Kiểm tra Order-Table Relationship.

                - Xác định Order Status.

                - Không tự tạo Order.

                - Không dùng latest() thay
                  cho quy tắc Active Order.

                - Xử lý trường hợp:
                  + Không có Active Order.
                  + Có một Active Order.
                  + Nhiều Active Orders hợp lệ.
                  + Dữ liệu mâu thuẫn.
                  + Không đủ quyền xem Order.

                C. DATA CONTRACT:

                Trả về dữ liệu tối thiểu:

                - Table ID.
                - Table Code.
                - Area Name.
                - Capacity.
                - Table Status.
                - Active Order ID nếu có.
                - Active Order Status nếu có.
                - Allowed Actions.

                Các trường thực tế phải
                được xác minh từ schema.

                D. TESTS:

                - Table tồn tại.
                - Table không tồn tại.
                - Table thuộc Area đúng.
                - No Active Order.
                - Active Order Found.
                - Multiple Active Orders.
                - Unauthorized Table/Order.
                - Không có dữ liệu bị sửa.
            </steps>
        </taskUnit>

        <taskUnit id="03">
            <name>
                Tích hợp Create/Continue Order
                và xây dựng Detail UI
            </name>

            <estimatedTime>
                25-30 phút.
            </estimatedTime>

            <steps>
                A. CREATE ORDER INTEGRATION:

                - Chỉ dùng POS Backend hiện có.

                - Xác minh Create Order Contract.

                - Xác minh User Permission.

                - Xác minh Table Eligibility.

                - Gọi Create Order
                  bằng HTTP Method hợp lệ.

                - Không insert Order trực tiếp
                  từ UI Controller.

                - Nhận Order ID từ Backend.

                - Chỉ điều hướng sau Success.

                B. CONTINUE ORDER INTEGRATION:

                - Xác định Active Order hợp lệ.

                - Kiểm tra quyền truy cập.

                - Điều hướng đến đúng Order.

                - Không tạo Order mới.

                - Không tự thay đổi Order Items.

                C. TABLE DETAIL UI:

                - Tích hợp Table Card Click.

                - Hiển thị Detail Panel
                  hoặc View theo Admin Layout.

                - Hiển thị Table Code.

                - Hiển thị Area.

                - Hiển thị Capacity.

                - Hiển thị Table Status.

                - Hiển thị Active Order Summary.

                - Hiển thị Available Actions.

                - Bảo đảm các nút hành động
                  phụ thuộc trạng thái và quyền.

                D. USER FEEDBACK:

                - Loading khi mở Detail.

                - Loading khi Create Order.

                - Disabled Button khi Submit.

                - Success Feedback.

                - Validation Errors.

                - Conflict Feedback.

                - Retry/Refresh phù hợp.

                E. DEPENDENCY HANDLING:

                Nếu POS chưa có Create/Open API:
                - Không tự xây dựng Order Backend.
                - Hoàn thiện phần Detail read-only.
                - Không tạo Button hoạt động giả.
                - Báo cáo yêu cầu tích hợp cho Đạt.
                - Đánh dấu BLOCKED cho Entry.

                F. TESTS:

                - Create Order thành công.
                - Create Order bị từ chối.
                - Continue Order thành công.
                - Không tạo Order trùng.
                - Không mở nhầm Order.
                - Unauthorized Action.
                - Stale Table State.
                - Backend Failure.
            </steps>
        </taskUnit>

        <taskUnit id="04">
            <name>
                Kiểm thử, Regression
                và hoàn thiện tích hợp
            </name>

            <estimatedTime>
                15-20 phút.
            </estimatedTime>

            <steps>
                A. TABLE DETAIL TESTS:

                - Click Table Card.
                - Table Detail đúng bàn.
                - Table Code chính xác.
                - Area chính xác.
                - Capacity chính xác.
                - Status chính xác.
                - Detail không sửa database.

                B. ORDER LOOKUP TESTS:

                - Bàn không có Order.
                - Bàn có Active Order.
                - Order đã hoàn thành.
                - Order đã hủy.
                - Nhiều Active Orders.
                - Order thuộc bàn khác.

                C. CREATE ORDER TESTS:

                - Bàn được phép nhận Order.
                - Bàn không được phép.
                - Reservation Conflict.
                - Maintenance Conflict.
                - Unauthorized Create.
                - Backend Error.
                - Duplicate Submit.
                - Concurrent Create nếu
                  môi trường có hỗ trợ.

                D. CONTINUE ORDER TESTS:

                - Mở đúng Order ID.
                - Không tạo Order mới.
                - Order không còn Active.
                - Không đủ quyền truy cập.
                - Order không thuộc bàn.
                - Dữ liệu đã thay đổi.

                E. SECURITY TESTS:

                - Guest.
                - Unauthorized Staff.
                - Table ID Tampering.
                - Order ID Tampering.
                - CSRF Protection.
                - Backend Authorization.

                F. UI TESTS:

                - Desktop.
                - Tablet.
                - Mobile.
                - Loading.
                - Error.
                - Conflict.
                - Keyboard Navigation.
                - Panel Open/Close.

                G. REGRESSION:

                - TASK 02 Area UI.
                - TASK 03 Table Floor Plan.
                - Area CRUD.
                - Cafe Table CRUD.
                - Reservation.
                - POS Routes.
                - Authentication/Authorization.

                H. FINAL CHECKS:

                - Chạy Feature Tests.
                - Kiểm tra Artisan route:list.
                - Kiểm tra PHP Syntax.
                - Kiểm tra Blade Rendering.
                - Kiểm tra JS Build nếu có.
                - Kiểm tra git diff/git status.
                - Kiểm tra file ngoài Scope.

                - Xuất Final Report.
                - Không commit/push/merge.
            </steps>
        </taskUnit>
    </tasks>

    <acceptanceCriteria>
        1. Đã khảo sát TASK 01-03.

        2. Đã xác minh Table/Order Models.

        3. Đã xác minh Order-Table Relationship.

        4. Có Table Detail UI.

        5. Table Detail đọc đúng dữ liệu MySQL.

        6. Hiển thị mã bàn chính xác.

        7. Hiển thị khu vực chính xác.

        8. Hiển thị sức chứa nếu có.

        9. Hiển thị trạng thái bàn đúng nguồn.

        10. Nhấn bàn không tự tạo Order.

        11. Nhấn bàn không tự đổi Table Status.

        12. Active Order Lookup đúng policy.

        13. Không mở Order của bàn khác.

        14. Xử lý No Active Order.

        15. Xử lý Multiple Active Orders.

        16. Xử lý Order đã hoàn thành/hủy.

        17. Có hành động Create Order
            khi điều kiện được đáp ứng.

        18. Create Order sử dụng POS Backend
            đã được xác minh.

        19. Không tự insert Order trực tiếp.

        20. Nhận Order ID hợp lệ từ Backend.

        21. Có hành động Continue Order
            khi Active Order phù hợp.

        22. Continue Order không tạo Order mới.

        23. Không tự sửa Order Items.

        24. Không tự sửa Payment Status.

        25. Không tự sửa Table Status
            để đồng bộ Order trong TASK này.

        26. Có kiểm tra Table Eligibility.

        27. Có kiểm tra Reservation Conflict
            nếu nghiệp vụ áp dụng.

        28. Có kiểm tra Maintenance Rules.

        29. Có Authentication.

        30. Có Backend Authorization.

        31. Không có Table/Order IDOR
            trong các trường hợp đã kiểm thử.

        32. Có CSRF Protection
            cho thao tác ghi dữ liệu.

        33. Không dùng GET để tạo Order.

        34. Có xử lý Double Submit ở UI.

        35. Backend có cơ chế an toàn
            trước Duplicate Order,
            hoặc ghi rõ BLOCKED nếu thiếu.

        36. Có xử lý Stale State/Conflict.

        37. Có Loading/Error States.

        38. Responsive Desktop.

        39. Responsive Tablet.

        40. Responsive Mobile.

        41. Có Accessibility cơ bản.

        42. Không có N+1 Query nghiêm trọng.

        43. Không sửa Order/Payment Logic.

        44. Không sửa Authentication/Authorization.

        45. Không sửa Reservation Logic.

        46. Không tạo Migration.

        47. Không thay đổi Database Schema.

        48. Không làm hỏng TASK 02-03.

        49. Không tự động commit/push/merge.

        50. Tests sử dụng môi trường an toàn.

        51. Không báo PASS khi chưa chạy test.

        52. Nếu thiếu POS Contract,
            phần Create/Continue Order
            phải báo BLOCKED.

        53. AI Agent phải xuất FINAL REPORT:

            A. MODULE OVERVIEW
            - Chức năng hoàn thành.
            - Chức năng chưa hoàn thành.
            - Blocker còn tồn tại.

            B. SOURCE CODE SURVEY
            - CafeTable Model.
            - Order Model.
            - Relationships.
            - Existing POS Services.
            - Routes.
            - Authorization.

            C. TABLE DETAIL
            - Data Source.
            - Table Identity.
            - Area/Capacity/Status.
            - UI Implementation.
            - Table Selection Workflow.

            D. ACTIVE ORDER LOOKUP
            - Active Order Definition.
            - Query/Service thực tế.
            - Status Rules.
            - Multiple Order Handling.
            - Missing Order Handling.

            E. CREATE ORDER INTEGRATION
            - Backend Route/Service.
            - HTTP Method.
            - Input Contract.
            - Output Contract.
            - Validation.
            - Authorization.
            - Error Handling.
            - Duplicate Order Protection.

            F. CONTINUE ORDER
            - Existing Route.
            - Order ID Validation.
            - Table-Order Relationship.
            - Authorization.
            - Navigation Result.

            G. TABLE ELIGIBILITY
            - Available.
            - Occupied.
            - Reserved.
            - Maintenance.
            - Payment Pending nếu có.
            - Business Rules.
            - Những quy tắc chưa xác nhận.

            H. CONCURRENCY REVIEW
            - Double Click.
            - Duplicate Request.
            - Two Staff Concurrent Access.
            - Stale Order.
            - Stale Table.
            - Backend Limitations.

            I. UI/UX IMPLEMENTATION
            - Table Detail Panel.
            - Active Order Summary.
            - Action Buttons.
            - Loading States.
            - Error/Conflict States.
            - Responsive.
            - Accessibility.

            J. SECURITY
            - Authentication.
            - Authorization.
            - CSRF.
            - Table ID Validation.
            - Order ID Validation.
            - IDOR Prevention.
            - Output Escaping.

            K. FILE CHANGES
            - File tạo mới.
            - File chỉnh sửa.
            - Mục đích thay đổi.
            - Xác nhận không sửa ngoài Scope.

            L. ROUTES
            - HTTP Method.
            - Route Name/Path.
            - Controller/Service.
            - Middleware/Policy.

            M. TEST RESULTS
            - Test ID.
            - Scenario.
            - Expected Result.
            - Actual Result.
            - PASS/FAIL/BLOCKED/NOT RUN.
            - Test Commands.
            - Test Environment.
            - Evidence nếu có.

            N. REGRESSION
            - TASK 02.
            - TASK 03.
            - Area CRUD.
            - Cafe Table CRUD.
            - Reservation.
            - POS Integration.
            - Authentication/Authorization.

            O. NEXT TASK HANDOFF
            - Selected Table Contract.
            - Active Order Contract.
            - POS Entry Route.
            - Order ID Source.
            - Product Selection Dependency.
            - Required APIs for TASK 05.
            - Các blocker cần gửi Đạt/Ninh.

            P. FINAL STATUS
            - READY FOR REVIEW
              hoặc NOT READY FOR REVIEW.
            - Blocker còn lại.
            - Công việc cần xác nhận.

        54. Chỉ đánh dấu TASK 04 hoàn thành
            khi Table Detail và các luồng
            Create/Continue Order được kiểm thử
            theo API và Business Rules thật.

        55. Nếu POS Backend chưa có
            API/Service phù hợp,
            không được tạo Order giả.

            Phần Detail read-only có thể
            hoàn thành riêng nhưng toàn bộ
            TASK 04 phải ghi rõ blocker.

        56. Không tuyên bố đã hoàn thiện
            Order-at-Table, Checkout, Payment
            hoặc Receipt trong TASK 04.
    </acceptanceCriteria>
</prompt>
```