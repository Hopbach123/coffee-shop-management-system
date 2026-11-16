```xml
<?xml version="1.0" encoding="UTF-8"?>
<prompt>
    <goal>
        Nâng cấp giao diện Area Management UI
        cho Coffee Shop Management System.

        TASK:
        02 - Modern Area Management UI.

        MODULE:
        Area &amp; Table POS Management.

        BRANCH:
        feature/area-table-pos-ui.

        THỜI GIAN:
        60-90 phút.

        ĐỘ KHÓ:
        D4.

        MỤC TIÊU CHÍNH:

        1. Khảo sát Area Management hiện tại.

        2. Đọc kết quả TASK 01:
           Architecture &amp; Integration Planning.

        3. Xây dựng giao diện quản lý khu vực
           theo phong cách Modern Coffee POS.

        4. Hiển thị các khu vực bằng
           Area Cards trực quan.

        5. Mỗi Area Card hiển thị:
           - Tên khu vực.
           - Mã khu vực nếu có.
           - Tổng số bàn.
           - Số bàn đang trống.
           - Số bàn có khách.
           - Số bàn đã đặt trước.
           - Số bàn bảo trì.
           - Trạng thái khu vực nếu có.
           - Các thao tác quản lý hợp lệ.

        6. Xây dựng các thẻ thống kê
           tổng quan toàn bộ khu vực.

        7. Bổ sung bộ lọc và tìm kiếm:
           - Tên khu vực.
           - Mã khu vực nếu có.
           - Trạng thái nếu schema hỗ trợ.
           - Sắp xếp theo chính sách có sẵn.

        8. Giữ và tích hợp các chức năng
           Area CRUD đã tồn tại:
           - Danh sách.
           - Thêm.
           - Chỉnh sửa.
           - Xóa an toàn.

        9. Bổ sung thao tác:
           "Xem bàn trong khu vực".

        10. Chuẩn bị điều hướng đến
            Table Floor Plan của TASK 03.

        11. Thiết kế giao diện Responsive:
            - Desktop.
            - Tablet.
            - Mobile.

        12. Xử lý:
            - Loading State.
            - Empty State.
            - Validation Error.
            - Database Error.
            - Unauthorized Actions.

        13. Kiểm thử hiển thị và thống kê
            dựa trên dữ liệu MySQL thực tế.

        14. Không thay đổi nghiệp vụ
            Area, Cafe Table, Order,
            Payment hoặc Reservation.

        KẾT QUẢ MONG MUỐN:

        Trang Quản lý khu vực có UI hiện đại,
        nhất quán với Admin Dashboard.

        Admin dễ dàng quan sát:
        - Quán có bao nhiêu khu vực.
        - Mỗi khu vực có bao nhiêu bàn.
        - Tình trạng các bàn theo khu vực.
        - Các thao tác quản lý được phép.

        Dữ liệu thống kê chính xác.

        Có đường dẫn tới sơ đồ bàn
        khi route tương ứng tồn tại.

        Không hardcode dữ liệu giả.

        Không phát sinh Migration mới.

        Không xây dựng POS hoặc Order
        trong TASK này.
    </goal>

    <context>
        PROJECT:
        Coffee Shop Management System.

        OWNER:
        Thành Toàn.

        TECHNOLOGY:
        - PHP Laravel.
        - MySQL.
        - Laravel MVC.
        - Eloquent ORM.
        - Blade Template.
        - HTML5/CSS3.
        - JavaScript hiện có.

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
        - Area &amp; Table POS UI.

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

        Người dùng muốn giao diện khu vực
        và quản lý bàn có trải nghiệm
        tương tự hệ thống POS chuyên nghiệp.

        Định hướng lấy cảm hứng từ Sapo FnB
        nhưng có thiết kế riêng, hiện đại hơn,
        không sao chép giao diện hoặc mã nguồn.

        Màn hình Area Management phải hỗ trợ:

        - Nhận diện khu vực nhanh.
        - Quan sát số lượng bàn.
        - Quan sát tình trạng bàn.
        - Quản lý thông tin khu vực.
        - Điều hướng vào khu vực để xem bàn.

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

        Đây là tên tham khảo.

        AI Agent phải xác minh:
        - Tên bảng thực tế.
        - Model thực tế.
        - Primary Key.
        - Foreign Key Area-Table.
        - Các trường trạng thái.
        - Eloquent Relationships.

        TABLE STATUS:

        Các trạng thái từng được đề xuất:
        - available.
        - occupied.
        - reserved.
        - maintenance.

        Chỉ sử dụng trạng thái thực tế
        đã xác minh từ source code.

        Không tự thêm trạng thái mới.

        Không tự gộp "waiting_payment"
        vào database table status.

        Nếu TASK 01 có chính sách
        Derived Display Status đã xác nhận,
        sử dụng nguồn tính tương ứng.

        Nếu chưa có, chỉ thống kê
        trạng thái được xác minh.

        Không tự suy diễn trạng thái bàn
        từ Order/Payment trong TASK này.

        NOTE:

        TASK 02 chỉ nâng cấp Area UI.

        Sơ đồ bàn đầy đủ thuộc TASK 03.

        Order/Payment UI thuộc TASK 04-06.

        Đồng bộ trạng thái bàn theo
        vòng đời Order thuộc TASK 07.

        Source code thực tế là căn cứ cuối cùng.
    </context>

    <scope>
        PHẠM VI ĐƯỢC PHÉP:

        1. SOURCE CODE REVIEW:

           - Area Model.
           - CafeTable Model.
           - Area Controller.
           - Area Routes.
           - Area Views.
           - Admin Layout.
           - Authentication/Authorization.
           - Existing Tests.
           - TASK 01 Architecture Report.

        2. AREA OVERVIEW UI:

           - Page Header.
           - Tổng quan khu vực.
           - Summary Cards.
           - Area Cards.
           - Search Bar.
           - Status Filter nếu có.
           - Navigation Controls.

        3. AREA STATISTICS:

           - Total Areas.
           - Total Tables.
           - Available Tables.
           - Occupied Tables.
           - Reserved Tables.
           - Maintenance Tables.

           Chỉ tính các chỉ số có
           dữ liệu và định nghĩa hợp lệ.

        4. AREA CARDS:

           - Area Name.
           - Area Code nếu có.
           - Total Tables.
           - Table Status Summary.
           - Edit Action nếu được phép.
           - Delete Action nếu được phép.
           - View Tables Action.

        5. SEARCH &amp; FILTER:

           - Search by Area Name.
           - Search by Code nếu có.
           - Filter by supported Status.
           - Stable Sorting.
           - Preserve Query Parameters
             khi phù hợp.

        6. AREA CRUD INTEGRATION:

           - Tái sử dụng Create Route.
           - Tái sử dụng Edit Route.
           - Tái sử dụng Delete Workflow.
           - Tái sử dụng Validation.
           - Tái sử dụng Authorization.

        7. UI STATES:

           - Empty State.
           - Loading State nếu có Async.
           - Error State.
           - No Search Results.
           - Disabled Actions.
           - Responsive Layout.

        8. TESTING:

           - Statistics Accuracy.
           - Area Card Data.
           - Search.
           - Filter.
           - Authorization.
           - Responsive.
           - Regression.

        FILE DỰ KIẾN:

        - app/Models/Area.php

        - app/Models/CafeTable.php

        - app/Http/Controllers/Admin/
          AreaController.php

        - resources/views/admin/areas/
          index.blade.php

        - resources/views/admin/areas/
          components/

        - resources/css/

        - resources/js/

        - routes/web.php

        - tests/Feature/
          AreaManagementUITest.php

        Các đường dẫn chỉ mang tính đề xuất.

        Phải kiểm tra file thực tế.

        KHÔNG TRIỂN KHAI:

        - Area CRUD Backend mới.
        - Cafe Table CRUD mới.
        - Table Floor Plan đầy đủ.
        - Order Entry.
        - Payment Integration.
        - Receipt Printing.
        - Table Status Synchronization mới.
        - Database Migration.
        - Database Schema Changes.
        - Real-time WebSocket.
        - POS Business Logic mới.
        - Refactor toàn bộ Admin Layout.
    </scope>

    <hardRules>
        1. BẮT BUỘC khảo sát source code
           trước khi sửa bất kỳ file nào.

        2. Kiểm tra:
           - Current Branch.
           - git status.
           - git diff.
           - Area Routes.
           - AreaController.
           - Area Model.
           - CafeTable Model.
           - Area-Table Relationship.
           - Existing Area Views.
           - Admin Layout.
           - Authorization.
           - Existing Tests.
           - TASK 01 Report nếu tồn tại.

        3. TRƯỚC KHI CODE:

           AI Agent phải báo cáo:
           - UI hiện tại.
           - CRUD Area đã có.
           - Area-Table Relationship.
           - Table Status thực tế.
           - Các thống kê có thể tính.
           - Routes có thể tái sử dụng.
           - Phần còn thiếu/Blockers.
           - Files sẽ chỉnh sửa.
           - Mini Plan.

        4. Không tự sửa Database Schema.

        5. Không tạo Migration mới.

        6. Không tự cài package UI mới.

        7. Không chỉnh sửa:
           - Authentication của Hợp.
           - Authorization của Hợp.
           - Order/Payment của Đạt.
           - Catalog/Promotion của Ninh.
           - Reservation Business Logic.
           - Cafe Table Business Logic.

        8. DESIGN PRINCIPLES:

           - Giao diện hiện đại.
           - Tối giản và chuyên nghiệp.
           - Phù hợp hệ thống Coffee POS.
           - Dễ thao tác bằng chuột/chạm.
           - Thông tin quan trọng nổi bật.
           - Không lạm dụng animation.
           - Không sao chép UI thương mại
             một cách nguyên bản.

        9. UI STRUCTURE:

           A. PAGE HEADER:

              - Title: Quản lý khu vực.
              - Description ngắn gọn.
              - Nút thêm khu vực
                nếu người dùng có quyền.

           B. SUMMARY SECTION:

              - Tổng khu vực.
              - Tổng số bàn.
              - Bàn trống.
              - Bàn có khách.
              - Trạng thái khác nếu có.

           C. AREA FILTER:

              - Search Input.
              - Status Filter nếu phù hợp.
              - Sorting Control khi cần.

           D. AREA GRID:

              - Responsive Cards.
              - Area Name.
              - Table Summary.
              - Status Indicators.
              - Authorized Actions.

        10. DATA SOURCE:

            - Dữ liệu phải lấy từ MySQL.
            - Không hardcode Area mẫu.
            - Không hardcode số bàn.
            - Không dùng Random Statistics.
            - Không sử dụng số liệu Dashboard
              chưa được xác minh.

        11. STATISTICS ACCURACY:

            - Đếm Area theo phạm vi hợp lệ.
            - Đếm bàn dựa trên quan hệ
              Area-Table thực tế.
            - Đếm Status đúng giá trị lưu.
            - Không đếm trùng Cafe Table.
            - Không gộp bàn bị xóa mềm
              nếu policy loại trừ.
            - Không đưa bàn ngoài khu vực
              vào số liệu của khu vực đó.
            - Nếu có trạng thái Unknown,
              phải xử lý riêng nếu cần.

        12. COUNT CONSISTENCY:

            Với mỗi khu vực:

            Tổng bàn phải phù hợp với
            số bản ghi Cafe Table hợp lệ
            được tính theo chính sách.

            Tổng các nhóm trạng thái
            chỉ bằng Total Tables nếu
            chúng bao phủ tất cả trạng thái
            và không giao nhau.

            Nếu không đủ thông tin:
            - Không ép số liệu khớp giả.
            - Không tính thiếu thành 0.
            - Ghi UNKNOWN hoặc BLOCKED
              ở chỉ số chưa xác minh.

        13. QUERY PERFORMANCE:

            - Tránh N+1 Queries.
            - Ưu tiên withCount() hoặc
              Aggregate Query phù hợp.
            - Không query mỗi bàn riêng lẻ.
            - Không tải toàn bộ Order/Payment
              chỉ để đếm bàn.
            - Kiểm tra query trên dữ liệu test.
            - Không tự tạo cache phức tạp.
            - Không tạo truy vấn quá nặng
              trên mỗi lần tải trang.

        14. AREA CARDS:

            Mỗi Card cần thể hiện:
            - Area Name.
            - Total Tables.
            - Available Count nếu có.
            - Occupied Count nếu có.
            - Các trạng thái được hỗ trợ.
            - Nút xem bàn.
            - Actions theo quyền.

            Không hiển thị thống kê
            không có dữ liệu hợp lệ.

        15. CARD NAVIGATION:

            - Nút "Xem bàn" phải dùng
              Route thực tế được phép.

            - Nếu route Table Floor Plan
              chưa có vì thuộc TASK 03:
              + Không tự tạo trang giả.
              + Không tự tạo Route nghiệp vụ.
              + Không dùng href="#".
              + Ghi Integration Dependency.
              + Có thể dùng hành động hiện có
                để xem bàn theo khu vực
                nếu route đó đã tồn tại.

            - Không tạo broken links.

        16. AREA CRUD:

            - Không viết lại Area CRUD.
            - Tái sử dụng Controller hiện có.
            - Tái sử dụng Create/Edit/Delete.
            - Không thay đổi Validation.
            - Không thay đổi Delete Policy.
            - Không tự xóa khu vực có bàn.
            - Không thay đổi ràng buộc FK.

        17. AUTHORIZATION:

            - Chỉ người có quyền mới được
              xem hoặc thao tác theo policy.
            - Không hiển thị nút Delete
              cho người không có quyền.
            - Không hiển thị Edit Action
              trái policy.
            - Không bỏ kiểm tra quyền Backend.
            - Không tạo Permission mới.
            - Không sửa Middleware của Hợp.

        18. SEARCH:

            - Search theo dữ liệu thực tế.
            - Không phân biệt chữ hoa/thường
              nếu policy/collation hỗ trợ.
            - Không yêu cầu tìm theo trường
              không tồn tại.
            - Escape output trong Blade.
            - Không chèn query thô
              từ input người dùng.

        19. FILTER:

            - Chỉ lọc theo trường
              được schema hỗ trợ.
            - Không tự tạo Area Status
              nếu bảng không có.
            - Không tự suy diễn trạng thái
              khu vực từ Payment.
            - Hiển thị trạng thái Filter
              rõ ràng cho người dùng.
            - Có cách xóa Filter.

        20. EMPTY STATE:

            - Chưa có khu vực.
            - Không có kết quả tìm kiếm.
            - Khu vực chưa có bàn.
            - Dữ liệu thống kê thiếu.

            Không hiển thị lỗi kỹ thuật
            thô ra người dùng.

        21. RESPONSIVE:

            Desktop:
            - Summary Cards rõ ràng.
            - Area Grid nhiều cột.
            - Actions dễ tiếp cận.

            Tablet:
            - Giảm số cột phù hợp.
            - Không chồng lấn nội dung.
            - Bộ lọc còn sử dụng được.

            Mobile:
            - Card chuyển sang ít cột.
            - Không tràn ngang.
            - Nút dễ chạm.
            - Tên khu vực không bị mất.
            - Thống kê dễ đọc.

        22. ACCESSIBILITY:

            - Button có nhãn.
            - Focus State rõ ràng.
            - Tab Navigation.
            - Không chỉ dùng màu sắc
              để thể hiện Status.
            - Icon phải có ngữ cảnh.
            - Contrast phù hợp.

        23. DESIGN SYSTEM:

            - Tái sử dụng Font.
            - Tái sử dụng Colors.
            - Tái sử dụng Spacing.
            - Tái sử dụng Button Styles.
            - Tái sử dụng Components
              hiện có khi phù hợp.

            - Không thay đổi CSS toàn cục
              nếu không cần thiết.

        24. UI STATUS VS DATABASE STATUS:

            - Không tự chuyển trạng thái bàn.
            - Không tự đánh dấu bàn có khách
              chỉ vì người dùng mở Area.
            - Không tự tạo trạng thái
              chờ thanh toán trong database.
            - Đồng bộ Order/Payment thuộc TASK 07.
            - Chỉ hiển thị các trạng thái
              được backend xác nhận.

        25. ERROR HANDLING:

            - Không render lỗi SQL thô.
            - Không báo thống kê 0 giả
              khi query thất bại.
            - Không phá vỡ toàn bộ trang
              nếu thành phần phụ gặp lỗi.
            - Ghi lỗi theo cơ chế log hiện có
              và không lộ dữ liệu nhạy cảm.

        26. DATABASE SAFETY:

            - Không chạy migrate:fresh
              trên database dùng chung.
            - Không chạy db:wipe.
            - Không truncate dữ liệu.
            - Không tạo Area/Table giả
              trong môi trường production.
            - Test ghi dữ liệu phải dùng
              database testing độc lập.

        27. GIT SAFETY:

            - Không ghi đè thay đổi
              chưa commit của thành viên khác.
            - Không tự động commit.
            - Không tự động push.
            - Không tự động merge.
            - Không tự ý đổi branch
              khi working tree chưa an toàn.

        28. TEST INTEGRITY:

            - Không sửa test để che lỗi.
            - Không tự tuyên bố UI đã được
              kiểm tra trực quan nếu không có browser.
            - Ghi PASS khi chạy thật.
            - Ghi FAIL khi kết quả sai.
            - Ghi BLOCKED khi thiếu phụ thuộc.
            - Ghi NOT RUN khi chưa kiểm thử.

        29. SCOPE DISCIPLINE:

            - Không triển khai Table Floor Plan.
            - Không triển khai Order-at-Table.
            - Không triển khai Checkout.
            - Không triển khai Receipt.
            - Không triển khai State Sync mới.
            - Không thay đổi POS Workflow.
    </hardRules>

    <tasks>
        <taskUnit id="01">
            <name>
                Khảo sát Area Management
                và lập kế hoạch UI
            </name>

            <estimatedTime>
                10-15 phút.
            </estimatedTime>

            <steps>
                - Kiểm tra branch/git status.
                - Kiểm tra TASK 01 Report.
                - Kiểm tra Area Model.
                - Kiểm tra CafeTable Model.
                - Kiểm tra Area Relationships.
                - Kiểm tra Routes.
                - Kiểm tra CRUD hiện có.
                - Kiểm tra Area Views.
                - Kiểm tra Admin Layout.
                - Kiểm tra Authorization.
                - Kiểm tra Status thực tế.
                - Kiểm tra CSS/JS hiện có.

                - Xác định thống kê khả thi.
                - Xác định UI đang tồn tại.
                - Xác định file cần sửa.
                - Xác định route xem bàn.
                - Xác định blocker.

                - Trình bày Mini Plan.
            </steps>
        </taskUnit>

        <taskUnit id="02">
            <name>
                Xây dựng Area Statistics
                và Data Preparation
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. BACKEND:

                - Tái sử dụng AreaController
                  hoặc Read Service hiện có.

                - Truy vấn Area hợp lệ.

                - Xác định Total Areas.

                - Xác định Total Tables.

                - Thống kê bàn theo
                  Status thực tế.

                - Thống kê theo từng Area.

                - Tránh N+1 Queries.

                - Dùng Aggregate Query
                  hoặc withCount() phù hợp.

                B. DATA VALIDATION:

                - Kiểm tra Area-Table FK.
                - Kiểm tra Soft Delete.
                - Kiểm tra các Status hợp lệ.
                - Đối chiếu tổng số bàn
                  với số bản ghi thực tế.
                - Không hardcode dữ liệu.

                C. FILTER DATA:

                - Tìm kiếm theo tên.
                - Lọc theo thuộc tính tồn tại.
                - Sắp xếp ổn định.
                - Không thay đổi dữ liệu gốc.

                D. ERROR HANDLING:

                - Database rỗng.
                - Area không có bàn.
                - Filter không có kết quả.
                - Query thất bại.
                - Chỉ số chưa có nguồn hợp lệ.

                E. TESTS:

                - Total Areas chính xác.
                - Total Tables chính xác.
                - Count theo Area đúng.
                - Count theo Status đúng.
                - Không đếm trùng.
                - Không có N+1 nghiêm trọng.
            </steps>
        </taskUnit>

        <taskUnit id="03">
            <name>
                Xây dựng Modern Area UI
                và Navigation
            </name>

            <estimatedTime>
                25-30 phút.
            </estimatedTime>

            <steps>
                A. PAGE HEADER:

                - Tiêu đề Quản lý khu vực.
                - Mô tả ngắn.
                - Nút Thêm khu vực
                  nếu có quyền.

                B. SUMMARY CARDS:

                - Tổng khu vực.
                - Tổng số bàn.
                - Bàn trống.
                - Bàn có khách.
                - Các trạng thái khác
                  nếu đã có nguồn hợp lệ.

                C. AREA GRID:

                - Render Area Cards động.
                - Hiển thị tên khu vực.
                - Hiển thị tổng số bàn.
                - Hiển thị phân bố trạng thái.
                - Hiển thị Icon thích hợp.
                - Hiển thị Actions theo quyền.

                D. FILTERS:

                - Search Input.
                - Status Filter khi hỗ trợ.
                - Clear Filters.
                - Empty Search State.

                E. ACTIONS:

                - Thêm khu vực.
                - Chỉnh sửa khu vực.
                - Xóa theo policy hiện có.
                - Xem bàn trong khu vực
                  khi có Route phù hợp.

                F. RESPONSIVE:

                - Desktop Grid.
                - Tablet Layout.
                - Mobile Cards.
                - Touch-friendly Buttons.
                - Không overflow ngang.

                G. UI QUALITY:

                - Visual Hierarchy rõ.
                - Spacing nhất quán.
                - Hover/Focus States.
                - Loading/Empty/Error States.
                - Status Icon &amp; Label.
                - Không chỉ dùng màu
                  để phân biệt trạng thái.
            </steps>
        </taskUnit>

        <taskUnit id="04">
            <name>
                Kiểm thử, sửa lỗi
                và hoàn thiện Area UI
            </name>

            <estimatedTime>
                15-20 phút.
            </estimatedTime>

            <steps>
                A. FUNCTIONAL TESTS:

                - Area Listing.
                - Search by Name.
                - Clear Search.
                - Filter nếu hỗ trợ.
                - Area Card Data.
                - Total Area Count.
                - Table Count per Area.
                - Status Counts.
                - Empty Area.
                - No Results.

                B. AUTHORIZATION:

                - Admin có quyền.
                - Staff có quyền phù hợp.
                - User không có quyền.
                - Guest.
                - Edit/Delete Actions.
                - Backend Authorization.

                C. NAVIGATION:

                - Create Route.
                - Edit Route.
                - Delete Workflow.
                - View Tables Route nếu có.
                - Không có Broken Links.

                D. UI TESTS:

                - Desktop.
                - Tablet.
                - Mobile.
                - Text Overflow.
                - Grid Layout.
                - Buttons.
                - Keyboard Navigation.

                E. PERFORMANCE:

                - Query Count nếu đo được.
                - Không N+1 nghiêm trọng.
                - Không query lặp không cần.
                - Số liệu khớp dữ liệu test.

                F. REGRESSION:

                - Area CRUD.
                - Cafe Table CRUD.
                - Admin Layout.
                - Authentication.
                - Authorization.
                - Các Route hiện tại.

                G. FINAL CHECKS:

                - Chạy Feature Tests.
                - Kiểm tra Artisan route:list.
                - Kiểm tra cú pháp PHP.
                - Kiểm tra Blade Rendering.
                - Kiểm tra Asset Build nếu có.
                - Kiểm tra git diff/git status.
                - Xác nhận không sửa ngoài scope.

                - Xuất Final Report.
                - Không commit/push/merge.
            </steps>
        </taskUnit>
    </tasks>

    <acceptanceCriteria>
        1. Đã khảo sát Area Management
           và kết quả TASK 01 nếu có.

        2. Sử dụng đúng Area Model thực tế.

        3. Sử dụng đúng CafeTable Model thực tế.

        4. Không thay đổi Database Schema.

        5. Có giao diện Area Management mới.

        6. Giao diện thống nhất Admin Layout.

        7. Hiển thị Area Cards từ MySQL.

        8. Tên khu vực chính xác.

        9. Tổng số khu vực chính xác.

        10. Tổng số bàn chính xác.

        11. Số bàn theo từng Area chính xác.

        12. Các chỉ số trạng thái có nguồn
            dữ liệu và quy tắc xác minh.

        13. Không hardcode Area/Table.

        14. Không thống kê trùng bàn.

        15. Không dùng số 0 giả
            khi thiếu dữ liệu xác minh.

        16. Có Search hoạt động.

        17. Có Filter nếu phù hợp schema.

        18. Có Empty State.

        19. Area không có bàn hiển thị hợp lý.

        20. Tái sử dụng Area Create.

        21. Tái sử dụng Area Edit.

        22. Tái sử dụng Area Delete
            theo quyền và policy hiện có.

        23. Có điều hướng xem bàn
            khi route thực sự tồn tại.

        24. Không tự tạo Route giả.

        25. Có xử lý lỗi hợp lý.

        26. Có Responsive Desktop.

        27. Có Responsive Tablet.

        28. Có Responsive Mobile.

        29. Không overflow nghiêm trọng.

        30. Có Accessibility cơ bản.

        31. Không có N+1 Query nghiêm trọng.

        32. Không sửa nghiệp vụ Area CRUD.

        33. Không sửa nghiệp vụ Cafe Table.

        34. Không sửa Order/Payment.

        35. Không sửa Authentication/Authorization.

        36. Không tạo Migration.

        37. Không tự động commit/push/merge.

        38. Có Feature Tests liên quan.

        39. Kết quả kiểm thử trung thực.

        40. Nếu thiếu route hoặc dữ liệu
            cần thiết, ghi BLOCKED.

        41. AI Agent phải xuất FINAL REPORT:

            A. MODULE OVERVIEW
            - Chức năng hoàn thành.
            - Chức năng chưa hoàn thành.
            - Blocker còn tồn tại.

            B. SOURCE CODE REVIEW
            - Area Model.
            - CafeTable Model.
            - Area Controller.
            - Area Routes.
            - Admin Layout.
            - Existing Authorization.

            C. AREA STATISTICS
            - Total Areas.
            - Total Tables.
            - Count per Area.
            - Count by Status.
            - Data Source.
            - Query Strategy.
            - Validation Results.

            D. UI IMPLEMENTATION
            - Page Header.
            - Summary Cards.
            - Area Cards.
            - Search/Filters.
            - CRUD Actions.
            - View Tables Navigation.
            - Empty/Error States.

            E. RESPONSIVE REVIEW
            - Desktop.
            - Tablet.
            - Mobile.
            - Layout Issues.
            - Accessibility.

            F. PERFORMANCE
            - Query Count nếu đo được.
            - N+1 Analysis.
            - Aggregate Query Strategy.
            - Performance Limitations.

            G. SECURITY
            - Authentication.
            - Authorization.
            - Blade Output Escaping.
            - Safe Navigation.
            - Unauthorized Actions.

            H. FILE CHANGES
            - File tạo mới.
            - File chỉnh sửa.
            - Mục đích thay đổi.
            - Xác nhận không sửa ngoài scope.

            I. TEST RESULTS
            - Test ID.
            - Test Scenario.
            - Expected Result.
            - Actual Result.
            - PASS/FAIL/BLOCKED/NOT RUN.
            - Test Commands.
            - Test Environment.
            - Evidence nếu có.

            J. REGRESSION
            - Area CRUD.
            - Cafe Table CRUD.
            - Admin Layout.
            - Authentication/Authorization.

            K. NEXT TASK HANDOFF
            - Area Data Contract.
            - Table Count Contract.
            - Area Filter/Navigation.
            - Design Components Reusable.
            - Dependencies cho TASK 03.
            - Các vấn đề cần xử lý tiếp.

            L. FINAL STATUS
            - READY FOR REVIEW
              hoặc NOT READY FOR REVIEW.
            - Blocker còn tồn tại.
            - Những việc cần xác nhận.

        42. Chỉ đánh dấu TASK 02 hoàn thành
            khi Area UI hoạt động với dữ liệu thật
            trong môi trường kiểm thử,
            thống kê chính xác,
            giao diện được tích hợp an toàn
            và không gây lỗi hồi quy nghiêm trọng.

        43. Không tuyên bố hoàn thiện
            Table Floor Plan hoặc POS
            trong TASK 02.
    </acceptanceCriteria>
</prompt>
```