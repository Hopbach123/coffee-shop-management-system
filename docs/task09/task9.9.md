```xml
<?xml version="1.0" encoding="UTF-8"?>
<prompt>
    <goal>
        TRIỂN KHAI TASK 09:
        TESTING &amp; FINAL INTEGRATION
        FOR AREA &amp; TABLE POS MANAGEMENT.

        DỰ ÁN:
        Coffee Shop Management System.

        MODULE:
        Area &amp; Table POS Management.

        BRANCH:
        feature/area-table-pos-ui.

        THỜI GIAN:
        60-90 phút.

        ĐỘ KHÓ:
        D5 - INTEGRATION TESTING.

        CÔNG NGHỆ:
        PHP Laravel, MySQL, Blade,
        JavaScript và các công cụ
        kiểm thử thực tế của dự án.

        MỤC TIÊU TỔNG QUÁT:

        Kiểm thử, phát hiện lỗi, sửa lỗi
        trong phạm vi được giao và nghiệm thu
        module Area &amp; Table POS Management.

        PHẠM VI KIỂM THỬ:

        1. Area Management.

        2. Table Floor Plan.

        3. Table Detail.

        4. Create/Continue Order.

        5. Order-at-Table UI.

        6. Order Items Persistence.

        7. Checkout &amp; Payment Integration.

        8. Receipt Preview &amp; Printing.

        9. Table Status Synchronization.

        10. Authentication &amp; Authorization.

        11. Data Integrity &amp; Concurrency.

        12. Performance.

        13. Responsive UI/UX.

        14. Regression Testing.

        LUỒNG NGHIỆM THU CHÍNH:

        Staff Login
             |
             v
        Area Management
             |
             v
        Select Area
             |
             v
        Table Floor Plan
             |
             v
        Select Table
             |
             v
        Table Detail
             |
             v
        Check Active Order
             |
             +--> No Active Order:
             |    Create Order qua POS Backend
             |
             +--> Active Order:
                  Continue Existing Order
             |
             v
        Product Selection
             |
             v
        Add Items To Draft
             |
             v
        Save Order Items
             |
             v
        Verify Backend Persistence
             |
             v
        Checkout
             |
             v
        Payment Confirmation
             |
             v
        Receipt Preview/Print
             |
             v
        Refresh Table State
             |
             v
        Verify Final Table Display Status.

        KẾT QUẢ MONG MUỐN:

        - Luồng POS tại bàn hoạt động
          theo Business Rules thực tế.

        - Không tạo Order trùng.

        - Không mở Order của bàn khác
          trái quyền truy cập.

        - Không lưu món sai hoặc trùng
          do lỗi giao diện.

        - Tổng tiền chính thức
          do Backend xác nhận.

        - Không tạo Payment trùng.

        - Không đánh dấu Paid giả.

        - In chứng từ không tự thay đổi
          Order/Payment/Table State.

        - Trạng thái bàn nhất quán giữa
          Floor Plan, Table Detail
          và Area Statistics.

        - Phân quyền được kiểm tra
          tại Backend.

        - Không có lỗi UI nghiêm trọng
          trên các viewport đã kiểm thử.

        - Không có lỗi hiệu năng nghiêm trọng
          được xác nhận trong phạm vi kiểm thử.

        - Có Test Matrix đầy đủ.

        - Có bằng chứng kiểm thử thực tế.

        - Có Bug Fix Summary.

        - Có Regression Report.

        - Có Final Integration Report.

        - Có kết luận:
          READY FOR REVIEW
          hoặc NOT READY FOR REVIEW.

        TASK 09 LÀ NHIỆM VỤ NGHIỆM THU.

        Không xây dựng lại dự án.

        Không thêm nghiệp vụ mới
        ngoài các chức năng đã thống nhất.

        Không thay đổi Database Schema.

        Không tự động commit/push/merge.
    </goal>

    <context>
        PROJECT:
        Coffee Shop Management System.

        OWNER:
        Thành Toàn.

        BRANCH:
        feature/area-table-pos-ui.

        TECHNOLOGY:
        - PHP Laravel.
        - MySQL.
        - Eloquent ORM.
        - Blade Template.
        - JavaScript.
        - Authentication/Authorization hiện có.
        - PHPUnit/Pest nếu dự án đang sử dụng.
        - Browser Testing nếu có công cụ.

        TEAM RESPONSIBILITIES:

        THÀNH TOÀN:
        - Area Management.
        - Cafe Table Management.
        - Reservation Management.
        - Table Floor Plan.
        - Table Detail.
        - Order-at-Table UI.
        - Checkout UI Integration.
        - Table Status Presentation.
        - UI/UX &amp; Responsive.

        ĐẠT:
        - POS Backend.
        - Order Management.
        - Order Items.
        - Payment.
        - Checkout.
        - Receipt Backend.

        NINH:
        - Catalog.
        - Product Categories.
        - Product Pricing.
        - Promotions.

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

        BUSINESS PRINCIPLES:

        1. Area chứa các Table hợp lệ.

        2. Table có định danh thực tế
           và thuộc Area theo Schema.

        3. Order liên kết Table
           theo hợp đồng Backend.

        4. Order Items được quản lý
           bởi POS Backend của Đạt.

        5. Product Data được quản lý
           bởi Catalog của Ninh.

        6. Payment State được xác nhận
           bởi Payment Backend.

        7. Authorization được quản lý
           bởi hệ thống của Hợp.

        8. Reservation và Maintenance
           ảnh hưởng đến khả năng
           tiếp nhận khách theo Policy.

        9. Table Display Status
           phải theo Contract của TASK 07.

        10. In chứng từ không phải
            bằng chứng thanh toán thành công.

        11. Payment Success không tự động
            đồng nghĩa Table Available
            khi còn ràng buộc khác.

        DATABASE:

        Các bảng có thể liên quan:

        - areas.
        - cafe_tables.
        - orders.
        - order_items.
        - products.
        - payments.
        - reservations.

        Đây là danh sách tham khảo.

        Phải xác minh:
        - Tên bảng.
        - Primary Keys.
        - Foreign Keys.
        - Relationships.
        - Status Fields.
        - Business Constraints.

        Không suy diễn Schema từ prompt.

        TESTING ENVIRONMENT:

        Ưu tiên:
        - Database testing độc lập.
        - Laravel Feature Tests.
        - Laravel Unit Tests.
        - HTTP Integration Tests.
        - Browser Tests nếu có.
        - Payment Fake/Mock/Sandbox.
        - Printer Preview Test.

        Không dùng Production Database
        để kiểm thử thao tác ghi.

        Không dùng tiền thật để test.

        Không khẳng định đã kiểm thử
        máy in vật lý nếu chỉ mở Print Dialog.

        TASK 09 phải kiểm chứng
        kết quả thật, không chỉ đọc code.
    </context>

    <scope>
        PHẠM VI ĐƯỢC PHÉP:

        A. SOURCE CODE REVIEW:

        - TASK 01-08.
        - Area Models/Controllers/Views.
        - Cafe Table Models/Controllers/Views.
        - Floor Plan.
        - Table Detail.
        - Order Integration.
        - POS Routes.
        - Payment Integration.
        - Receipt/Print Views.
        - Table Status Resolver.
        - Authentication/Authorization.
        - Existing Tests.

        B. END-TO-END WORKFLOW:

        - Area Selection.
        - Table Selection.
        - Active Order Lookup.
        - Create/Continue Order.
        - Product Selection.
        - Save Order Items.
        - Checkout.
        - Payment Result.
        - Receipt Rendering.
        - Table Status Refresh.

        C. FUNCTIONAL TESTING:

        - Area UI.
        - Table Floor Plan.
        - Table Detail.
        - Order-at-Table.
        - Checkout.
        - Receipt.
        - State Synchronization.

        D. SECURITY TESTING:

        - Authentication.
        - Authorization.
        - CSRF.
        - IDOR.
        - XSS.
        - Request Validation.
        - Mass Assignment Boundaries.
        - Financial Data Integrity.

        E. DATA INTEGRITY:

        - Table-Order Relationship.
        - Order Items Persistence.
        - Payment Confirmation.
        - Status Consistency.
        - Duplicate Requests.
        - Concurrent Operations.

        F. PERFORMANCE:

        - Query Count.
        - N+1 Queries.
        - Duplicate Queries.
        - Floor Plan Load.
        - Order Summary Load.
        - Table Status Refresh.
        - Asset Build.

        G. UI/UX:

        - Desktop.
        - Tablet.
        - Mobile.
        - Touch Interaction.
        - Loading/Empty/Error States.
        - Conflict States.
        - Accessibility.
        - Print Preview.

        H. BUG FIX:

        - Chỉ sửa lỗi được xác nhận.
        - Chỉ sửa file thuộc phạm vi
          Area &amp; Table POS UI.
        - Không tự thay đổi nghiệp vụ
          của các thành viên khác.
        - Chạy lại test sau khi sửa.

        I. FINAL REPORT:

        - Test Matrix.
        - Bug Fix Summary.
        - Security Review.
        - Performance Report.
        - Regression Report.
        - Integration Dependencies.
        - Final Acceptance Status.

        FILE DỰ KIẾN KIỂM TRA:

        - app/Models/

        - app/Http/Controllers/Admin/

        - app/Services/

        - resources/views/admin/areas/

        - resources/views/admin/tables/

        - resources/views/admin/pos/

        - resources/views/layouts/

        - resources/js/

        - resources/css/

        - routes/web.php

        - routes/admin.php nếu tồn tại.

        - tests/Feature/

        - tests/Unit/

        - tests/Browser/ nếu có.

        Tên thư mục mang tính tham khảo.

        BẮT BUỘC tìm đúng file thực tế.

        KHÔNG TRIỂN KHAI:

        - POS Backend mới.
        - Payment Service mới.
        - Payment Gateway mới.
        - Product Catalog mới.
        - Reservation Business Logic mới.
        - Table Status Enum mới.
        - Database Migration.
        - Database Schema Modification.
        - Refund/Split Payment mới.
        - Chuyển/Gộp/Tách bàn mới.
        - Realtime Infrastructure mới.
        - Refactor toàn bộ dự án.
    </scope>

    <hardRules>
        1. BẮT BUỘC khảo sát source code
           trước khi thực hiện kiểm thử.

        2. Kiểm tra:
           - Current Branch.
           - git status.
           - git diff.
           - TASK 01-08.
           - Models/Migrations.
           - Controllers/Services.
           - Routes/Middleware.
           - Blade Views.
           - JavaScript/CSS.
           - Existing Tests.
           - Testing Environment.

        3. TRƯỚC KHI SỬA CODE:

           Phải báo cáo:
           - Module Inventory.
           - Các chức năng đã hoàn thành.
           - Các chức năng còn thiếu.
           - API Contracts thực tế.
           - Business Rules thực tế.
           - Test Environment.
           - Test Matrix ưu tiên.
           - Các rủi ro dữ liệu.
           - File dự kiến thay đổi.
           - Mini Plan.

        4. TIME LIMIT:

           TASK phải thực hiện trong
           60-90 phút.

           Ưu tiên kiểm thử rủi ro cao.

           Không cố kiểm thử toàn bộ
           mọi trường hợp khi không đủ giờ.

           Những case chưa kiểm thử
           phải ghi NOT RUN.

        5. TEST PRIORITY:

           P0 - CRITICAL:

           - Sai Order/Table Identity.
           - Unauthorized Access.
           - Duplicate Payment.
           - Paid Status giả.
           - Sai tổng tiền chính thức.
           - Mất Order Items.
           - Table Status sai nghiêm trọng.
           - Giải phóng bàn trái Policy.
           - Giao dịch ghi dữ liệu ngoài quyền.

           P1 - HIGH:

           - Không mở được Order hợp lệ.
           - Save Order thất bại.
           - Không xử lý Conflict.
           - Receipt chứa dữ liệu sai.
           - Responsive không thao tác được.
           - Performance Bottleneck nghiêm trọng.

           P2 - MEDIUM/LOW:

           - Spacing.
           - Icons.
           - Hover/Animation.
           - Cosmetic Issues.

           Phải ưu tiên P0 và P1.

        6. DATABASE SAFETY:

           - Kiểm tra .env.testing.
           - Xác nhận tên/kết nối Test Database
             mà không làm lộ thông tin bí mật.
           - Không mặc định .env.testing
             đã trỏ đến database an toàn.
           - Không chạy migrate:fresh
             trên database dùng chung.
           - Không chạy db:wipe.
           - Không truncate Production Data.
           - Không tạo giao dịch thật
             trong môi trường Production.
           - Không dùng Payment Gateway thật
             để thử nghiệm.

        7. TEST FIXTURES:

           - Dữ liệu test phải tách biệt.
           - Dùng Factory/Seeder testing
             theo cách dự án hỗ trợ.
           - Không sửa dữ liệu sản xuất.
           - Không hardcode kết quả PASS.
           - Không dựa vào dữ liệu demo
             để tuyên bố hệ thống đúng.

        8. TEST RESULT RULES:

           PASS:
           Đã chạy, kết quả đúng kỳ vọng.

           FAIL:
           Đã chạy, kết quả không đúng.

           BLOCKED:
           Không thể kiểm thử do thiếu
           Contract, quyền hoặc phụ thuộc.

           NOT RUN:
           Chưa thực hiện kiểm thử.

           NOT APPLICABLE:
           Không áp dụng theo thiết kế
           đã được xác minh.

           Không được đổi NOT RUN thành PASS.

        9. TEST EVIDENCE:

           Ghi:
           - Test ID.
           - Test Command/Steps.
           - Expected Result.
           - Actual Result.
           - Status.
           - Environment.
           - Evidence.

           Chỉ chụp Screenshot nếu
           có Browser/UI thực sự chạy.

           Không tự tạo Screenshot giả.

        10. AREA MANAGEMENT:

            Kiểm tra:
            - Area Listing.
            - Area Statistics.
            - Area Search/Filter.
            - Area CRUD Regression.
            - Area-Table Relationship.
            - Area Navigation.

            Không sửa Area Business Rules
            chỉ để test đạt.

        11. TABLE FLOOR PLAN:

            Kiểm tra:
            - Table Cards.
            - Table Code/Number.
            - Area Name.
            - Capacity.
            - Status.
            - Search.
            - Filters.
            - Status Statistics.

            Không hardcode Table Data.

            Không thay đổi Table Status
            chỉ vì mở Floor Plan.

        12. TABLE DETAIL:

            Kiểm tra:
            - Table Identity.
            - Area.
            - Capacity.
            - Persisted/Display Status.
            - Active Order.
            - Allowed Actions.
            - Authorization.

            Không mở nhầm Order
            của bàn khác.

        13. ORDER CREATION:

            - Chỉ dùng POS Backend hiện có.
            - Không tự INSERT Order.
            - Không tạo Order bằng GET.
            - Không bỏ qua Order Validation.
            - Không tạo Order cho bàn
              đang bị chặn bởi Business Rules.
            - Không tạo Order trùng.
            - Nếu Backend thiếu bảo vệ
              concurrency, báo cáo cho Đạt.

        14. CONTINUE ORDER:

            - Mở đúng Active Order.
            - Không tự tạo Order mới.
            - Không mở Order đã hoàn tất
              trái nghiệp vụ.
            - Không mở Order bàn khác.
            - Xử lý Multiple Active Orders
              theo Policy đã duyệt.

        15. ORDER ITEMS:

            - Product lấy từ Catalog.
            - Quantity hợp lệ.
            - Notes hợp lệ.
            - Draft khác Saved Items.
            - Save dùng POS Backend.
            - Không sửa Unit Price tùy ý.
            - Không cộng trùng Items.
            - Không báo lưu thành công giả.
            - Không làm mất Draft khi API lỗi
              trái chính sách đã thiết kế.

        16. PRODUCT PRICING:

            - Backend quyết định giá.
            - Backend quyết định Promotion.
            - Backend quyết định Tax/Discount
              nếu nghiệp vụ có hỗ trợ.
            - Không tin Client Total.
            - Không sửa Catalog của Ninh.
            - Không sửa Pricing Logic của Đạt.

        17. CHECKOUT:

            - Order phải hợp lệ.
            - User phải có quyền.
            - Amount phải từ Backend.
            - Payment Method phải tồn tại.
            - Không Checkout Order sai.
            - Không tự đánh dấu Paid.

        18. PAYMENT CONFIRMATION:

            - Success chỉ từ Backend.
            - Pending không được coi Paid.
            - Failed không được coi Paid.
            - Unknown phải kiểm tra lại.
            - Không tin Query Parameter success.
            - Không tự tạo Payment Record.
            - Không tự sửa Payment Status.

        19. DUPLICATE PAYMENT:

            - Kiểm tra Double Click.
            - Kiểm tra Repeated Requests.
            - Kiểm tra Concurrent Checkout
              nếu môi trường cho phép.
            - Không retry mù quáng
              giao dịch chưa rõ kết quả.
            - Xác minh Idempotency/Protection
              có thật ở Backend.

            Nếu chưa có:
            - Ghi HIGH-RISK.
            - Đánh dấu BLOCKED khi phụ thuộc.
            - Không tự viết Payment Service mới.

        20. RECEIPT &amp; PRINT:

            - Phiếu tạm tính phải rõ nhãn.
            - Biên nhận cần Payment hợp lệ.
            - Không giả hóa đơn điện tử.
            - Receipt Data từ Backend.
            - Không tạo Transaction ID giả.
            - Không in Order bàn khác trái quyền.
            - Nhấn In không tạo Payment.
            - Nhấn In không sửa Order Status.
            - Nhấn In không sửa Table Status.
            - Print Error không hoàn tác Payment.

        21. TABLE STATUS:

            - Dùng Contract TASK 07.
            - Floor Plan và Detail nhất quán.
            - Area Statistics đếm đúng.
            - Không giả định Paid = Available.
            - Không bỏ qua Active Orders.
            - Không bỏ qua Reservation.
            - Không bỏ qua Maintenance.
            - Không tự UPDATE status
              từ thao tác Refresh.

        22. TABLE RELEASE:

            Kiểm tra:
            - Order Completion.
            - Payment Confirmation.
            - Other Active Orders.
            - Reservation.
            - Maintenance.
            - Cleanup Policy nếu có.

            Không được giải phóng bàn
            nếu thiếu điều kiện nghiệp vụ.

            Nếu Table Release Policy chưa rõ,
            phải báo cáo BLOCKED.

        23. RESERVATION:

            - Không tự nhận bàn đã đặt.
            - Không tự hủy đặt bàn.
            - Không nhận khách trái Policy.
            - Kiểm tra xung đột khi
              Order được tạo tại bàn
              có Reservation hợp lệ.

        24. MAINTENANCE:

            - Không cho Order trái Policy.
            - Không tự bỏ Maintenance.
            - Không tự đổi trạng thái
              chỉ để test đạt.
            - Kiểm tra UI và Backend Behavior.

        25. CONCURRENCY:

            Kiểm tra các tình huống:

            - Hai nhân viên chọn cùng Table.
            - Hai nhân viên tạo Order.
            - Hai nhân viên thêm món.
            - Checkout xảy ra khi đang thêm món.
            - Hai nhân viên cùng Checkout.
            - Reservation đổi trong lúc
              nhân viên đang chọn bàn.
            - Maintenance đổi khi Table đang mở.
            - Refresh Response về sai thứ tự.

            Kiểm thử bằng môi trường
            độc lập và cơ chế an toàn.

            Không chạy nhiều Payment thật.

        26. STALE STATE:

            - Không dùng State cũ
              để xác nhận thao tác ghi.
            - Backend phải xác minh lại
              điều kiện nghiệp vụ.
            - UI hiển thị Conflict.
            - Có Refresh an toàn.
            - Không tự ghi đè dữ liệu mới hơn.

        27. AUTHENTICATION:

            - Guest không truy cập
              chức năng quản trị trái Policy.
            - Session được kiểm tra.
            - Không bypass Middleware.
            - Không tự mở Public Route.

        28. AUTHORIZATION:

            Kiểm tra quyền:
            - Xem Area/Table.
            - Quản lý Area/Table.
            - Xem Order.
            - Tạo Order.
            - Sửa Order Items.
            - Checkout.
            - Xem/In Receipt.
            - Thao tác liên quan Status.

            Chỉ sử dụng Permission
            thực tế của dự án.

            Không tự tạo Role/Permission.

        29. IDOR SECURITY:

            - Thay Table ID trên URL.
            - Thay Order ID.
            - Thay Payment ID.
            - Thay Receipt ID.

            Backend phải áp dụng đúng
            quyền và quan hệ dữ liệu.

            Không chỉ ẩn Button ở UI.

        30. CSRF &amp; HTTP:

            - GET không tạo Order.
            - GET không tạo Payment.
            - GET không sửa Table.
            - POST/PATCH/PUT/DELETE
              phải tuân thủ Contract.
            - CSRF Protection vẫn hoạt động.
            - Không tạo Route ghi dữ liệu
              không có kiểm soát quyền.

        31. XSS &amp; INPUT VALIDATION:

            - Product Name.
            - Area Name.
            - Table Code.
            - Order Notes.
            - Receipt Data.
            - Search Input.

            - Escape output.
            - Validate input.
            - Không render Raw HTML nguy hiểm.
            - Không làm lộ SQL Exception.
            - Không bypass Form Request.

        32. PERFORMANCE:

            Phải đánh giá:

            - Floor Plan Load.
            - Area Statistics.
            - Table Detail.
            - Active Order Lookup.
            - Product Listing.
            - Order Summary.
            - Status Refresh.

            Ghi nhận khi đo được:
            - Dataset Size.
            - Query Count.
            - Query Duration.
            - Request Duration.
            - N+1 Presence.
            - Duplicate Queries.

            Không tự tuyên bố đạt
            ngưỡng hiệu năng chưa được đặt.

            Nếu chưa có Baseline,
            báo cáo số liệu đo
            và đề xuất ngưỡng tham khảo.

        33. PERFORMANCE SAFETY:

            - Không chạy Load Test nặng
              trên Server dùng chung.
            - Không tạo lượng lớn
              dữ liệu production.
            - Không tự tạo Cache phức tạp.
            - Không sửa Backend ngoài Scope
              để tối ưu truy vấn.
            - Báo cáo Owner nếu Bottleneck
              thuộc module khác.

        34. RESPONSIVE:

            Kiểm tra:
            - Mobile.
            - Tablet.
            - Laptop.
            - Desktop.

            Mỗi màn hình cần xem xét:
            - Layout.
            - Touch Actions.
            - Drawer/Panel.
            - Search/Filter.
            - Table Grid.
            - Product Cart.
            - Checkout Actions.
            - Error Messages.
            - Print Preview.

            Không báo UI PASS
            nếu chỉ kiểm tra một viewport.

        35. ACCESSIBILITY:

            - Keyboard Navigation.
            - Focus State.
            - Form Labels.
            - Button Semantics.
            - Modal/Drawer Focus.
            - Escape Close.
            - Status Labels.
            - Color Contrast.

            Không chỉ dùng màu sắc
            để thể hiện trạng thái bàn.

        36. ERROR STATES:

            - API Unavailable.
            - Validation Error.
            - Unauthorized.
            - Table Conflict.
            - Order Closed.
            - Product Unavailable.
            - Payment Failed.
            - Payment Pending.
            - Print Failure.
            - Unknown State.

            UI phải thông báo rõ
            nhưng không làm lộ
            thông tin kỹ thuật nhạy cảm.

        37. BUG FIX:

            - Chỉ sửa lỗi đã xác nhận.
            - Ưu tiên Critical/High.
            - Chỉ sửa file thuộc module.
            - Không sửa Order Business Logic.
            - Không sửa Payment Business Logic.
            - Không sửa Auth của Hợp.
            - Không sửa Catalog của Ninh.
            - Không thay đổi Database Schema.
            - Chạy lại test sau khi sửa.

        38. REGRESSION:

            Kiểm tra:
            - TASK 01 Architecture Contract.
            - TASK 02 Area Management UI.
            - TASK 03 Floor Plan.
            - TASK 04 Table Detail.
            - TASK 05 Order-at-Table.
            - TASK 06 Checkout/Receipt.
            - TASK 07 Status Synchronization.
            - TASK 08 Responsive/UX.

            Không tuyên bố Regression PASS
            khi chưa chạy các case tương ứng.

        39. GIT SAFETY:

            - Không tự động commit.
            - Không tự động push.
            - Không tự động merge.
            - Không ghi đè code
              chưa commit của người khác.
            - Không đổi branch khi
              Working Tree không an toàn.
            - Không tạo Pull Request tự động.

        40. FINAL STATUS:

            READY FOR REVIEW chỉ khi:

            - P0 Tests được thực hiện
              và không còn lỗi Critical.
            - Các luồng cốt lõi đã được
              xác minh trong phạm vi hỗ trợ.
            - Không có lỗi High nghiêm trọng
              chưa xử lý ảnh hưởng nghiệm thu.
            - Không có blocker nghiệp vụ
              ngăn luồng POS chính.
            - Không có thay đổi ngoài Scope.
            - Có Test Report thực tế.

            Nếu không đạt các điều kiện:
            NOT READY FOR REVIEW.

            Không đánh đồng READY FOR REVIEW
            với đã được Merge/Deploy.
    </hardRules>

    <tasks>
        <taskUnit id="01">
            <name>
                Khảo sát Integration
                và lập Final Test Matrix
            </name>

            <estimatedTime>
                10-15 phút.
            </estimatedTime>

            <steps>
                - Kiểm tra Current Branch.
                - Kiểm tra git status/git diff.
                - Đọc TASK 01-08.
                - Kiểm tra Area/Table Routes.
                - Kiểm tra Order Services.
                - Kiểm tra Payment Contracts.
                - Kiểm tra Receipt Routes.
                - Kiểm tra Status Resolver.
                - Kiểm tra Authorization.
                - Kiểm tra Existing Tests.
                - Kiểm tra Test Database.

                - Xác định:
                  + Chức năng đã có.
                  + Chức năng còn thiếu.
                  + API có thể kiểm thử.
                  + Business Rules đã xác minh.
                  + Test Scenarios P0/P1.
                  + Các blockers.

                - Lập Final Test Matrix:

                  Test ID.
                  Module.
                  Scenario.
                  Expected Result.
                  Priority.
                  Test Method.
                  Environment.
                  Status.
                  Evidence.

                - Trình bày Mini Plan
                  trước khi sửa code.
            </steps>
        </taskUnit>

        <taskUnit id="02">
            <name>
                Kiểm thử End-to-End POS Workflow
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. AREA &amp; TABLE:

                - Load Areas.
                - Select Area.
                - Load Tables.
                - Filter by Status.
                - Search Table.
                - Open Table Detail.

                B. ORDER ENTRY:

                - Check Active Order.
                - Create Order nếu đủ điều kiện.
                - Continue Existing Order.
                - Verify Order/Table Relationship.
                - Reject Invalid Table.
                - Reject Forbidden Order.

                C. ORDER ITEMS:

                - Load Products.
                - Select Product.
                - Adjust Quantity.
                - Add Notes nếu hỗ trợ.
                - Save Items.
                - Reload Order.
                - Verify Persisted Items.
                - Verify Server Total.

                D. CHECKOUT:

                - Open Correct Checkout.
                - Read Backend Amount.
                - Check Payment Eligibility.
                - Use Supported Payment Method.
                - Verify Backend Payment Result.

                E. RECEIPT:

                - Render Receipt đúng loại.
                - Verify Receipt Data.
                - Open Print Preview.
                - Confirm Print does not modify
                  Payment/Order/Table State.

                F. TABLE STATUS:

                - Refresh Floor Plan.
                - Refresh Table Detail.
                - Check Area Statistics.
                - Verify Display Status
                  theo Policy thực tế.

                G. NEGATIVE FLOW:

                - Order Creation Failed.
                - Product Invalid.
                - Save Order Failed.
                - Payment Failed.
                - Payment Pending.
                - Backend Unavailable.

                - Ghi Test Results thực tế.
                - Không tạo dữ liệu Production.
            </steps>
        </taskUnit>

        <taskUnit id="03">
            <name>
                Kiểm thử Security,
                Concurrency và Data Integrity
            </name>

            <estimatedTime>
                15-20 phút.
            </estimatedTime>

            <steps>
                A. AUTHENTICATION:

                - Guest Access.
                - Authorized Staff.
                - Unauthorized Staff.
                - Admin theo quyền thực tế.

                B. AUTHORIZATION:

                - View Table.
                - Create Order.
                - Edit Order Items.
                - Checkout.
                - View Receipt.
                - Print Receipt.

                C. IDOR:

                - Invalid Table ID.
                - Unauthorized Order ID.
                - Unauthorized Payment ID.
                - Unauthorized Receipt ID.

                D. CSRF &amp; VALIDATION:

                - Invalid Product.
                - Invalid Quantity.
                - Invalid Request Fields.
                - Invalid HTTP Method.
                - CSRF Protection.

                E. DATA INTEGRITY:

                - Order gắn đúng Table.
                - Order Items lưu đúng.
                - Không tự thay đổi Price.
                - Payment Result chính xác.
                - Table State đúng Policy.

                F. CONCURRENCY:

                - Double Create Order.
                - Double Save Items.
                - Double Checkout.
                - Concurrent Table Actions.
                - Stale Order.
                - Stale Status.

                Chỉ chạy những case
                môi trường có thể kiểm soát.

                Nếu thiếu Backend Contract:
                - Không sửa Service của Owner.
                - Ghi BLOCKED/HIGH-RISK.
                - Mô tả điều kiện tái hiện.

                G. FINANCIAL SAFETY:

                - Không Paid giả.
                - Không tin Client Total.
                - Không tạo Payment trùng.
                - Không tự giải phóng Table.
                - Không phát hành Invoice giả.
            </steps>
        </taskUnit>

        <taskUnit id="04">
            <name>
                Performance, UI/UX,
                Bug Fix và Regression
            </name>

            <estimatedTime>
                15-20 phút.
            </estimatedTime>

            <steps>
                A. PERFORMANCE:

                - Kiểm tra N+1 Query.
                - Kiểm tra Duplicate Query.
                - Floor Plan Query Count.
                - Area Statistics Query Count.
                - Table Detail Query Count.
                - Status Refresh Query Count.
                - Ghi Dataset Size nếu đo được.
                - Không chạy Load Test nặng.

                B. RESPONSIVE:

                - Mobile.
                - Tablet.
                - Laptop.
                - Desktop.

                Kiểm tra:
                - Table Cards.
                - Area Tabs.
                - Detail Panel.
                - Product Cart.
                - Checkout UI.
                - Print Preview.
                - Loading/Error States.

                C. ACCESSIBILITY:

                - Keyboard Navigation.
                - Focus State.
                - Button Labels.
                - Modal/Drawer.
                - Status Labels.
                - Touch Targets.

                D. BUG FIX:

                - Tổng hợp Bugs.
                - Đánh giá Severity.
                - Sửa Critical/High
                  thuộc phạm vi được phép.
                - Không sửa Backend
                  của thành viên khác.
                - Chạy lại Failed Tests.

                E. REGRESSION:

                - TASK 02 Area UI.
                - TASK 03 Floor Plan.
                - TASK 04 Table Detail.
                - TASK 05 Order UI.
                - TASK 06 Checkout.
                - TASK 07 Status Sync.
                - TASK 08 UI/UX.
                - Admin Layout.
                - Existing CRUD.
                - Authorization.

                - Các case chưa chạy
                  phải được ghi NOT RUN.
            </steps>
        </taskUnit>

        <taskUnit id="05">
            <name>
                Final Acceptance Report
                và Handoff
            </name>

            <estimatedTime>
                5-10 phút.
            </estimatedTime>

            <steps>
                - Tổng hợp Test Matrix.
                - Tổng hợp PASS/FAIL.
                - Tổng hợp BLOCKED/NOT RUN.
                - Tổng hợp Bugs đã sửa.
                - Tổng hợp Bugs chưa sửa.
                - Tổng hợp Security Review.
                - Tổng hợp Performance Results.
                - Tổng hợp Responsive Results.
                - Tổng hợp Regression Results.

                - Chạy lại các test trọng yếu
                  nếu thời gian còn cho phép.

                - Kiểm tra PHP Syntax.
                - Kiểm tra Laravel Tests.
                - Kiểm tra Asset Build.
                - Kiểm tra route:list.
                - Kiểm tra git status/git diff.

                - Xác nhận:
                  + Không tạo Migration.
                  + Không sửa Schema.
                  + Không sửa Auth.
                  + Không sửa Order Backend.
                  + Không sửa Payment Backend.
                  + Không sửa Catalog.
                  + Không sửa ngoài Scope.

                - Xuất Final Integration Report.

                - Kết luận READY FOR REVIEW
                  hoặc NOT READY FOR REVIEW.

                - Không commit/push/merge.
            </steps>
        </taskUnit>
    </tasks>

    <acceptanceCriteria>
        1. Đã khảo sát TASK 01-08.

        2. Có Final Test Matrix.

        3. Có môi trường kiểm thử an toàn.

        4. Area Selection được kiểm thử.

        5. Table Floor Plan được kiểm thử.

        6. Table Detail được kiểm thử.

        7. Active Order Lookup được kiểm thử.

        8. Create/Continue Order được kiểm thử
           theo API thực tế.

        9. Order/Table Relationship chính xác.

        10. Không tạo Order trùng
            trong các case đã xác minh.

        11. Product Data lấy từ Backend.

        12. Quantity/Notes được kiểm thử.

        13. Order Items Persistence
            được kiểm chứng thực tế.

        14. Không cộng trùng Order Items.

        15. Backend Total được xác minh.

        16. Checkout đúng Order.

        17. Payment Methods hợp lệ.

        18. Không đánh dấu Paid giả.

        19. Pending Payment được xử lý đúng.

        20. Failed Payment được xử lý đúng.

        21. Duplicate Payment Protection
            được xác minh hoặc ghi blocker.

        22. Receipt Data chính xác.

        23. Không giả lập hóa đơn điện tử.

        24. Print không tạo Payment mới.

        25. Print không đổi Table Status.

        26. Table State đúng Status Contract.

        27. Không giải phóng bàn
            trái Business Rules.

        28. Reservation được kiểm tra
            trong phạm vi liên quan.

        29. Maintenance được kiểm tra
            trong phạm vi liên quan.

        30. Area Statistics nhất quán
            với Floor Plan.

        31. Table Detail nhất quán
            với Floor Plan.

        32. Authentication được kiểm thử.

        33. Authorization được kiểm thử.

        34. Không có IDOR nghiêm trọng
            trong các case đã kiểm chứng.

        35. CSRF Protection được kiểm tra.

        36. Input Validation được kiểm tra.

        37. Concurrency Risks được đánh giá.

        38. Không còn lỗi Critical
            chưa xử lý trong phạm vi nghiệm thu.

        39. Performance được đánh giá
            bằng số liệu thực khi có công cụ.

        40. Không có N+1 Query nghiêm trọng
            chưa được xử lý hoặc báo cáo.

        41. Responsive được kiểm thử
            trên các viewport khả dụng.

        42. Có Accessibility Review.

        43. Có Loading/Empty/Error Review.

        44. Có Regression Results.

        45. Không thay đổi Database Schema.

        46. Không tạo Migration.

        47. Không sửa Order/Payment Backend
            ngoài quyền sở hữu.

        48. Không sửa Catalog/Promotion Logic.

        49. Không sửa Authentication/Authorization.

        50. Không tự động commit/push/merge.

        51. Không báo PASS khi chưa chạy.

        52. Có Bug Fix Summary.

        53. Có Evidence cho các kết quả
            đã thực sự kiểm chứng.

        54. Có báo cáo rõ chức năng BLOCKED.

        55. AI Agent phải xuất FINAL REPORT:

            A. PROJECT OVERVIEW
            - Project.
            - Module.
            - Branch.
            - Test Environment.
            - Scope.
            - Current Implementation Status.

            B. TASK COMPLETION MATRIX
            - TASK 01.
            - TASK 02.
            - TASK 03.
            - TASK 04.
            - TASK 05.
            - TASK 06.
            - TASK 07.
            - TASK 08.
            - TASK 09.

            - Verified.
            - Partial.
            - Blocked.
            - Not Verified.

            C. END-TO-END WORKFLOW
            - Area Selection.
            - Table Selection.
            - Table Detail.
            - Create/Continue Order.
            - Product Selection.
            - Save Order Items.
            - Checkout.
            - Payment Result.
            - Receipt/Print.
            - Table State Refresh.

            D. FUNCTIONAL TEST RESULTS
            - Test ID.
            - Module.
            - Scenario.
            - Expected Result.
            - Actual Result.
            - PASS/FAIL/BLOCKED/NOT RUN.
            - Test Command.
            - Evidence.

            E. SECURITY REVIEW
            - Authentication.
            - Authorization.
            - CSRF.
            - IDOR.
            - XSS.
            - Validation.
            - Financial Integrity.

            F. DATA INTEGRITY
            - Table-Order Relationship.
            - Order Items Persistence.
            - Order Total.
            - Payment Result.
            - Receipt Reference.
            - Table Status Consistency.

            G. CONCURRENCY REVIEW
            - Duplicate Order.
            - Duplicate Item Save.
            - Duplicate Payment.
            - Stale Table.
            - Stale Order.
            - Concurrent Checkout.
            - Backend Limitations.

            H. PERFORMANCE REPORT
            - Dataset Size.
            - Query Count.
            - Request Duration.
            - N+1 Analysis.
            - Duplicate Queries.
            - Bottlenecks.
            - Các giới hạn đo lường.

            I. UI/UX REPORT
            - Area UI.
            - Floor Plan.
            - Table Detail.
            - Order UI.
            - Checkout.
            - Receipt.
            - Desktop.
            - Tablet.
            - Mobile.
            - Accessibility.

            J. BUG FIX SUMMARY
            - Bug ID.
            - Severity.
            - Root Cause.
            - Affected File.
            - Fix Description.
            - Retest Result.
            - Remaining Bugs.

            K. REGRESSION REPORT
            - Area CRUD.
            - Cafe Table CRUD.
            - Reservation.
            - Order/POS.
            - Payment.
            - Catalog Integration.
            - Admin Layout.
            - Authentication/Authorization.

            L. FILE CHANGES
            - File tạo mới.
            - File chỉnh sửa.
            - Mục đích thay đổi.
            - Xác nhận không sửa ngoài Scope.

            M. OPEN ISSUES &amp; DEPENDENCIES
            - Missing APIs.
            - Business Rules chưa rõ.
            - Required Owner.
            - Severity.
            - Recommended Next Action.

            N. GIT REVIEW
            - Current Branch.
            - Working Tree Status.
            - Changed Files.
            - Uncommitted Changes.
            - Files Outside Scope.
            - PR Readiness.

            O. FINAL ACCEPTANCE
            - READY FOR REVIEW
              hoặc NOT READY FOR REVIEW.
            - Tổng số PASS.
            - Tổng số FAIL.
            - Tổng số BLOCKED.
            - Tổng số NOT RUN.
            - Các rủi ro còn lại.
            - Điều kiện cần hoàn thành
              trước khi tạo Pull Request.

        56. Chỉ kết luận READY FOR REVIEW
            khi các kiểm thử P0 đạt
            và không còn blocker nghiêm trọng
            ngăn luồng POS cốt lõi.

        57. Nếu Payment Backend chưa sẵn sàng,
            không được tuyên bố luồng
            Checkout/Payment đã hoàn thiện.

        58. Nếu các API Order chưa sẵn sàng,
            không được giả lập dữ liệu
            để kết luận End-to-End PASS.

        59. Nếu chỉ kiểm thử bằng Mock,
            phải ghi rõ phạm vi kiểm thử.
            Không xem kết quả Mock là bằng chứng
            giao dịch tiền thật thành công.

        60. TASK 09 kết thúc bằng:
            - Final Test Matrix.
            - Final Bug Report.
            - Final Integration Report.
            - Final Readiness Assessment.

            Không tự động Merge hoặc Deploy.
    </acceptanceCriteria>
</prompt>
```