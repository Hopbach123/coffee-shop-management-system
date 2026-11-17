```xml
<?xml version="1.0" encoding="UTF-8"?>
<prompt>
    <goal>
        Khảo sát kiến trúc và thiết kế luồng
        POS tại bàn cho Coffee Shop Management System.

        TASK:
        01 - Area &amp; Table POS
        Architecture &amp; Integration Planning.

        THỜI GIAN:
        60-90 phút.

        BRANCH ĐỀ XUẤT:
        feature/area-table-pos-ui.

        MỤC TIÊU CHÍNH:

        1. Khảo sát toàn bộ cấu trúc
           dự án Laravel hiện tại.

        2. Rà soát Area Management.

        3. Rà soát Cafe Table Management.

        4. Rà soát POS/Order Management.

        5. Rà soát Order Items và
           các quan hệ sản phẩm liên quan.

        6. Rà soát Payment Management.

        7. Rà soát Reservation Management.

        8. Kiểm tra:
           - Models.
           - Database Schema.
           - Migrations.
           - Controllers.
           - Services.
           - Routes.
           - Middleware.
           - Policies/Gates.
           - Blade Views.
           - Existing Tests.

        9. Xác định khả năng kết nối:
           Area -> Cafe Table -> Order
           -> Payment -> Receipt.

        10. Thiết kế luồng POS tại bàn:
            - Chọn khu vực.
            - Xem danh sách bàn.
            - Chọn một bàn.
            - Xem trạng thái bàn.
            - Tạo đơn tại bàn.
            - Tiếp tục đơn đang phục vụ.
            - Gọi món.
            - Lưu đơn hàng.
            - Xem tạm tính.
            - Thanh toán.
            - In chứng từ phù hợp.
            - Đồng bộ trạng thái bàn.

        11. Thiết kế dữ liệu phục vụ UI:
            - Danh sách khu vực.
            - Danh sách bàn theo khu vực.
            - Số lượng bàn theo trạng thái.
            - Đơn hàng đang phục vụ tại bàn.
            - Tổng tiền hiện tại nếu có.
            - Thông tin thanh toán cần thiết.

        12. Kiểm tra quy tắc trạng thái bàn
            và vòng đời đơn hàng.

        13. Thiết kế Integration Contract
            cho các chức năng Order/Payment.

        14. Xác định phần việc phụ thuộc
            vào các thành viên khác.

        15. Thiết kế UI Architecture
            cho TASK 02-09.

        16. Xây dựng tài liệu kỹ thuật
            làm nền tảng triển khai.

        KẾT QUẢ MONG MUỐN:

        - Có báo cáo khảo sát code thực tế.
        - Có Database Relationship Mapping.
        - Có Route/API Inventory.
        - Có State Transition Design.
        - Có POS-at-Table Workflow.
        - Có Integration Contract.
        - Có UI Architecture đề xuất.
        - Có danh sách Dependencies/Blockers.
        - Có phân chia trách nhiệm rõ ràng.
        - Có tài liệu bàn giao TASK 02-09.

        Không tự xây dựng hệ thống POS mới.

        Không tự thay đổi nghiệp vụ Order.

        Không tự thay đổi nghiệp vụ Payment.

        Không tạo Migration mới.

        Không chỉnh sửa Database Schema.

        Không thay đổi giao diện trong TASK 01.
    </goal>

    <context>
        PROJECT:
        Coffee Shop Management System.

        OWNER:
        Thành Toàn.

        MODULE:
        Area &amp; Table POS Management.

        BRANCH:
        feature/area-table-pos-ui.

        TECHNOLOGY:
        - PHP Laravel.
        - MySQL.
        - Laravel MVC.
        - Eloquent ORM.
        - Blade Template.
        - JavaScript.
        - Authentication/Authorization hiện có.

        TEAM OWNERSHIP:

        THÀNH TOÀN:
        - Area Management.
        - Cafe Table Management.
        - Reservation Management.
        - Dashboard.
        - User Management.
        - Customer Management.
        - Area &amp; Table POS UI.

        HỢP:
        - Authentication.
        - Authorization.
        - Inventory.

        NINH:
        - Catalog.
        - Promotion.

        ĐẠT:
        - POS.
        - Order.
        - Payment.

        YÊU CẦU NGƯỜI DÙNG:

        Xây dựng giao diện quản lý khu vực
        và quản lý bàn hiện đại, lấy cảm hứng
        từ cách vận hành của Sapo FnB.

        Yêu cầu:
        - Hiển thị khu vực rõ ràng.
        - Hiển thị số bàn.
        - Hiển thị trạng thái bàn.
        - Xem sơ đồ bàn trực quan.
        - Nhấn vào bàn để nhận Order.
        - Thêm món tại bàn.
        - Xem Order đang phục vụ.
        - Thực hiện thanh toán.
        - In chứng từ phù hợp.
        - Cập nhật trạng thái bàn.

        ĐỊNH HƯỚNG UI:

        Modern Coffee POS Dashboard.

        Ưu tiên:
        - Giao diện chuyên nghiệp.
        - Thẻ khu vực trực quan.
        - Table Cards dễ thao tác.
        - Màu trạng thái rõ ràng.
        - Thao tác nhanh tại quầy.
        - Responsive Desktop/Tablet/Mobile.

        Không sao chép nguyên bản
        giao diện, tài sản hoặc mã nguồn
        của sản phẩm thương mại khác.

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
        Checkout, Receipt &amp; Printing UI.

        TASK 07:
        Table Status Synchronization.

        TASK 08:
        UI/UX Polish &amp; Responsive.

        TASK 09:
        Testing &amp; Final Integration.

        QUAN TRỌNG:

        Tên Model, bảng, trường, trạng thái,
        Route, Service và API chưa được
        cung cấp đầy đủ trong prompt này.

        AI Agent phải tìm trong source code
        trước khi đưa ra kết luận.

        Không được suy diễn rằng các module
        đã hoàn thành hoặc đã có đầy đủ API.

        Tất cả dữ liệu ví dụ chỉ phục vụ
        phân tích, không phải schema thực tế.
    </context>

    <scope>
        PHẠM VI TASK 01:

        A. PROJECT SURVEY:

        - Laravel Version.
        - Project Directory Structure.
        - Admin Layout.
        - Area Management.
        - Cafe Table Management.
        - Order/POS Management.
        - Payment Management.
        - Reservation Management.
        - Product/Catalog Integration.
        - Authentication/Authorization.
        - Existing Tests.

        B. DATABASE ANALYSIS:

        Khảo sát bảng thực tế liên quan đến:

        - Areas.
        - Cafe Tables.
        - Orders.
        - Order Items.
        - Products.
        - Payments.
        - Reservations.

        Tên bảng trên là gợi ý.

        Phải xác nhận tên thật.

        Xác định:
        - Primary Keys.
        - Foreign Keys.
        - Relationships.
        - Nullable Constraints.
        - Unique Constraints.
        - Status Fields.
        - Timestamps.
        - Soft Delete nếu có.

        C. BUSINESS WORKFLOW:

        Thiết kế luồng:

        Area Selection
             |
             v
        Table Selection
             |
             v
        Table Status Evaluation
             |
             v
        Create/Open Order
             |
             v
        Add/Update Order Items
             |
             v
        Calculate Total
             |
             v
        Checkout/Payment
             |
             v
        Receipt/Invoice Handling
             |
             v
        Table Status Synchronization.

        D. ROUTE/API SURVEY:

        Kiểm tra:
        - Existing Named Routes.
        - HTTP Methods.
        - Controllers.
        - Request Validation.
        - Middleware.
        - Authorization.
        - Response Structure.
        - Error Handling.

        E. INTEGRATION CONTRACT:

        Đề xuất hợp đồng tích hợp giữa
        Area/Table UI với Order/Payment.

        Bao gồm:
        - Input.
        - Output.
        - Validation.
        - Error Codes.
        - Authorization.
        - Ownership.
        - Data Dependencies.
        - Transaction Boundary.

        Chỉ ghi là EXISTING khi endpoint
        hoặc Service thực sự tồn tại.

        F. TABLE STATUS DESIGN:

        Khảo sát:
        - Available.
        - Occupied.
        - Reserved.
        - Maintenance.

        Các trạng thái trên chỉ là
        tham khảo từ kế hoạch trước.

        Phải xác minh enum/status thực tế.

        Phân biệt trạng thái được lưu
        trong database và trạng thái
        tổng hợp hiển thị trên UI.

        G. UI ARCHITECTURE:

        Đề xuất:
        - Area Overview.
        - Table Floor Plan.
        - Table Detail Panel.
        - Order Panel.
        - Checkout Section.
        - Receipt View.
        - Status Indicators.

        Không triển khai giao diện
        trong TASK 01.

        H. DOCUMENTATION:

        Tạo tài liệu khảo sát và thiết kế
        trong thư mục docs phù hợp
        nếu dự án cho phép.

        Tên đề xuất:
        docs/area-table-pos-architecture.md

        Không tạo file trùng.

        KHÔNG TRIỂN KHAI:

        - UI Area mới.
        - Table Grid mới.
        - Order Form mới.
        - Payment Form mới.
        - Receipt Template mới.
        - API Order mới.
        - Payment Gateway mới.
        - Table Status Sync mới.
        - Database Migration.
        - Schema Modification.
        - Business Logic Refactor.
    </scope>

    <hardRules>
        1. BẮT BUỘC kiểm tra source code
           trước khi đưa ra thiết kế.

        2. Kiểm tra:
           - Current Branch.
           - git status.
           - git diff.
           - Project Structure.
           - Routes.
           - Models.
           - Migrations.
           - Controllers.
           - Services.
           - Middleware/Policies.
           - Existing Tests.

        3. Không tự động chuyển branch
           nếu có thay đổi chưa commit
           hoặc có nguy cơ mất dữ liệu.

        4. Trước khi chỉnh sửa tài liệu,
           phải trình bày:

           - Hiện trạng kiến trúc.
           - Các module đã tồn tại.
           - Các điểm tích hợp có thật.
           - Các dữ liệu còn thiếu.
           - Các rủi ro.
           - File dự kiến tạo.
           - Mini Plan.

        5. TASK 01 CHỈ KHẢO SÁT VÀ THIẾT KẾ.

           Không tự viết chức năng
           để khắc phục các module còn thiếu.

        6. DATABASE:

           - Không tự tạo bảng.
           - Không tạo Migration.
           - Không chạy migrate:fresh.
           - Không chạy db:wipe.
           - Không truncate dữ liệu.
           - Không sửa dữ liệu production.
           - Không tự thay đổi Foreign Keys.

        7. SOURCE OF TRUTH:

           - Migration thực tế.
           - Model thực tế.
           - Routes thực tế.
           - Service thực tế.
           - Business Rules đã được duyệt.

           Không dựa trên tên trường suy đoán.

        8. AREA MANAGEMENT:

           - Xác định Model Area thực tế.
           - Xác định Area Routes.
           - Xác định quan hệ Area-Table.
           - Kiểm tra khu vực bị vô hiệu hóa
             hoặc bị xóa được xử lý thế nào.
           - Không sửa Area CRUD.

        9. CAFE TABLE MANAGEMENT:

           - Xác định CafeTable Model.
           - Xác định Table Identifier.
           - Xác định Table Code/Number.
           - Xác định Area Reference.
           - Xác định Capacity.
           - Xác định Table Status.
           - Xác định Maintenance Rules.
           - Không sửa Cafe Table CRUD.

        10. ORDER MANAGEMENT:

            - Kiểm tra Order Model.
            - Kiểm tra Order Items.
            - Kiểm tra quan hệ Table-Order.
            - Kiểm tra Order Lifecycle.
            - Kiểm tra Order Status.
            - Kiểm tra việc thêm món.
            - Kiểm tra khả năng tiếp tục Order.
            - Kiểm tra tạo Order đồng thời.

            Không tự xây dựng Order Service.

        11. ACTIVE ORDER POLICY:

            Phải xác minh:
            - Mỗi bàn được phép có
              bao nhiêu Order chưa hoàn tất?
            - Order nào được xem là đang phục vụ?
            - Có cho phép nhiều hóa đơn/bàn không?
            - Có cho phép tách/gộp đơn không?
            - Có cho phép chuyển bàn không?
            - Có cho phép nhiều nhân viên
              cùng cập nhật một đơn không?

            Chỉ ghi nhận chức năng có thật.

            Không tự thêm tính năng
            tách/gộp/chuyển bàn ngoài yêu cầu.

        12. PAYMENT MANAGEMENT:

            - Kiểm tra Payment Model.
            - Kiểm tra Payment Routes/Services.
            - Xác định Payment Status.
            - Xác định Payment Method.
            - Kiểm tra xử lý thanh toán thất bại.
            - Kiểm tra thanh toán một phần
              nếu hệ thống có hỗ trợ.
            - Kiểm tra hoàn tiền nếu có.

            Không tự tạo Payment Logic.

        13. RECEIPT &amp; INVOICE:

            Phân biệt rõ:
            - Phiếu tạm tính.
            - Biên nhận đã thanh toán.
            - Hóa đơn điện tử theo nghiệp vụ
              và quy định áp dụng nếu có.

            Không mặc định hệ thống
            đã hỗ trợ hóa đơn điện tử.

            Không coi thao tác In
            là bằng chứng thanh toán thành công.

            Phải xác định dữ liệu,
            template và API in hiện có.

        14. RESERVATION:

            - Kiểm tra Reservation Model.
            - Kiểm tra quan hệ với Table.
            - Kiểm tra Reservation Status.
            - Kiểm tra quy tắc giữ bàn.
            - Kiểm tra nhận bàn.
            - Kiểm tra xung đột đặt bàn.

            Không tự thay đổi Reservation Logic.

        15. TABLE STATUS:

            Phải phân biệt:

            A. Persisted Table Status:
               Trạng thái lưu trong database.

            B. Derived POS Display Status:
               Trạng thái suy ra từ Order,
               Payment, Reservation,
               Maintenance và các quy tắc khác.

            Không tự thêm "waiting_payment"
            vào enum/table status.

            Không tự đồng nhất
            Payment Status với Table Status.

        16. STATUS TRANSITIONS:

            Phải xác minh các tình huống:

            - Bàn trống -> Mở đơn.
            - Mở đơn thành công.
            - Mở đơn thất bại.
            - Thêm món vào đơn.
            - Đơn chờ thanh toán.
            - Thanh toán thành công.
            - Thanh toán thất bại.
            - Thanh toán một phần nếu có.
            - Order bị hủy.
            - Bàn có Reservation.
            - Bàn đang bảo trì.

            Chỉ xác nhận trạng thái sau khi
            nghiệp vụ backend cho phép.

        17. TABLE RELEASE POLICY:

            Không tự giả định:
            Thanh toán -> Bàn trống ngay.

            Phải xác minh:
            - Có đơn khác đang mở không?
            - Thanh toán đã hoàn tất chưa?
            - Có Reservation đang hiệu lực không?
            - Có Maintenance Lock không?
            - Có bước dọn bàn thủ công không?

            Nếu chưa có Business Rule,
            báo cáo BLOCKED.

        18. AUTHORIZATION:

            - Sử dụng Auth hiện có của Hợp.
            - Kiểm tra quyền xem khu vực/bàn.
            - Kiểm tra quyền tạo Order.
            - Kiểm tra quyền sửa Order.
            - Kiểm tra quyền Checkout.
            - Kiểm tra quyền In chứng từ.
            - Kiểm tra quyền đổi trạng thái bàn.

            Không tự tạo Role/Permission.

            Không tự sửa Middleware/Policy.

        19. INTEGRATION OWNERSHIP:

            Thành Toàn:
            - Area UI.
            - Table Floor Plan.
            - Table Detail UI.
            - Order UI Integration.
            - Status Presentation.

            Đạt:
            - Order Business Logic.
            - Order Item Persistence.
            - Payment Business Logic.
            - Receipt Backend.

            Ninh:
            - Product Catalog.
            - Product Price.
            - Promotion Rules.

            Hợp:
            - Authentication.
            - Authorization.

            Nếu thiếu API của Đạt/Ninh/Hợp,
            phải tạo Integration Requirement,
            không tự sửa module của họ.

        20. API CONTRACT:

            Không tự tuyên bố API tồn tại.

            Mỗi điểm tích hợp phải có trạng thái:
            - EXISTING.
            - PARTIAL.
            - MISSING.
            - BLOCKED.
            - NEEDS CONFIRMATION.

            Nếu là đề xuất,
            đánh dấu PROPOSED.

        21. DATA CONTRACT:

            Phải xác định tối thiểu:
            - Table ID.
            - Area ID.
            - Order ID.
            - Order Status.
            - Payment Status.
            - Total Amount.
            - Currency.
            - Receipt Reference nếu có.

            Chỉ sử dụng trường thực tế.

            Trường còn thiếu phải nêu rõ.

        22. CONCURRENCY:

            Phải đánh giá:
            - Hai nhân viên chọn cùng bàn.
            - Hai request cùng tạo Order.
            - Hai request cùng thêm món.
            - Hai request cùng Checkout.
            - Trạng thái bàn bị lỗi thời.
            - Double-submit từ UI.

            Đề xuất Idempotency/Locking
            nếu nghiệp vụ cần thiết.

            Không tự triển khai trong TASK 01.

        23. FINANCIAL SAFETY:

            - Không tính lại tiền trong UI
              như nguồn dữ liệu có thẩm quyền.
            - Không cho UI tự quyết định
              Payment đã thành công.
            - Không tự thay đổi giá sản phẩm.
            - Không tự tạo Discount Logic.
            - Tổng tiền chính thức phải đến
              từ backend nghiệp vụ.

        24. PERFORMANCE DESIGN:

            - Tránh N+1 Query cho Table Grid.
            - Không query từng Order
              cho từng Table nếu có cách gộp.
            - Đề xuất Aggregate Queries.
            - Phân tích số lượng dữ liệu cần tải.
            - Không đề xuất polling liên tục
              khi chưa có nhu cầu rõ ràng.

        25. UI DESIGN:

            Thiết kế theo hướng:
            - Area Navigation.
            - Table Status Cards.
            - Table Detail Panel.
            - Order Entry.
            - Checkout.
            - Receipt.
            - Status Refresh.

            Không tạo UI trong TASK 01.

        26. SECURITY:

            - Không bỏ qua Backend Authorization.
            - Không sửa quyền để demo thành công.
            - Không ghi lộ Secret/API Key.
            - Không tự thêm Route không bảo vệ.
            - Không sử dụng dữ liệu production
              để thử thanh toán.

        27. DOCUMENTATION:

            Mọi kết luận cần ghi:
            - File thực tế.
            - Class/Method nếu có.
            - Route nếu có.
            - Schema Evidence nếu có.
            - Trạng thái xác minh.

            Không ghi PASS khi chưa kiểm tra.

        28. Không tự động commit/push/merge.

        29. Không ghi đè thay đổi chưa commit
            của thành viên khác.

        30. Không sửa module ngoài phạm vi.

        31. Nếu chưa đủ dữ liệu,
            ghi UNKNOWN/BLOCKED,
            không tự suy đoán.
    </hardRules>

    <tasks>
        <taskUnit id="01">
            <name>
                Khảo sát Project Architecture
                và hiện trạng các module
            </name>

            <estimatedTime>
                15-20 phút.
            </estimatedTime>

            <steps>
                - Kiểm tra Current Branch.
                - Kiểm tra git status/git diff.
                - Kiểm tra composer.json.
                - Kiểm tra Project Structure.
                - Kiểm tra Area Module.
                - Kiểm tra Cafe Table Module.
                - Kiểm tra Order/POS Module.
                - Kiểm tra Payment Module.
                - Kiểm tra Reservation Module.
                - Kiểm tra Catalog Dependency.
                - Kiểm tra Auth/Authorization.

                - Lập Module Inventory:

                  + Module Name.
                  + Owner.
                  + Model.
                  + Controller.
                  + Service.
                  + Routes.
                  + Views.
                  + Test Coverage.
                  + Completion Status.

                - Không giả định module
                  đã hoàn thành vì tồn tại file.

                - Trình bày hiện trạng
                  và Mini Plan trước khi
                  tiếp tục thiết kế.
            </steps>
        </taskUnit>

        <taskUnit id="02">
            <name>
                Phân tích Database Relationships
                và Business State
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. DATABASE MAPPING:

                - Xác định tên bảng thực tế.
                - Xác định Primary Keys.
                - Xác định Foreign Keys.
                - Xác định Eloquent Relationships.
                - Xác định các Status Fields.

                - Kiểm tra:
                  Area -> Cafe Table.

                  Cafe Table -> Order.

                  Order -> Order Items.

                  Order -> Payment.

                  Cafe Table -> Reservation.

                  Order Items -> Product.

                - Nếu quan hệ không tồn tại,
                  đánh dấu MISSING.

                - Không tự tạo Foreign Key.

                B. STATE ANALYSIS:

                - Liệt kê Table Status thực tế.
                - Liệt kê Order Status thực tế.
                - Liệt kê Payment Status thực tế.
                - Liệt kê Reservation Status thực tế.

                C. STATE TRANSITION TABLE:

                Mỗi trường hợp cần ghi:
                - Event.
                - Current State.
                - Conditions.
                - Backend Source of Truth.
                - Expected Next State.
                - Owner.
                - Verification Status.

                Phân tích tối thiểu:
                - Create Order.
                - Add Order Item.
                - Await Payment.
                - Payment Success.
                - Payment Failure.
                - Order Cancellation.
                - Reservation Conflict.
                - Maintenance Conflict.

                D. IMPORTANT RULES:

                - Không mặc định một bàn
                  chỉ có một Order nếu code
                  không có ràng buộc đó.

                - Không mặc định in chứng từ
                  sẽ giải phóng bàn.

                - Không mặc định trạng thái
                  UI là cột database.

                - Đánh dấu các Business Rules
                  cần nhóm phê duyệt.
            </steps>
        </taskUnit>

        <taskUnit id="03">
            <name>
                Thiết kế POS Workflow
                và Integration Contract
            </name>

            <estimatedTime>
                25-30 phút.
            </estimatedTime>

            <steps>
                A. USER WORKFLOW:

                Thiết kế các luồng:

                FLOW 01:
                Chọn khu vực -> Hiển thị bàn.

                FLOW 02:
                Chọn bàn trống -> Tạo Order.

                FLOW 03:
                Chọn bàn có khách
                -> Mở Order đang phục vụ.

                FLOW 04:
                Chọn món -> Lưu Order Items.

                FLOW 05:
                Tạm tính -> Checkout -> Payment.

                FLOW 06:
                Thanh toán hợp lệ
                -> Xem/In chứng từ.

                FLOW 07:
                Đồng bộ Table Status
                từ dữ liệu nghiệp vụ.

                FLOW 08:
                Xử lý lỗi/Conflict/Retry.

                B. ROUTE/API CONTRACT:

                Với mỗi thao tác, ghi:

                - Use Case.
                - HTTP Method.
                - Named Route/API Path.
                - Controller/Service.
                - Request Fields.
                - Response Fields.
                - Validation.
                - Middleware/Authorization.
                - Error/Conflict Behavior.
                - Owner.
                - EXISTING/PARTIAL/MISSING.
                - Evidence.

                C. CONTRACT REQUIREMENTS:

                Cần xác minh khả năng:

                - List Areas.
                - List Tables by Area.
                - Get Table Status.
                - Get Active Order by Table.
                - Create Order for Table.
                - Add/Update Order Items.
                - Get Order Summary.
                - Start/Complete Checkout.
                - Get Payment Result.
                - Get Printable Receipt.
                - Refresh Table State.

                Đây là danh sách USE CASE,
                không khẳng định API đã có.

                D. INTEGRATION BLOCKERS:

                Với API thiếu, xác định:
                - Owner cần xử lý.
                - Input đề xuất.
                - Output đề xuất.
                - Quy tắc nghiệp vụ cần xác nhận.
                - Mức độ ảnh hưởng.
                - Task phụ thuộc.

                E. SEQUENCE DESIGN:

                Tạo ít nhất một Sequence Diagram
                mô tả:

                Staff -> Area/Table UI
                -> Table Backend
                -> Order Service
                -> Payment Service
                -> Receipt
                -> Table State Refresh.

                Các bước chưa được xác minh
                phải ghi PROPOSED.

                F. CONCURRENCY:

                - Đánh giá Duplicate Order.
                - Đánh giá Double Payment.
                - Đánh giá Stale Table State.
                - Đề xuất cách xử lý xung đột.
                - Không triển khai logic mới.
            </steps>
        </taskUnit>

        <taskUnit id="04">
            <name>
                Thiết kế UI Architecture
                và hoàn thiện tài liệu bàn giao
            </name>

            <estimatedTime>
                15-20 phút.
            </estimatedTime>

            <steps>
                A. UI ARCHITECTURE:

                Đề xuất các thành phần:

                1. Area Overview:
                   - Area Cards.
                   - Area Statistics.
                   - Navigation.

                2. Table Floor Plan:
                   - Table Cards.
                   - Table Code.
                   - Capacity.
                   - Status.
                   - Filters.

                3. Table Detail:
                   - Selected Table.
                   - Area.
                   - Current State.
                   - Active Order.
                   - Available Actions.

                4. Order Panel:
                   - Product Listing.
                   - Item Selection.
                   - Quantity.
                   - Notes.
                   - Order Summary.

                5. Checkout:
                   - Amount Summary.
                   - Payment Action.
                   - Payment Result.

                6. Receipt:
                   - Printable View.
                   - Print Action.
                   - Receipt Data.

                7. State Feedback:
                   - Loading.
                   - Empty State.
                   - Error State.
                   - Conflict State.
                   - Status Refresh.

                B. DATA FLOW:

                Thiết kế luồng dữ liệu:

                Database
                   |
                   v
                Existing Models/Services
                   |
                   v
                Controllers/Read Services
                   |
                   v
                Blade/Frontend Components
                   |
                   v
                User Actions
                   |
                   v
                Existing Authorized Backend
                   |
                   v
                Refresh UI State.

                C. RESPONSIBILITY MATRIX:

                Ghi rõ:

                - Toàn phụ trách phần nào.
                - Đạt phụ trách phần nào.
                - Ninh phụ trách phần nào.
                - Hợp phụ trách phần nào.

                D. HANDOFF TASK 02-09:

                Với từng Task phải ghi:
                - Input Dependencies.
                - Required Services.
                - Proposed Files.
                - Business Rules.
                - Acceptance Criteria.
                - Existing Blockers.

                E. DOCUMENTATION:

                Tạo hoặc cập nhật tài liệu
                khảo sát trong docs/
                nếu dự án cho phép.

                Nội dung phải có:
                - Findings.
                - Database Mapping.
                - Workflow.
                - State Matrix.
                - Integration Contract.
                - Dependency Matrix.
                - UI Architecture.
                - Open Questions.
                - Implementation Roadmap.

                F. FINAL CHECKS:

                - Kiểm tra git status/git diff.
                - Xác nhận không sửa source
                  nghiệp vụ ngoài yêu cầu.
                - Không tạo Migration.
                - Không thay đổi database.
                - Không sửa Auth/Order/Payment.

                - Xuất Final Report.

                - Không commit/push/merge.
            </steps>
        </taskUnit>
    </tasks>

    <acceptanceCriteria>
        1. Đã kiểm tra source code thực tế.

        2. Đã xác định Laravel Version.

        3. Đã khảo sát Area Management.

        4. Đã khảo sát Cafe Table Management.

        5. Đã khảo sát Order/POS Management.

        6. Đã khảo sát Payment Management.

        7. Đã khảo sát Reservation Management.

        8. Đã kiểm tra Catalog Dependency.

        9. Đã kiểm tra Authentication
           và Authorization.

        10. Có Module Inventory.

        11. Có Database Relationship Mapping.

        12. Có danh sách Schema thực tế.

        13. Có danh sách Status thực tế.

        14. Có Table/Order/Payment
            State Transition Matrix.

        15. Có POS-at-Table Workflow.

        16. Có Route/API Inventory.

        17. Có Integration Contract
            cho các thao tác cần thiết.

        18. Mỗi API/Service có trạng thái
            xác minh rõ ràng.

        19. Có phương án tránh
            Duplicate Order.

        20. Có phân tích rủi ro
            Double Payment.

        21. Có phương án xử lý
            Stale Table Status.

        22. Có phân biệt Receipt/Invoice.

        23. Có quy tắc xác định
            khi nào được giải phóng bàn
            hoặc ghi rõ cần xác nhận.

        24. Có Sequence Diagram.

        25. Có UI Component Architecture.

        26. Có trách nhiệm rõ ràng
            cho Thành Toàn/Đạt/Ninh/Hợp.

        27. Có Dependency Matrix.

        28. Có danh sách Missing API.

        29. Có Open Questions/Blockers.

        30. Có tài liệu bàn giao TASK 02-09.

        31. Không tự tạo API nghiệp vụ mới.

        32. Không tự sửa Order/Payment.

        33. Không thay đổi Database Schema.

        34. Không tạo Migration.

        35. Không sửa Authentication.

        36. Không sửa module ngoài phạm vi.

        37. Không tự động commit/push/merge.

        38. Không tuyên bố API tồn tại
            nếu chưa xác minh.

        39. Kết quả khảo sát phải có bằng chứng
            từ source code.

        40. AI Agent phải xuất FINAL REPORT:

            A. PROJECT OVERVIEW
            - Laravel Version.
            - Project Structure.
            - Branch hiện tại.
            - Git Working Tree.
            - Existing Architecture.

            B. MODULE INVENTORY
            - Area.
            - Cafe Table.
            - Order/POS.
            - Payment.
            - Reservation.
            - Catalog.
            - Authentication/Authorization.

            C. DATABASE RELATIONSHIPS
            - Table Names.
            - Primary Keys.
            - Foreign Keys.
            - Eloquent Relationships.
            - Missing Relationships.
            - Schema Dependencies.

            D. ROUTE/API INVENTORY
            - Use Case.
            - HTTP Method.
            - Route Name/Path.
            - Controller/Service.
            - Authorization.
            - Verification Status.
            - Source Evidence.

            E. POS WORKFLOW
            - Area Selection.
            - Table Selection.
            - Open/Create Order.
            - Add Order Items.
            - Checkout.
            - Payment.
            - Receipt.
            - Status Refresh.

            F. TABLE STATE DESIGN
            - Table Status.
            - Order Status.
            - Payment Status.
            - Reservation Status.
            - Derived UI Status.
            - Allowed Transitions.
            - Conflict Rules.

            G. INTEGRATION CONTRACT
            - Input.
            - Output.
            - Validation.
            - Authorization.
            - Error Handling.
            - Transaction Boundary.
            - Owner.
            - EXISTING/PARTIAL/MISSING/
              BLOCKED/PROPOSED.

            H. SECURITY/CONCURRENCY
            - Authorization.
            - Duplicate Order Risk.
            - Double Payment Risk.
            - Stale State Risk.
            - Data Integrity.
            - Pending Decisions.

            I. UI ARCHITECTURE
            - Area Overview.
            - Table Floor Plan.
            - Table Detail.
            - Order Entry.
            - Checkout.
            - Receipt.
            - Status Feedback.

            J. RESPONSIBILITY MATRIX
            - Thành Toàn.
            - Đạt.
            - Ninh.
            - Hợp.
            - Integration Dependencies.

            K. NEXT TASK HANDOFF
            - TASK 02 Requirements.
            - TASK 03 Requirements.
            - TASK 04 Requirements.
            - TASK 05 Requirements.
            - TASK 06 Requirements.
            - TASK 07 Requirements.
            - TASK 08 Requirements.
            - TASK 09 Requirements.

            L. FILE CHANGES
            - File tài liệu được tạo.
            - File được chỉnh sửa.
            - Xác nhận không sửa source
              ngoài phạm vi khảo sát.

            M. VALIDATION RESULTS
            - Hạng mục khảo sát.
            - Evidence.
            - VERIFIED.
            - PARTIAL.
            - MISSING.
            - BLOCKED.
            - NOT CHECKED.

            N. FINAL STATUS
            - ARCHITECTURE READY
              hoặc NOT READY.
            - Những API còn thiếu.
            - Những Business Rules chưa rõ.
            - Những việc cần nhóm xác nhận.
            - Đề xuất thứ tự thực hiện.

        41. Chỉ đánh dấu TASK 01 hoàn thành
            khi báo cáo khảo sát đã đầy đủ,
            các phụ thuộc được làm rõ
            và có tài liệu đủ để triển khai
            những TASK tiếp theo.

        42. Nếu thiếu API hoặc Business Rule,
            không được tự giả định đã sẵn sàng
            cho toàn bộ luồng POS.

        43. Không tuyên bố đã triển khai
            chức năng POS tại bàn
            trong TASK 01.
    </acceptanceCriteria>
</prompt>
```