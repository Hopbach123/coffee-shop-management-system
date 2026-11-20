```xml
<?xml version="1.0" encoding="UTF-8"?>
<prompt>
    <goal>
        Xây dựng Table Floor Plan UI
        và Table Status Cards cho dự án
        Coffee Shop Management System.

        TASK:
        03 - Table Floor Plan UI
        &amp; Status Cards.

        THỜI GIAN:
        60-90 phút.

        BRANCH:
        feature/area-table-pos-ui.

        MODULE:
        Area &amp; Table POS Management.

        MỤC TIÊU CHÍNH:

        1. Khảo sát kết quả TASK 01 và TASK 02.

        2. Khảo sát Cafe Table Management
           và Area Management hiện có.

        3. Xây dựng trang sơ đồ bàn
           theo phong cách Modern Coffee POS.

        4. Hiển thị danh sách bàn bằng
           Responsive Grid Cards.

        5. Mỗi Table Card phải có:
           - Mã hoặc số bàn.
           - Tên khu vực.
           - Sức chứa.
           - Trạng thái hiện tại.
           - Icon trạng thái.
           - Hành động chọn/xem bàn
             nếu route phù hợp đã tồn tại.

        6. Hiển thị danh sách khu vực
           dưới dạng Tabs hoặc Filter.

        7. Cho phép chuyển nhanh giữa
           những khu vực đã tồn tại.

        8. Xây dựng bộ lọc:
           - Theo khu vực.
           - Theo trạng thái bàn.
           - Theo sức chứa nếu phù hợp.

        9. Xây dựng tìm kiếm bàn:
           - Theo mã bàn.
           - Theo tên bàn nếu có.
           - Theo các trường đã xác minh.

        10. Hiển thị các thẻ thống kê:
            - Tổng số bàn.
            - Bàn trống.
            - Bàn có khách.
            - Bàn đã đặt trước.
            - Bàn bảo trì.
            - Trạng thái khác nếu
              nghiệp vụ có hỗ trợ.

        11. Phân biệt trạng thái bằng
            cả màu sắc, icon và nhãn.

        12. Chuẩn bị điều hướng khi
            người dùng nhấn vào bàn.

        13. Xử lý trạng thái:
            - Loading.
            - Empty.
            - Error.
            - No Results.
            - Unauthorized.

        14. Thiết kế Responsive:
            - Desktop.
            - Tablet.
            - Mobile.

        15. Tối ưu truy vấn dữ liệu
            và tránh N+1 Queries.

        16. Kiểm thử chức năng,
            giao diện và phân quyền.

        KẾT QUẢ MONG MUỐN:

        Có màn hình Table Floor Plan
        hiện đại, đẹp và dễ thao tác.

        Dữ liệu bàn được đọc từ MySQL.

        Mỗi bàn có thông tin và trạng thái
        chính xác theo nguồn đã xác minh.

        Người dùng có thể:
        - Chọn khu vực.
        - Xem bàn trong khu vực.
        - Tìm kiếm mã bàn.
        - Lọc trạng thái bàn.
        - Nhận biết bàn đang sử dụng.
        - Chọn bàn theo luồng được phép.

        Không hardcode danh sách bàn.

        Không phát sinh Migration.

        Không tạo Order trong TASK này.

        Không thay đổi trạng thái bàn
        khi chỉ nhấn vào Table Card.

        Không triển khai Checkout/Payment.
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
        - HTML5.
        - CSS3.
        - JavaScript hiện có.

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

        ĐẠT:
        - POS.
        - Order.
        - Payment.

        NINH:
        - Catalog.
        - Promotion.

        HỢP:
        - Authentication.
        - Authorization.
        - Inventory.

        BUSINESS REQUIREMENTS:

        Người dùng muốn quản lý khu vực
        và bàn tương tự hệ thống POS,
        nhưng giao diện hiện đại hơn.

        Các khu vực phải hiển thị rõ ràng.

        Bàn phải được biểu diễn trực quan.

        Nhân viên dễ dàng nhận biết:
        - Bàn đang trống.
        - Bàn đang phục vụ.
        - Bàn đã đặt trước.
        - Bàn không thể sử dụng.
        - Trạng thái khác nếu có.

        Giao diện phải sẵn sàng
        cho việc chọn bàn để Order
        trong TASK 04-05.

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

        Các bảng dự kiến:
        - areas.
        - cafe_tables.

        Đây là tên tham khảo,
        không phải schema đã xác minh.

        AI Agent phải kiểm tra:
        - Migration thực tế.
        - Model Area.
        - Model CafeTable.
        - Quan hệ Area-Table.
        - Table Identifier.
        - Table Code.
        - Table Name nếu có.
        - Capacity.
        - Table Status.
        - Soft Delete nếu có.

        STATUS DỰ KIẾN:

        - available.
        - occupied.
        - reserved.
        - maintenance.

        Những giá trị này chỉ tham khảo
        từ thiết kế trước đó.

        Chỉ sử dụng trạng thái thực tế
        được TASK 01 và source code xác nhận.

        Phải phân biệt:

        Persisted Table Status:
        Giá trị lưu trong database.

        Derived POS Display Status:
        Giá trị tính từ Order/Payment/
        Reservation theo chính sách
        đã được phê duyệt.

        Không tự suy diễn trạng thái
        chờ thanh toán từ Order.

        Nếu TASK 01 chưa xác minh
        Derived Status, chỉ sử dụng
        Table Status hiện có.

        UI DESIGN DIRECTION:

        Modern Coffee POS Floor Plan.

        Đặc điểm:
        - Clean Dashboard.
        - Area Tabs.
        - Modern Table Cards.
        - Status Summary.
        - Search/Filters.
        - Consistent Typography.
        - Responsive Grid.
        - Clear Visual Hierarchy.

        Lấy cảm hứng từ trải nghiệm POS,
        không sao chép nguyên bản
        thiết kế hoặc tài sản của Sapo.

        QUAN TRỌNG:

        TASK 03 chỉ xây dựng
        sơ đồ bàn và chọn bàn.

        TASK 04 mới xử lý:
        - Table Detail.
        - Current Order Lookup.
        - Create/Open Order Entry.

        TASK 05 xử lý gọi món.

        TASK 06 xử lý Payment/Receipt.

        TASK 07 xử lý đồng bộ
        trạng thái theo nghiệp vụ.

        Không xây dựng các chức năng này
        trước trong TASK 03.
    </context>

    <scope>
        PHẠM VI ĐƯỢC PHÉP:

        1. SOURCE CODE SURVEY:

           - Area Model.
           - CafeTable Model.
           - Area Relationships.
           - Cafe Table Controller.
           - Cafe Table Routes.
           - Existing Table Views.
           - Admin Layout.
           - Authorization.
           - TASK 01-02 Documentation.

        2. FLOOR PLAN PAGE:

           - Page Header.
           - Area Navigation.
           - Status Statistics.
           - Table Grid.
           - Table Cards.
           - Search/Filter Controls.
           - Empty/Error States.

        3. AREA NAVIGATION:

           - All Areas nếu phù hợp.
           - Danh sách Area thật.
           - Chọn Area.
           - Đếm bàn theo Area.
           - Đồng bộ với TASK 02.
           - Giữ Area đang chọn
             nếu thiết kế cho phép.

        4. TABLE CARDS:

           - Table Code/Number.
           - Area Name.
           - Capacity.
           - Current Status.
           - Status Label.
           - Status Icon.
           - Allowed Navigation Action.

        5. TABLE FILTERING:

           - Search by Code/Name.
           - Filter by Area.
           - Filter by Status.
           - Capacity Filter nếu phù hợp.
           - Clear Filters.
           - No Results State.

        6. DATA ACCESS:

           - Database Queries.
           - Eloquent Relationships.
           - Status Counts.
           - Sorting.
           - Pagination/Chunking
             nếu quy mô yêu cầu.
           - Avoid N+1 Queries.

        7. RESPONSIVE UI:

           - Desktop Grid.
           - Tablet Grid.
           - Mobile Grid.
           - Keyboard Accessibility.
           - Touch-friendly Interaction.

        8. TESTING:

           - Table Listing.
           - Area Filtering.
           - Status Filtering.
           - Search.
           - Statistics.
           - Authorization.
           - Navigation.
           - Responsive.
           - Regression.

        FILE DỰ KIẾN:

        - app/Models/Area.php

        - app/Models/CafeTable.php

        - app/Http/Controllers/Admin/
          CafeTableController.php

        - app/Http/Controllers/Admin/
          TableFloorPlanController.php

        - resources/views/admin/tables/
          floor-plan.blade.php

        - resources/views/admin/tables/
          components/table-card.blade.php

        - resources/views/admin/tables/
          components/status-summary.blade.php

        - resources/css/

        - resources/js/

        - routes/web.php

        - tests/Feature/
          TableFloorPlanTest.php

        Các file chỉ mang tính đề xuất.

        BẮT BUỘC kiểm tra file thực tế.

        Không tạo Controller/Route trùng.

        KHÔNG TRIỂN KHAI:

        - Order Creation.
        - Order Modification.
        - Order Items Management.
        - Product Selection.
        - Payment.
        - Receipt Printing.
        - Table Release Logic.
        - New Status Synchronization.
        - Reservation Business Logic.
        - Database Migration.
        - Database Schema Changes.
        - Real-time WebSocket.
        - Table Drag-and-Drop Layout Editor.
        - Chuyển/Gộp/Tách bàn.
        - Refactor toàn bộ Admin.
    </scope>

    <hardRules>
        1. BẮT BUỘC khảo sát source code
           trước khi viết hoặc sửa code.

        2. Kiểm tra:
           - Current Branch.
           - git status.
           - git diff.
           - TASK 01 Report.
           - TASK 02 Implementation.
           - Area Model.
           - CafeTable Model.
           - Model Relationships.
           - Table Status.
           - Table Code/Capacity.
           - Existing Controllers.
           - Existing Routes.
           - Existing Views.
           - Authorization.
           - Existing Tests.

        3. TRƯỚC KHI CODE:

           AI Agent phải trình bày:
           - Hiện trạng Cafe Table UI.
           - Schema thực tế.
           - Các Status được hỗ trợ.
           - Area-Table Relationship.
           - Data Contract.
           - Route có thể tái sử dụng.
           - Quy tắc chọn bàn.
           - Các blocker.
           - File dự kiến thay đổi.
           - Mini Plan.

        4. Không tự tạo Migration.

        5. Không thay đổi Database Schema.

        6. Không tự cài package UI mới
           khi chưa được phê duyệt.

        7. Không chỉnh sửa nghiệp vụ:
           - Order của Đạt.
           - Payment của Đạt.
           - Catalog của Ninh.
           - Authentication của Hợp.
           - Authorization của Hợp.
           - Reservation hiện có.

        8. DATA SOURCE:

           - Danh sách bàn từ MySQL.
           - Danh sách khu vực từ MySQL.
           - Không hardcode Table Code.
           - Không hardcode Area Name.
           - Không tạo số liệu thống kê giả.
           - Không seed dữ liệu production.
           - Không sử dụng dữ liệu mock
             để làm kết quả nghiệm thu.

        9. TABLE IDENTITY:

           - Dùng ID thật để định danh bàn.
           - Table Code/Number chỉ hiển thị.
           - Không tự dùng Table Code
             làm khóa chính.
           - Nếu mã bàn không duy nhất
             toàn hệ thống, phải xác định
             thêm Area theo schema.
           - Không tạo liên kết sai bàn.

        10. AREA FILTER:

            - Dùng ID Area thật.
            - Không lọc bằng chuỗi tên
              khi có khóa quan hệ.
            - Không làm xuất hiện bàn
              ngoài khu vực đang chọn.
            - Xử lý Area không tồn tại.
            - Kiểm tra Authorization.

        11. TABLE CARD DATA:

            Chỉ hiển thị dữ liệu đã xác minh:

            - Table Code.
            - Table Name nếu có.
            - Capacity.
            - Area.
            - Status.

            Nếu dữ liệu không tồn tại:
            - Không tự bịa.
            - Không hiển thị giá trị giả.
            - Hiển thị fallback hợp lý
              hoặc bỏ thuộc tính tùy chọn.

        12. STATUS MAPPING:

            - Dùng các trạng thái được duyệt.
            - Không tự đổi giá trị Status.
            - Không tự tạo trạng thái database.
            - Mỗi trạng thái phải có Label.
            - Mỗi trạng thái có Icon phù hợp.
            - Sử dụng màu nhất quán.
            - Không chỉ dùng màu sắc.

        13. STATUS PRIORITY:

            Nếu TASK 01 đã xác định
            Derived Display Status:

            - Áp dụng đúng thứ tự ưu tiên.
            - Không tự suy diễn Order Status.
            - Không tự coi Payment Success
              là điều kiện duy nhất
              để bàn trở thành Available.
            - Không bỏ qua Reservation
              hoặc Maintenance.

            Nếu chưa có State Policy:
            - Hiển thị Persisted Status.
            - Ghi blocker cho trạng thái
              tổng hợp chưa xác định.

        14. STATUS STATISTICS:

            - Đếm bàn theo Data Contract.
            - Không đếm trùng.
            - Không dùng các nhóm
              trạng thái giao nhau
              như nhóm độc lập nếu
              không có định nghĩa rõ.
            - Tổng thống kê phải nhất quán
              với phạm vi lọc được ghi rõ.
            - Không tự coi NULL Status
              là Available.
            - Không báo 0 giả khi thiếu dữ liệu.

        15. TABLE SORTING:

            - Sử dụng thứ tự theo policy.
            - Ưu tiên Table Code/Number
              hoặc thuộc tính thứ tự thật.
            - Nếu mã có dạng B1, B2, B10,
              cân nhắc natural ordering
              nếu yêu cầu và có thể thực hiện.
            - Có tie-breaker ổn định.
            - Không phụ thuộc thứ tự
              trả về ngẫu nhiên của database.
            - Không tự thêm cột sort_order.

        16. SEARCH:

            - Search theo trường có thật.
            - Trim input hợp lệ.
            - Giới hạn độ dài tìm kiếm.
            - Không xây dựng SQL unsafe.
            - Search phải kết hợp đúng
              với Area/Status Filters.
            - Có thông báo không tìm thấy.
            - Có Clear Search.

        17. FILTERING:

            - Chỉ cho phép giá trị hợp lệ.
            - Không tin vào Filter từ client.
            - Không đưa Status không tồn tại
              thành lựa chọn hoạt động.
            - Kết quả sau Filter
              phải đúng phạm vi được phép.
            - Giữ các Filter khi điều hướng
              hoặc phân trang nếu phù hợp.

        18. TABLE CLICK:

            - Nhấn Table Card không được
              trực tiếp sửa Database.
            - Không tự tạo Order.
            - Không tự đổi Status.
            - Không tự chiếm bàn.
            - Không tự mở Payment.

            Nếu Table Detail Route của TASK 04
            chưa tồn tại:
            - Không tạo href giả.
            - Không tạo Order Route mới.
            - Không mở Modal Order giả
              làm như đã hoạt động.
            - Có thể chọn bàn ở UI
              nếu hành vi read-only hợp lệ,
              hoặc hiển thị trạng thái
              chờ tích hợp.
            - Ghi Integration Dependency.

            Nếu đã có Route Detail hợp lệ:
            - Dùng đúng Table ID.
            - Kiểm tra quyền điều hướng.
            - Không thay đổi nghiệp vụ Route.

        19. MAINTENANCE TABLE:

            - Phải phân biệt trạng thái bảo trì.
            - Không hiển thị nút tạo Order
              nếu policy không cho phép.
            - Không tự sửa Status.
            - Không dùng CSS disabled
              thay cho Backend Authorization.

        20. RESERVATION TABLE:

            - Hiển thị trạng thái
              đã đặt trước theo nguồn xác minh.
            - Không tự chuyển sang Occupied.
            - Không tự xác nhận Reservation.
            - Không tạo Reservation Logic mới.

        21. AUTHORIZATION:

            - Chỉ người có quyền
              xem sơ đồ bàn mới được truy cập.
            - Tái sử dụng Auth hiện có.
            - Không tạo Role mới.
            - Không tạo Permission mới.
            - Không sửa Middleware.
            - Backend phải kiểm tra quyền.
            - Không chỉ ẩn Button ở UI.

        22. QUERY PERFORMANCE:

            - Tránh N+1 Queries.
            - Không query Area riêng
              cho từng Table.
            - Không query Order riêng
              cho từng Table nếu không cần.
            - Ưu tiên eager loading
              hoặc aggregate query phù hợp.
            - Không tải toàn bộ dữ liệu
              Order/Payment khi chưa cần.
            - Không thêm polling tự động
              trong TASK 03.

        23. DESIGN SYSTEM:

            - Tái sử dụng Admin Layout.
            - Tái sử dụng Typography.
            - Tái sử dụng Colors.
            - Tái sử dụng Spacing.
            - Tái sử dụng Icons hiện có.
            - Thiết kế riêng có tính thẩm mỹ.
            - Không sao chép nguyên mẫu Sapo.

        24. FLOOR PLAN DESIGN:

            PAGE HEADER:
            - Title.
            - Short Description.
            - Summary.

            AREA NAVIGATION:
            - Area Tabs.
            - Active Area.
            - Area Counts nếu có.

            FILTER BAR:
            - Search.
            - Area Filter.
            - Status Filter.
            - Capacity Filter nếu phù hợp.

            TABLE GRID:
            - Responsive Table Cards.
            - Clear Table Number.
            - Capacity.
            - Status.
            - Area Context.

        25. TABLE CARD UX:

            - Mã bàn là thông tin nổi bật.
            - Nhãn trạng thái dễ đọc.
            - Không làm Card quá rối.
            - Dễ nhận biết bàn được chọn.
            - Dễ thao tác bằng cảm ứng.
            - Có Hover/Focus State.
            - Không hiển thị nút thao tác
              chưa có chức năng.

        26. RESPONSIVE:

            Desktop:
            - Grid nhiều cột.
            - Filter rõ ràng.
            - Area Tabs dễ chuyển.

            Tablet:
            - Grid tự thích ứng.
            - Card đủ kích thước.
            - Không tràn ngang.

            Mobile:
            - Card dễ chạm.
            - Filter không bị che.
            - Không mất mã bàn.
            - Không tràn ngang.
            - Không che nội dung chính.

        27. ACCESSIBILITY:

            - Card tương tác dùng
              Link/Button phù hợp.
            - Card không tương tác
              không giả làm Button.
            - Focus rõ ràng.
            - Keyboard Navigation.
            - Không chỉ dùng màu
              để thể hiện trạng thái.
            - Text Contrast phù hợp.

        28. EMPTY/ERROR STATES:

            - Chưa có Area.
            - Area không có Table.
            - Không có kết quả Search.
            - Filter không có kết quả.
            - Database Error.
            - Route Detail chưa có.
            - Thiếu quyền truy cập.

            Không hiển thị SQL Error thô.

        29. DATABASE SAFETY:

            - TASK 03 chỉ đọc dữ liệu
              trong luồng hiển thị.
            - Không INSERT/UPDATE/DELETE.
            - Không chạy db:wipe.
            - Không chạy migrate:fresh
              trên database dùng chung.
            - Tests dùng database riêng.
            - Không tạo dữ liệu production.

        30. REGRESSION:

            - Giữ nguyên Area CRUD.
            - Giữ nguyên Cafe Table CRUD.
            - Giữ nguyên Admin Layout.
            - Không sửa Reservation.
            - Không sửa Order/Payment.
            - Không phá Routes hiện có.

        31. GIT SAFETY:

            - Không tự động commit.
            - Không tự động push.
            - Không tự động merge.
            - Không ghi đè thay đổi
              chưa commit của người khác.
            - Không đổi branch khi chưa an toàn.

        32. TEST INTEGRITY:

            - PASS khi test đã chạy thành công.
            - FAIL khi kiểm thử phát hiện lỗi.
            - BLOCKED khi thiếu dependency.
            - NOT RUN khi chưa thực hiện.
            - Không tự báo đã xem browser
              nếu không có browser.
            - Không thay dữ liệu giả
              để tạo kết quả đẹp.

        33. SCOPE BOUNDARY:

            - Không triển khai Order Entry.
            - Không triển khai Product Selection.
            - Không triển khai Checkout.
            - Không triển khai Receipt.
            - Không triển khai Status Sync mới.
            - Không tự tạo Modal POS.
            - Không phát triển tính năng
              ngoài TASK 03.
    </hardRules>

    <tasks>
        <taskUnit id="01">
            <name>
                Khảo sát Cafe Table
                và Floor Plan Architecture
            </name>

            <estimatedTime>
                10-15 phút.
            </estimatedTime>

            <steps>
                - Kiểm tra branch hiện tại.
                - Kiểm tra git status/git diff.
                - Đọc TASK 01 Architecture.
                - Đọc TASK 02 Area UI.
                - Kiểm tra Area Model.
                - Kiểm tra CafeTable Model.
                - Kiểm tra Schema.
                - Kiểm tra Relationships.
                - Kiểm tra Table Controller.
                - Kiểm tra Routes.
                - Kiểm tra Admin Layout.
                - Kiểm tra Authorization.
                - Kiểm tra Status Mapping.
                - Kiểm tra UI Components hiện có.

                - Xác định:
                  + Table Identity.
                  + Area Navigation.
                  + Status Source.
                  + Sorting Policy.
                  + Table Detail Dependency.
                  + File dự kiến thay đổi.

                - Trình bày Mini Plan
                  trước khi code.
            </steps>
        </taskUnit>

        <taskUnit id="02">
            <name>
                Xây dựng Floor Plan Data Query,
                Statistics và Filters
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. DATA ACCESS:

                - Tái sử dụng Controller
                  hoặc tạo Read Controller
                  nếu thực sự cần.

                - Đọc Area hợp lệ.

                - Đọc Table hợp lệ.

                - Lấy Area Relationship.

                - Lấy Table Code/Name.

                - Lấy Capacity.

                - Lấy Status.

                - Không query thừa.

                B. STATISTICS:

                - Total Tables.

                - Available Tables nếu có.

                - Occupied Tables nếu có.

                - Reserved Tables nếu có.

                - Maintenance Tables nếu có.

                - Nhóm trạng thái khác
                  nếu backend hỗ trợ.

                - Xác định rõ thống kê
                  đang áp dụng toàn quán
                  hay khu vực đã lọc.

                C. FILTERS:

                - Area Filter.

                - Status Filter.

                - Search by Table Code.

                - Capacity Filter nếu cần.

                - Kết hợp Filter hợp lệ.

                - Clear Filters.

                D. PERFORMANCE:

                - Kiểm tra N+1 Query.

                - Sử dụng eager loading
                  hoặc aggregate query phù hợp.

                - Không tải Order/Payment
                  nếu chưa có Data Contract.

                E. TESTS:

                - Total Tables chính xác.

                - Count by Area chính xác.

                - Count by Status chính xác.

                - Search chính xác.

                - Filter kết hợp chính xác.

                - Không dùng dữ liệu giả.
            </steps>
        </taskUnit>

        <taskUnit id="03">
            <name>
                Xây dựng Modern Table Floor Plan UI
            </name>

            <estimatedTime>
                25-30 phút.
            </estimatedTime>

            <steps>
                A. PAGE HEADER:

                - Title: Sơ đồ bàn.

                - Mô tả ngắn.

                - Thẻ thống kê tổng quan.

                B. AREA NAVIGATION:

                - Tabs hoặc Segmented Controls.

                - Hiển thị Area Name.

                - Active Area rõ ràng.

                - Điều hướng giữa Area.

                - Không hardcode Area.

                C. FILTER BAR:

                - Search Input.

                - Status Filter.

                - Capacity Filter nếu phù hợp.

                - Clear Filters.

                D. TABLE GRID:

                - Responsive Grid.

                - Table Card.

                - Table Code/Number nổi bật.

                - Capacity.

                - Area Label.

                - Status Icon.

                - Status Badge/Text.

                - Selected State nếu có.

                E. TABLE STATUS STYLING:

                Thiết kế nhất quán cho
                những trạng thái đã xác minh.

                Gợi ý hiển thị:

                Available:
                - Icon phù hợp.
                - Nhãn "Trống".

                Occupied:
                - Icon phù hợp.
                - Nhãn "Có khách".

                Reserved:
                - Icon phù hợp.
                - Nhãn "Đã đặt".

                Maintenance:
                - Icon phù hợp.
                - Nhãn "Bảo trì".

                Không bắt buộc sử dụng
                các giá trị trạng thái này
                nếu database không hỗ trợ.

                F. TABLE SELECTION:

                - Xác định hành vi chọn bàn.

                - Chỉ dùng Route đã tồn tại.

                - Không tạo Order tự động.

                - Không thay đổi Table Status.

                - Nếu Table Detail chưa có,
                  ghi rõ phụ thuộc TASK 04.

                G. UI STATES:

                - Empty Area.

                - No Tables.

                - No Search Results.

                - Loading nếu có Async.

                - Error State.

                - Unauthorized State.

                H. RESPONSIVE:

                - Desktop.

                - Tablet.

                - Mobile.

                - Touch-friendly Cards.

                - Accessible Controls.

                - Không overflow ngang.
            </steps>
        </taskUnit>

        <taskUnit id="04">
            <name>
                Kiểm thử, sửa lỗi và nghiệm thu
            </name>

            <estimatedTime>
                15-20 phút.
            </estimatedTime>

            <steps>
                A. FUNCTIONAL TESTS:

                - Hiển thị danh sách Table.

                - Table không có Area
                  nếu schema cho phép.

                - Area không có Table.

                - Table Code hợp lệ.

                - Table Capacity hợp lệ.

                - Table Status hợp lệ.

                - Search by Code.

                - Filter by Area.

                - Filter by Status.

                - Clear Filters.

                - Multiple Filters.

                B. STATISTICS TESTS:

                - Total Tables.

                - Available Count.

                - Occupied Count.

                - Reserved Count.

                - Maintenance Count.

                - Count per Area.

                - Không đếm trùng.

                C. NAVIGATION TESTS:

                - Area Tabs.

                - Active Area.

                - Table Selection.

                - Valid Detail Route nếu có.

                - Không có broken link.

                - Không tự tạo Order.

                D. SECURITY TESTS:

                - Admin có quyền.

                - Staff có quyền phù hợp.

                - User không có quyền.

                - Guest.

                - Backend Authorization.

                - Blade Output Escaping.

                E. UI TESTS:

                - Desktop.

                - Tablet.

                - Mobile.

                - Card Layout.

                - Card Status.

                - Touch Interaction.

                - Keyboard Focus.

                - Empty/Error States.

                F. PERFORMANCE:

                - Query Count nếu đo được.

                - N+1 Review.

                - Filter Performance.

                - Không query Order
                  riêng cho mỗi bàn.

                G. REGRESSION:

                - TASK 01 Architecture.

                - TASK 02 Area UI.

                - Area CRUD.

                - Cafe Table CRUD.

                - Admin Layout.

                - Authentication/Authorization.

                H. FINAL CHECKS:

                - Chạy Feature Tests.

                - Kiểm tra Artisan route:list.

                - Kiểm tra PHP Syntax.

                - Kiểm tra Blade Rendering.

                - Kiểm tra Asset Build nếu có.

                - Kiểm tra git diff/git status.

                - Ghi:
                  PASS/FAIL/BLOCKED/NOT RUN.

                - Không tự động commit/push/merge.
            </steps>
        </taskUnit>
    </tasks>

    <acceptanceCriteria>
        1. Đã khảo sát TASK 01-02.

        2. Đã xác minh Area/CafeTable Schema.

        3. Có trang Table Floor Plan.

        4. Trang sử dụng Admin Layout hiện có.

        5. Bàn được lấy từ MySQL.

        6. Khu vực được lấy từ MySQL.

        7. Hiển thị Table Cards dạng Grid.

        8. Hiển thị mã/số bàn chính xác.

        9. Hiển thị khu vực chính xác.

        10. Hiển thị sức chứa nếu có.

        11. Hiển thị trạng thái đúng nguồn.

        12. Không tự tạo trạng thái mới.

        13. Không hardcode Table/Area Data.

        14. Có Area Navigation.

        15. Có Active Area State.

        16. Có tìm kiếm bàn.

        17. Có Filter theo Area.

        18. Có Filter theo Status.

        19. Có Clear Filters.

        20. Có thống kê tổng số bàn.

        21. Có thống kê trạng thái hợp lệ.

        22. Không đếm trùng bàn.

        23. Có Empty State.

        24. Có No Results State.

        25. Có Error Handling.

        26. Không sử dụng Route giả.

        27. Có Table Selection phù hợp
            với route hiện có nếu được hỗ trợ.

        28. Không tạo Order khi chọn bàn.

        29. Không thay đổi trạng thái bàn
            khi chỉ mở sơ đồ.

        30. Không thay đổi Area CRUD.

        31. Không thay đổi Cafe Table CRUD.

        32. Không sửa Order/Payment.

        33. Không sửa Reservation Logic.

        34. Không sửa Authentication/Authorization.

        35. Có Backend Authorization.

        36. Có Responsive Desktop.

        37. Có Responsive Tablet.

        38. Có Responsive Mobile.

        39. Không overflow nghiêm trọng.

        40. Có Accessibility cơ bản.

        41. Không có N+1 Query nghiêm trọng.

        42. Không tạo Migration.

        43. Không thay đổi Database Schema.

        44. Tests dùng môi trường an toàn.

        45. Không tự động commit/push/merge.

        46. Không báo PASS khi chưa chạy test.

        47. AI Agent phải xuất FINAL REPORT:

            A. MODULE OVERVIEW
            - Chức năng hoàn thành.
            - Chức năng chưa hoàn thành.
            - Blocker còn tồn tại.

            B. SOURCE CODE REVIEW
            - Area Model.
            - CafeTable Model.
            - Relationships.
            - Routes.
            - Admin Layout.
            - Authorization.

            C. FLOOR PLAN ARCHITECTURE
            - Data Flow.
            - Controller/Service.
            - Blade Components.
            - Area Navigation.
            - Table Card Structure.

            D. TABLE DATA CONTRACT
            - Table ID.
            - Table Code/Number.
            - Area ID.
            - Area Name.
            - Capacity.
            - Persisted Status.
            - Derived Status nếu được duyệt.
            - Các trường còn thiếu.

            E. STATUS DESIGN
            - Supported Status Values.
            - Status Labels.
            - Status Icons.
            - Display Priority nếu có.
            - Unknown Status Handling.

            F. FILTERING &amp; SEARCH
            - Area Filter.
            - Status Filter.
            - Search.
            - Clear Filters.
            - Sorting.
            - Combined Filters.

            G. STATISTICS
            - Total Tables.
            - Count by Area.
            - Count by Status.
            - Query Strategy.
            - Verification Results.

            H. UI IMPLEMENTATION
            - Page Header.
            - Area Tabs.
            - Filter Bar.
            - Table Cards.
            - Empty/Error States.
            - Table Selection Behavior.

            I. RESPONSIVE &amp; ACCESSIBILITY
            - Desktop.
            - Tablet.
            - Mobile.
            - Keyboard Navigation.
            - Accessibility Issues.

            J. PERFORMANCE
            - Query Count nếu đo được.
            - N+1 Analysis.
            - Data Loading Strategy.
            - Remaining Bottlenecks.

            K. SECURITY
            - Authentication.
            - Authorization.
            - Output Escaping.
            - Safe Navigation.
            - Unauthorized Access Tests.

            L. FILE CHANGES
            - File tạo mới.
            - File chỉnh sửa.
            - Mục đích thay đổi.
            - Xác nhận không sửa ngoài scope.

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
            - TASK 01.
            - TASK 02.
            - Area CRUD.
            - Cafe Table CRUD.
            - Admin Layout.
            - Authentication/Authorization.

            O. NEXT TASK HANDOFF
            - Table Selection Contract.
            - Selected Table Identifier.
            - Detail Route Availability.
            - Table Status Source.
            - Integration Dependencies.
            - Các yêu cầu cho TASK 04.

            P. FINAL STATUS
            - READY FOR REVIEW
              hoặc NOT READY FOR REVIEW.
            - Blocker còn lại.
            - Những việc cần xác nhận.

        48. Chỉ đánh dấu TASK 03 hoàn thành
            khi Floor Plan hoạt động
            với dữ liệu đã xác minh,
            tìm kiếm/lọc chính xác,
            giao diện được kiểm thử
            và không phá các module liên quan.

        49. Nếu Table Detail Route chưa có,
            có thể hoàn thành Floor Plan
            nhưng phải ghi rõ Dependency
            cần xử lý trong TASK 04.

        50. Không tuyên bố chức năng
            gọi món, thanh toán hoặc in
            hóa đơn đã hoàn thành
            trong TASK 03.
    </acceptanceCriteria>
</prompt>
```