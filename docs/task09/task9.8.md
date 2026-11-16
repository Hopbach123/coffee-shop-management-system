```xml
<?xml version="1.0" encoding="UTF-8"?>
<prompt>
    <goal>
        TRIỂN KHAI TASK 08:
        UI/UX POLISH &amp; RESPONSIVE OPTIMIZATION

        DỰ ÁN:
        Coffee Shop Management System.

        MODULE:
        Area &amp; Table POS Management.

        BRANCH:
        feature/area-table-pos-ui.

        THỜI GIAN:
        60-90 phút.

        ĐỘ KHÓ:
        D4 - FRONTEND UI/UX.

        CÔNG NGHỆ:
        PHP Laravel, Blade, MySQL,
        JavaScript, CSS và những thư viện
        giao diện hiện có trong dự án.

        MỤC TIÊU:

        Hoàn thiện giao diện quản lý khu vực,
        sơ đồ bàn và các màn hình vận hành POS
        tại bàn thành một trải nghiệm thống nhất,
        hiện đại, trực quan và dễ sử dụng.

        PHẠM VI GIAO DIỆN:

        1. Area Management UI.

        2. Table Floor Plan UI.

        3. Table Detail Panel.

        4. Order-at-Table UI.

        5. Checkout UI.

        6. Receipt/Print Preview UI.

        7. Table Status Indicators.

        YÊU CẦU CHÍNH:

        A. VISUAL DESIGN:

        - Thiết kế hiện đại, chuyên nghiệp.
        - Thống nhất màu sắc và Typography.
        - Khoảng cách và bố cục hợp lý.
        - Cards trực quan.
        - Icons nhất quán.
        - Phân cấp thông tin rõ ràng.
        - Trạng thái bàn dễ nhận biết.

        B. RESPONSIVE:

        - Desktop.
        - Tablet.
        - Mobile.
        - Không overflow ngang.
        - Không mất nút chức năng quan trọng.
        - Không che tổng tiền hoặc thông tin bàn.
        - Tối ưu cho thao tác chạm.

        C. INTERACTION:

        - Hover States.
        - Focus States.
        - Pressed States.
        - Selected States.
        - Disabled States.
        - Expand/Collapse.
        - Drawer/Panel Open/Close.
        - Form Feedback.

        D. SYSTEM STATES:

        - Loading.
        - Empty.
        - No Results.
        - Error.
        - Unauthorized.
        - Network Failure.
        - Conflict/Stale Data.
        - Saving.
        - Payment Pending.
        - Success Feedback.

        E. ACCESSIBILITY:

        - Keyboard Navigation.
        - Focus Visibility.
        - Semantic HTML.
        - Accessible Dialog/Drawer.
        - Accessible Form Labels.
        - Touch Target Size.
        - Reduced Motion.
        - Contrast.

        F. REGRESSION:

        - Giữ nguyên nghiệp vụ Area.
        - Giữ nguyên Cafe Table.
        - Không làm hỏng Order.
        - Không làm hỏng Payment.
        - Không làm sai trạng thái bàn.
        - Không làm hỏng Receipt Printing.
        - Không thay đổi Authorization.

        KẾT QUẢ MONG MUỐN:

        Giao diện Area &amp; Table POS
        có trải nghiệm nhất quán và đẹp.

        Nhân viên có thể thao tác thuận tiện
        trên máy tính, Tablet và điện thoại.

        Các thao tác quan trọng dễ tìm:
        - Chọn khu vực.
        - Chọn bàn.
        - Xem trạng thái.
        - Mở Order.
        - Thêm món.
        - Lưu Order.
        - Checkout.
        - Xem và in chứng từ.

        Không làm sai dữ liệu backend.

        Không xây dựng nghiệp vụ mới.

        Không thay đổi Database Schema.

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
        - PHP Laravel.
        - Blade Template.
        - MySQL.
        - JavaScript.
        - CSS.
        - UI Framework hiện có.

        TEAM OWNERSHIP:

        THÀNH TOÀN:
        - Area Management.
        - Cafe Table Management.
        - Reservation Management.
        - Table Floor Plan.
        - Table Detail.
        - Order-at-Table UI.
        - Checkout UI Integration.
        - Table Status Presentation.

        ĐẠT:
        - POS.
        - Order.
        - Order Items.
        - Payment.
        - Receipt Backend.

        NINH:
        - Catalog.
        - Promotion.
        - Product Pricing.

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

        DESIGN DIRECTION:

        Modern Coffee POS.

        Lấy cảm hứng từ trải nghiệm quản lý
        khu vực và bàn của phần mềm Sapo FnB,
        nhưng thiết kế giao diện riêng.

        Yêu cầu:
        - Cao cấp nhưng không rườm rà.
        - Tập trung vào tốc độ thao tác.
        - Khu vực dễ nhận biết.
        - Mã bàn nổi bật.
        - Trạng thái bàn rõ ràng.
        - Giỏ hàng dễ quan sát.
        - Tổng tiền nổi bật.
        - Nút hành động quan trọng dễ tiếp cận.

        Không sao chép nguyên bản giao diện,
        icon có bản quyền, tài sản hoặc mã nguồn
        của sản phẩm thương mại khác.

        SOURCE OF TRUTH:

        - Area/CafeTable Backend hiện có.
        - Order/POS Backend của Đạt.
        - Payment Backend của Đạt.
        - Product Catalog của Ninh.
        - Authorization của Hợp.
        - Table Status Contract TASK 07.

        TASK 08 là nhiệm vụ cải thiện UI/UX.

        Nếu TASK 01-07 còn thiếu chức năng
        hoặc API chưa hoàn thiện,
        phải báo cáo dependency.

        Không dùng giao diện giả lập
        để tuyên bố chức năng đã hoàn thành.
    </context>

    <scope>
        PHẠM VI ĐƯỢC PHÉP:

        1. UI AUDIT:

        - Area Management.
        - Table Floor Plan.
        - Table Detail.
        - Order-at-Table.
        - Checkout.
        - Receipt Preview.
        - Status Indicators.
        - Admin Layout.

        2. DESIGN CONSISTENCY:

        - Colors.
        - Typography.
        - Spacing.
        - Border Radius.
        - Shadows.
        - Buttons.
        - Form Controls.
        - Cards.
        - Icons.
        - Status Labels.

        3. RESPONSIVE:

        - Desktop Layout.
        - Tablet Layout.
        - Mobile Layout.
        - Responsive Navigation.
        - Responsive Grid.
        - Responsive Drawer.
        - Responsive Order Cart.
        - Responsive Checkout.
        - Print Layout Preservation.

        4. INTERACTION:

        - Area Tabs.
        - Table Selection.
        - Table Detail Panel.
        - Product Selection.
        - Quantity Controls.
        - Cart Drawer.
        - Checkout Feedback.
        - Navigation Controls.

        5. UI STATES:

        - Loading/Skeleton.
        - Empty State.
        - No Results.
        - Error State.
        - Conflict State.
        - Unauthorized State.
        - Success Feedback.
        - Disabled/Processing State.

        6. ACCESSIBILITY:

        - Keyboard Navigation.
        - Semantic Markup.
        - Focus Management.
        - ARIA Attributes.
        - Accessible Labels.
        - Color Contrast.
        - Touch Targets.
        - Reduced Motion.

        7. UI TESTING:

        - Layout Testing.
        - Responsive Testing.
        - Interaction Testing.
        - Accessibility Review.
        - Regression Testing.

        FILE DỰ KIẾN KIỂM TRA:

        - resources/views/admin/areas/

        - resources/views/admin/tables/

        - resources/views/admin/pos/

        - resources/views/layouts/

        - resources/views/components/

        - resources/css/

        - resources/js/

        - tests/Feature/

        - tests/Browser/ nếu tồn tại.

        Đây là các đường dẫn gợi ý.

        Phải tìm đúng file thực tế.

        Không tạo lại Component đã tồn tại.

        KHÔNG TRIỂN KHAI:

        - Area CRUD Backend mới.
        - Cafe Table CRUD Backend mới.
        - Order Service mới.
        - Payment Service mới.
        - Receipt Backend mới.
        - Product Catalog mới.
        - Table Status Logic mới.
        - Database Migration.
        - Database Schema Changes.
        - Realtime/WebSocket mới.
        - Refactor toàn bộ Admin Layout.
        - Thay đổi Authentication/Authorization.
        - Chức năng nghiệp vụ ngoài TASK 08.
    </scope>

    <hardRules>
        1. BẮT BUỘC khảo sát source code
           trước khi chỉnh sửa.

        2. Kiểm tra:
           - Current Branch.
           - git status.
           - git diff.
           - TASK 01-07.
           - Blade Views.
           - CSS/JS Assets.
           - Existing Components.
           - Admin Layout.
           - UI Framework.
           - Routes.
           - Authorization.
           - Existing Tests.

        3. TRƯỚC KHI CODE:

           AI Agent phải báo cáo:
           - UI hiện tại.
           - Responsive Issues.
           - Layout Inconsistencies.
           - UX Bottlenecks.
           - Accessibility Issues.
           - Thiếu Loading/Empty/Error States.
           - Các thành phần dùng lại được.
           - Các blocker.
           - File dự kiến thay đổi.
           - Mini Plan.

        4. Không tự tạo Migration.

        5. Không thay đổi Database Schema.

        6. Không tự cài UI Package mới.

        7. Không sửa:
           - Order Backend của Đạt.
           - Payment Backend của Đạt.
           - Catalog của Ninh.
           - Authentication của Hợp.
           - Authorization của Hợp.
           - Reservation Business Logic.

        8. DESIGN SYSTEM:

           - Tái sử dụng Design Tokens
             hiện có nếu dự án hỗ trợ.
           - Không phá vỡ Theme của Admin.
           - Không thay đổi CSS toàn cục
             khi không cần thiết.
           - Nếu chưa có Design Tokens,
             chỉ tổ chức lại style
             trong phạm vi module.
           - Không áp đặt Framework mới.

        9. VISUAL HIERARCHY:

           Thông tin quan trọng phải nổi bật:

           - Area Name.
           - Table Number.
           - Table Status.
           - Order Status.
           - Cart Total.
           - Payment Total.
           - Primary Actions.

           Không làm người dùng phải
           tìm kiếm quá lâu các thao tác chính.

        10. AREA UI:

            - Area Cards rõ ràng.
            - Thống kê khu vực dễ đọc.
            - Số bàn nổi bật.
            - Các trạng thái rõ ràng.
            - Search/Filter dễ thao tác.
            - Không thay đổi cách tính thống kê.
            - Không hardcode dữ liệu.

        11. TABLE FLOOR PLAN:

            - Table Number là thông tin chính.
            - Table Cards có kích thước phù hợp.
            - Grid thích ứng màn hình.
            - Status có Icon và Label.
            - Selected Table rõ ràng.
            - Không tạo nút Order giả.
            - Không làm sai Status Contract.
            - Không tự cập nhật Table Status.

        12. TABLE STATUS COLORS:

            - Dùng màu trạng thái nhất quán.
            - Không mặc định màu sắc
              là nguồn thông tin duy nhất.
            - Phải có nhãn trạng thái.
            - Tránh dùng cùng một màu
              cho hai trạng thái dễ nhầm.
            - Không đổi ý nghĩa Business State
              chỉ vì đổi màu giao diện.

        13. TABLE DETAIL PANEL:

            - Hiển thị Table Code.
            - Hiển thị Area.
            - Hiển thị Capacity.
            - Hiển thị Order Summary.
            - Hiển thị Status.
            - Primary Action rõ ràng.
            - Có cách đóng Panel.
            - Không che nội dung quan trọng.
            - Không mở nhầm Order.

        14. ORDER-AT-TABLE UI:

            - Product Cards dễ chọn.
            - Category Tabs dễ chuyển.
            - Search Input dễ tìm.
            - Quantity Controls dễ chạm.
            - Notes Input rõ ràng.
            - Saved Items và Draft Items
              phải phân biệt được.
            - Không làm mất Draft khi
              giao diện thay đổi kích thước.
            - Save Order Button nổi bật.
            - Không tự Submit Form
              khi chỉ thay đổi giao diện.

        15. CHECKOUT UI:

            - Tổng tiền chính thức nổi bật.
            - Phương thức thanh toán rõ ràng.
            - Payment Status dễ phân biệt.
            - Không nhầm "In" với "Thanh toán".
            - Có trạng thái Processing.
            - Không hiển thị Success giả.
            - Không tự Retry Payment.
            - Không thay đổi Amount.
            - Không thay đổi Payment Status.

        16. RECEIPT &amp; PRINT:

            - Không làm hỏng Print Template.
            - Không đưa Sidebar/Nav vào bản in
              nếu template không yêu cầu.
            - Không cắt mất tổng tiền.
            - Không cắt mất mã chứng từ.
            - Không tự thay đổi nội dung hóa đơn.
            - Không tự phát hành hóa đơn điện tử.
            - Không sửa trạng thái bàn khi In.
            - Kiểm tra @media print
              nếu dự án đang sử dụng.

        17. DESKTOP RESPONSIVE:

            - Kiểm tra màn hình rộng.
            - Kiểm tra Laptop phổ biến.
            - Area Grid không quá dàn trải.
            - Table Grid có khoảng cách hợp lý.
            - Table Detail có thể đặt
              bên cạnh Floor Plan nếu phù hợp.
            - Product Grid và Cart
              dễ quan sát đồng thời.
            - Không che Order Total.

        18. TABLET RESPONSIVE:

            - Kiểm tra màn hình Tablet.
            - Hỗ trợ thao tác Touch.
            - Area Tabs dễ chuyển.
            - Table Cards đủ lớn.
            - Detail Panel không quá hẹp.
            - Cart có thể dùng Drawer
              nếu bố cục yêu cầu.
            - Không phụ thuộc Hover.
            - Không che Checkout Button.

        19. MOBILE RESPONSIVE:

            - Không overflow ngang.
            - Table Code không bị cắt.
            - Status Label vẫn đọc được.
            - Search/Filter dễ mở.
            - Product Cards không quá nhỏ.
            - Quantity Controls dễ chạm.
            - Cart có thể mở dạng Drawer.
            - Primary Action luôn dễ tiếp cận.
            - Có cách quay lại Floor Plan.
            - Không mất Table Context.

        20. BREAKPOINTS:

            - Ưu tiên Breakpoints đã có
              trong Design System hiện tại.
            - Nếu chưa có, thiết kế phù hợp
              với nội dung và bố cục thực tế.
            - Kiểm tra tối thiểu các viewport
              đại diện cho Mobile, Tablet,
              Laptop và Desktop.
            - Không chỉ dựa vào một kích thước.
            - Không tạo Media Queries trùng
              hoặc mâu thuẫn không cần thiết.

        21. TOUCH INTERACTION:

            - Không chỉ dựa vào Hover.
            - Nút bấm đủ dễ chạm.
            - Khoảng cách giữa nút hợp lý.
            - Không đặt Delete sát Save
              gây thao tác nhầm.
            - Tránh Double Tap gây Submit lặp.
            - Không tạo hành động thanh toán
              chỉ bằng chạm nhầm trên Card.

        22. DRAWER/MODAL:

            - Dùng đúng Semantic Structure.
            - Có nút đóng rõ ràng.
            - Escape đóng khi phù hợp.
            - Quản lý Focus hợp lý.
            - Trả Focus về Trigger sau khi đóng.
            - Không cho Keyboard Focus
              đi vào nội dung phía sau
              khi mở Modal đúng nghĩa.
            - Không làm mất Draft khi
              đóng/mở Panel ngoài ý muốn.
            - Không tạo nhiều lớp Overlay
              chồng lên nhau.
            - Không khóa cuộn trang vĩnh viễn.

        23. LOADING STATES:

            Phải xem xét:

            - Area Loading.
            - Floor Plan Loading.
            - Table Detail Loading.
            - Product Loading.
            - Order Saving.
            - Checkout Processing.
            - Table Status Refreshing.

            Nếu yêu cầu bất đồng bộ:
            - Có Spinner hoặc Skeleton hợp lý.
            - Không làm Layout nhảy mạnh.
            - Không hiển thị dữ liệu giả
              như kết quả đã tải.
            - Không kéo dài animation giả
              khi dữ liệu đã sẵn sàng.

        24. EMPTY STATES:

            - Chưa có khu vực.
            - Khu vực không có bàn.
            - Không tìm thấy bàn.
            - Không có sản phẩm.
            - Không tìm thấy sản phẩm.
            - Giỏ hàng trống.
            - Order chưa có món.
            - Chưa có chứng từ hợp lệ.

            Empty State phải:
            - Có thông điệp rõ ràng.
            - Có hành động tiếp theo hợp lệ
              nếu người dùng có quyền.
            - Không tạo nút giả.
            - Không làm người dùng tưởng lỗi.

        25. ERROR STATES:

            - Backend Unavailable.
            - Network Error.
            - Unauthorized.
            - Validation Error.
            - Table Conflict.
            - Order Closed.
            - Product Unavailable.
            - Payment Failed.
            - Payment Pending.
            - Receipt Unavailable.

            Error Message phải:
            - Dễ hiểu.
            - Đúng nguồn lỗi.
            - Không chứa Stack Trace/SQL Error.
            - Không giả định thao tác thất bại
              hoặc thành công khi chưa xác minh.

        26. CONFLICT STATES:

            - Table Status đã thay đổi.
            - Order bị nhân viên khác sửa.
            - Order đã thanh toán.
            - Product Availability thay đổi.
            - Payment Result chưa rõ.

            Chỉ hiển thị hành động Retry/Refresh
            nếu an toàn theo Contract.

            Không tự gửi lại Payment Request
            trong trạng thái chưa xác định.

        27. SUCCESS STATES:

            - Chỉ thông báo thành công
              sau Backend Confirmation.
            - Không tự đánh dấu Order Saved
              chỉ vì Animation hoàn tất.
            - Không tự đánh dấu Payment Paid.
            - Không tự chuyển Table Available
              trái Status Contract.
            - Không dùng thông báo "Đã in"
              nếu chỉ mở Print Dialog.

        28. UNSAVED CHANGES:

            - Không làm mất Cart Draft
              âm thầm khi đổi kích thước màn hình.
            - Không tự Save khi mở/đóng Drawer.
            - Nếu người dùng rời trang
              khi còn dữ liệu chưa lưu,
              xử lý theo chính sách đã có.
            - Không lưu dữ liệu nhạy cảm
              vào Local Storage tùy tiện.

        29. KEYBOARD ACCESSIBILITY:

            - Tab Order hợp lý.
            - Focus Ring rõ ràng.
            - Enter/Space kích hoạt
              Button theo Semantic HTML.
            - Escape đóng Dialog khi phù hợp.
            - Không đặt Click Handler
              lên div không hỗ trợ Keyboard
              nếu đã có Button/Link phù hợp.
            - Có Label cho Input/Select.

        30. ARIA:

            - aria-expanded khi cần.
            - aria-controls khi phù hợp.
            - aria-current cho Navigation.
            - Accessible Dialog Title.
            - Không sử dụng ARIA sai ngữ nghĩa.
            - Không thay thế Semantic HTML
              bằng ARIA không cần thiết.

        31. COLOR &amp; CONTRAST:

            - Text phải đủ dễ đọc.
            - Status Label phải rõ ràng.
            - Disabled State dễ phân biệt.
            - Focus State nổi bật.
            - Không chỉ dùng màu để báo lỗi.
            - Ưu tiên đạt WCAG AA
              với nội dung và controls chính.

        32. ANIMATION:

            - Chỉ dùng animation vừa phải.
            - Không làm chậm thao tác POS.
            - Không dùng hiệu ứng rối mắt.
            - Ưu tiên Transition ngắn.
            - Tôn trọng prefers-reduced-motion.
            - Không dùng animation che lỗi.
            - Không cần thêm thư viện mới.

        33. UI PERFORMANCE:

            - Hạn chế DOM quá lớn.
            - Không render Product/Table
              lặp vô ích.
            - Không thêm JavaScript nặng.
            - Không tạo Event Listener trùng.
            - Không tạo Interval không kiểm soát.
            - Không tải hình ảnh quá lớn.
            - Không làm tăng số lần gọi API
              khi thay đổi Breakpoint.
            - Không làm hỏng State Refresh.

        34. BUSINESS BOUNDARY:

            - Không tự sửa Area CRUD.
            - Không tự sửa Cafe Table CRUD.
            - Không tự tính lại Order Total.
            - Không tự chỉnh Product Price.
            - Không tự thay đổi Order Items
              ngoài Action hợp lệ.
            - Không thay đổi Payment Status.
            - Không thay đổi Table Status.
            - Không thay đổi Reservation.
            - Không sửa Backend Authorization.

        35. SECURITY:

            - Không thay đổi Middleware.
            - Không thêm Route công khai.
            - Không render Raw HTML không an toàn.
            - Không bypass CSRF.
            - Không bỏ Validation Backend.
            - Không lộ Order/Payment Data
              trên giao diện trái quyền.
            - Không giả định nút bị ẩn
              đồng nghĩa được bảo vệ Backend.

        36. TESTING ENVIRONMENT:

            - Dùng môi trường test an toàn.
            - Không tạo Order Production.
            - Không dùng giao dịch thật
              để thử UI thanh toán.
            - Không xóa dữ liệu thật.
            - Không chạy migrate:fresh
              trên database dùng chung.
            - Không chạy db:wipe.

        37. GIT SAFETY:

            - Không tự động commit.
            - Không tự động push.
            - Không tự động merge.
            - Không ghi đè code chưa commit
              của thành viên khác.
            - Không đổi branch khi chưa an toàn.

        38. TEST INTEGRITY:

            - PASS khi test thật sự đạt.
            - FAIL khi phát hiện lỗi.
            - BLOCKED khi thiếu Dependency.
            - NOT RUN khi chưa thực hiện.
            - NOT APPLICABLE nếu không áp dụng.
            - Không báo đã kiểm thử Browser
              nếu chưa có môi trường Browser.
            - Không tự tạo Screenshot giả.

        39. SCOPE DISCIPLINE:

            Chỉ sửa những thành phần UI
            thuộc Area &amp; Table POS
            cần thiết cho TASK 08.

            Nếu phát hiện lỗi backend,
            phải báo cáo Owner của module
            thay vì tự mở rộng phạm vi.

        40. TIME MANAGEMENT:

            Ưu tiên theo thứ tự:

            P0:
            - UI không sử dụng được.
            - Lỗi Responsive nghiêm trọng.
            - Nút bị che hoặc thao tác nhầm.
            - Mất Draft.
            - Payment/Order Feedback sai.
            - Modal không thể đóng.

            P1:
            - Thiếu Loading/Error States.
            - Table/Area Cards khó đọc.
            - Cart thao tác khó.
            - Accessibility quan trọng.

            P2:
            - Animation.
            - Shadow/Spacing nhỏ.
            - Hover Polish.
            - Trang trí phụ.

            Không hy sinh P0/P1
            để hoàn thiện animation P2.
    </hardRules>

    <tasks>
        <taskUnit id="01">
            <name>
                Khảo sát UI và lập UX Audit
            </name>

            <estimatedTime>
                10-15 phút.
            </estimatedTime>

            <steps>
                - Kiểm tra Branch/git status.
                - Đọc kết quả TASK 01-07.
                - Kiểm tra Admin Layout.
                - Kiểm tra Area UI.
                - Kiểm tra Table Floor Plan.
                - Kiểm tra Table Detail.
                - Kiểm tra Order-at-Table.
                - Kiểm tra Checkout UI.
                - Kiểm tra Receipt Preview.
                - Kiểm tra CSS/JS Framework.
                - Kiểm tra Existing Tests.

                - Lập UX Audit:

                  + Page.
                  + Current Issue.
                  + Viewport.
                  + Severity.
                  + Root Cause.
                  + Proposed Fix.
                  + File cần chỉnh sửa.

                - Chọn các lỗi quan trọng
                  phù hợp thời gian TASK.

                - Trình bày Mini Plan
                  trước khi code.
            </steps>
        </taskUnit>

        <taskUnit id="02">
            <name>
                Hoàn thiện Visual Design
                và Responsive Layout
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. AREA MANAGEMENT:

                - Tối ưu Area Cards.
                - Tối ưu Summary Cards.
                - Tối ưu Filter Bar.
                - Cân chỉnh Spacing/Typography.
                - Responsive Grid.

                B. TABLE FLOOR PLAN:

                - Làm nổi bật Table Code.
                - Thống nhất Status Colors.
                - Tối ưu Table Grid.
                - Dễ chuyển Area Tabs.
                - Search/Filter gọn gàng.
                - Touch-friendly Cards.

                C. TABLE DETAIL:

                - Tối ưu Detail Panel.
                - Header rõ ràng.
                - Active Order dễ nhận biết.
                - Primary Action nổi bật.
                - Drawer trên màn hình nhỏ
                  nếu phù hợp.

                D. ORDER-AT-TABLE:

                - Product Grid trực quan.
                - Category Navigation.
                - Search Input.
                - Cart Panel.
                - Quantity Controls.
                - Save Order Action.
                - Responsive Cart Drawer.

                E. CHECKOUT:

                - Order Summary rõ ràng.
                - Tổng tiền nổi bật.
                - Payment Methods dễ chọn.
                - Payment Status rõ ràng.
                - Không nhầm Checkout và Print.

                F. RECEIPT:

                - Giữ Print Layout.
                - Kiểm tra bản in không overflow.
                - Không thay đổi dữ liệu chứng từ.

                G. RESPONSIVE:

                - Mobile.
                - Tablet.
                - Laptop.
                - Desktop.

                - Không thay đổi nghiệp vụ.
            </steps>
        </taskUnit>

        <taskUnit id="03">
            <name>
                Hoàn thiện Interactions
                và UI States
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. INTERACTIONS:

                - Selected Area.
                - Selected Table.
                - Table Detail Open/Close.
                - Product Add Action.
                - Cart Quantity Controls.
                - Save Order Feedback.
                - Checkout State.
                - Safe Refresh State.

                B. LOADING:

                - Loading Area/Table.
                - Loading Table Detail.
                - Loading Products.
                - Saving Order.
                - Checkout Processing.
                - Refreshing Status.

                C. EMPTY:

                - No Areas.
                - No Tables.
                - No Search Results.
                - No Products.
                - Empty Cart.
                - Empty Order.

                D. ERROR:

                - Network Error.
                - Validation Error.
                - Unauthorized.
                - Product Unavailable.
                - Order Conflict.
                - Payment Failed.
                - Payment Pending.
                - Backend Unavailable.

                E. MODAL/DRAWER:

                - Open/Close.
                - Focus Management.
                - Keyboard Escape.
                - Overlay Behavior.
                - Prevent Accidental Data Loss.
                - Responsive Panel Layout.

                F. INTERACTION SAFETY:

                - Không tạo Request trùng.
                - Không tự Submit Payment.
                - Không tự đổi Table Status.
                - Không báo Success giả.
                - Không làm mất Draft khi lỗi.
            </steps>
        </taskUnit>

        <taskUnit id="04">
            <name>
                Kiểm thử Responsive,
                Accessibility và Regression
            </name>

            <estimatedTime>
                15-20 phút.
            </estimatedTime>

            <steps>
                A. RESPONSIVE TESTS:

                - Mobile Portrait.
                - Mobile Landscape nếu có.
                - Tablet Portrait.
                - Tablet Landscape.
                - Laptop.
                - Desktop.

                Kiểm tra:
                - Không overflow ngang.
                - Không mất Action.
                - Không che Total Amount.
                - Không cắt Table Code.
                - Drawer hoạt động.
                - Search/Filter hoạt động.
                - Product Cards thích ứng.

                B. ACCESSIBILITY:

                - Keyboard Tab.
                - Focus State.
                - Accessible Labels.
                - Modal Focus.
                - Escape Close.
                - ARIA Attributes.
                - Color Contrast.
                - Reduced Motion.

                C. FUNCTIONAL REGRESSION:

                - Area Listing/CRUD.
                - Table Floor Plan.
                - Table Detail.
                - Order Entry.
                - Product Selection.
                - Cart Management.
                - Order Save.
                - Checkout.
                - Receipt Printing.
                - Table Status Refresh.

                D. SECURITY REGRESSION:

                - Authentication.
                - Authorization.
                - CSRF không bị ảnh hưởng.
                - Không thêm Route sai.
                - Không lộ dữ liệu.

                E. PERFORMANCE:

                - JS Build.
                - CSS Build.
                - Không tạo Event Listener trùng.
                - Không có Request lặp vô ích.
                - Không tăng Query Count
                  do UI thay đổi khi có thể đo.

                F. FINAL CHECKS:

                - Chạy Feature Tests phù hợp.
                - Kiểm tra Asset Build.
                - Kiểm tra Blade Rendering.
                - Kiểm tra PHP/JS Syntax.
                - Kiểm tra git status/git diff.
                - Kiểm tra file ngoài Scope.

                - Ghi PASS/FAIL/BLOCKED/NOT RUN.

                - Không commit/push/merge.
            </steps>
        </taskUnit>

        <taskUnit id="05">
            <name>
                Final UI Review &amp; Handoff
            </name>

            <estimatedTime>
                5 phút.
            </estimatedTime>

            <steps>
                - Tổng hợp UX Audit.
                - Tổng hợp lỗi đã sửa.
                - Tổng hợp Responsive Matrix.
                - Tổng hợp Accessibility Review.
                - Xác nhận Regression Results.
                - Liệt kê blocker.
                - Liệt kê file thay đổi.
                - Chuẩn bị handoff TASK 09.
                - Xuất Final Report.
            </steps>
        </taskUnit>
    </tasks>

    <acceptanceCriteria>
        1. Đã khảo sát UI từ TASK 02-07.

        2. Có UX Audit và Mini Plan.

        3. Giao diện Area nhất quán.

        4. Table Floor Plan trực quan.

        5. Table Cards dễ đọc.

        6. Table Status rõ ràng.

        7. Table Detail Panel dễ sử dụng.

        8. Product Selection UI dễ thao tác.

        9. Order Cart dễ quan sát.

        10. Quantity Controls dễ chạm.

        11. Save Order Action rõ ràng.

        12. Checkout Total nổi bật.

        13. Payment Status rõ ràng.

        14. Không nhầm Payment và Print.

        15. Receipt Print Layout không bị hỏng.

        16. Desktop Responsive đạt yêu cầu.

        17. Tablet Responsive đạt yêu cầu.

        18. Mobile Responsive đạt yêu cầu.

        19. Không overflow ngang nghiêm trọng.

        20. Không che Action quan trọng.

        21. Có Hover/Focus/Selected States.

        22. Có Disabled/Processing States.

        23. Có Loading States phù hợp.

        24. Có Empty States phù hợp.

        25. Có Error States phù hợp.

        26. Có Conflict States phù hợp.

        27. Modal/Drawer có thể đóng an toàn.

        28. Có Keyboard Accessibility cơ bản.

        29. Form Controls có Label.

        30. Focus State rõ ràng.

        31. Status không chỉ phân biệt bằng màu.

        32. Animation không gây cản trở thao tác.

        33. Hỗ trợ Reduced Motion khi phù hợp.

        34. Không làm mất Unsaved Cart Draft
            trái quy tắc đã được xác minh.

        35. Không tạo Duplicate Submit từ UI.

        36. Không tạo Payment Request giả.

        37. Không báo Payment Success giả.

        38. Không tự thay đổi Table Status.

        39. Không thay đổi Order Total.

        40. Không thay đổi Pricing Rules.

        41. Không sửa Order/Payment Backend.

        42. Không sửa Catalog/Promotion Backend.

        43. Không sửa Authentication/Authorization.

        44. Không tạo Migration.

        45. Không thay đổi Database Schema.

        46. Không cài Package mới trái phép.

        47. Không làm hỏng TASK 02-07.

        48. Không sửa module ngoài Scope.

        49. Không tự commit/push/merge.

        50. Không báo PASS khi chưa test thật.

        51. AI Agent phải xuất FINAL REPORT:

            A. MODULE OVERVIEW
            - Chức năng UI đã hoàn thiện.
            - Chức năng chưa hoàn thiện.
            - Blocker còn tồn tại.

            B. UX AUDIT
            - Page.
            - Issue.
            - Severity.
            - Root Cause.
            - Fix.
            - Verification Status.

            C. DESIGN SYSTEM
            - Colors.
            - Typography.
            - Spacing.
            - Cards.
            - Buttons.
            - Icons.
            - Status Indicators.

            D. RESPONSIVE REVIEW
            - Desktop.
            - Laptop.
            - Tablet.
            - Mobile.
            - Viewport đã kiểm thử.
            - Layout Problems.
            - Fixed Issues.
            - Remaining Issues.

            E. INTERACTION REVIEW
            - Area Navigation.
            - Table Selection.
            - Table Detail.
            - Product Selection.
            - Cart Controls.
            - Order Save.
            - Checkout.
            - Receipt Preview.

            F. UI STATES
            - Loading.
            - Empty.
            - No Results.
            - Error.
            - Unauthorized.
            - Conflict.
            - Success.
            - Pending/Processing.

            G. ACCESSIBILITY
            - Keyboard Navigation.
            - Focus Management.
            - ARIA Attributes.
            - Touch Targets.
            - Color Contrast.
            - Reduced Motion.

            H. PERFORMANCE
            - Asset Build.
            - UI Rendering.
            - JavaScript Issues.
            - Duplicate Requests.
            - Performance Limitations.

            I. BUSINESS INTEGRITY
            - Area/Cafe Table.
            - Order.
            - Payment.
            - Receipt.
            - Table Status.
            - No Backend Logic Changes.

            J. FILE CHANGES
            - File tạo mới.
            - File chỉnh sửa.
            - Mục đích thay đổi.
            - Xác nhận không sửa ngoài Scope.

            K. TEST RESULTS
            - Test ID.
            - Scenario.
            - Viewport.
            - Expected Result.
            - Actual Result.
            - PASS/FAIL/BLOCKED/NOT RUN.
            - Test Environment.
            - Evidence nếu có.

            L. REGRESSION REPORT
            - TASK 02.
            - TASK 03.
            - TASK 04.
            - TASK 05.
            - TASK 06.
            - TASK 07.
            - Admin Layout.
            - Authentication/Authorization.

            M. NEXT TASK HANDOFF
            - UI Components đã hoàn thiện.
            - Responsive Matrix.
            - Known UI Issues.
            - Test Cases cho TASK 09.
            - Integration Dependencies.
            - Các rủi ro còn lại.

            N. FINAL STATUS
            - READY FOR REVIEW hoặc
              NOT READY FOR REVIEW.
            - Blocker.
            - Những việc cần hoàn thiện.

        52. Chỉ đánh dấu TASK 08 hoàn thành
            khi những vấn đề UI nghiêm trọng
            được giải quyết và các
            kiểm thử tương ứng có kết quả.

        53. Nếu không có Browser/Viewport Tools,
            không được tuyên bố đã kiểm chứng
            Responsive bằng mắt.

        54. Không tuyên bố toàn bộ
            Area &amp; Table POS hoàn thành
            trước TASK 09.
    </acceptanceCriteria>
</prompt>
```