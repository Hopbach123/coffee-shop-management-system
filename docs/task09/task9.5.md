```xml
<?xml version="1.0" encoding="UTF-8"?>
<prompt>
    <goal>
        TRIỂN KHAI TASK 05:
        ORDER-AT-TABLE UI INTEGRATION

        DỰ ÁN:
        Coffee Shop Management System.

        MODULE:
        Area &amp; Table POS Management.

        BRANCH:
        feature/area-table-pos-ui.

        THỜI GIAN:
        60-90 phút.

        CÔNG NGHỆ:
        PHP Laravel, MySQL, Blade,
        JavaScript và công nghệ UI
        thực tế đang sử dụng trong dự án.

        MỤC TIÊU:

        Xây dựng giao diện nhận Order
        tại bàn theo phong cách Coffee POS
        hiện đại, trực quan và chuyên nghiệp.

        Giao diện phải cho phép:

        1. Nhận Table ID và Order ID hợp lệ
           từ luồng TASK 04.

        2. Hiển thị thông tin bàn:
           - Mã bàn.
           - Khu vực.
           - Trạng thái.
           - Mã Order đang phục vụ.

        3. Đọc danh sách sản phẩm
           từ Catalog/POS hiện có.

        4. Hiển thị sản phẩm dưới dạng
           Product Cards trực quan.

        5. Tìm kiếm sản phẩm theo tên
           hoặc mã nếu dữ liệu hỗ trợ.

        6. Lọc sản phẩm theo danh mục
           thực tế trong database.

        7. Thêm món vào giỏ hàng/order draft.

        8. Điều chỉnh số lượng món.

        9. Ghi chú cho từng món
           nếu nghiệp vụ hỗ trợ.

        10. Hiển thị giỏ hàng gồm:
            - Sản phẩm.
            - Số lượng.
            - Đơn giá hiển thị hợp lệ.
            - Thành tiền dự kiến.
            - Ghi chú nếu có.

        11. Hiển thị các Order Items
            đã được lưu trước đó.

        12. Phân biệt:
            - Món đã lưu vào Order.
            - Món chưa lưu (draft).

        13. Gửi thao tác lưu món đến
            Order Controller/Service/API
            hiện có của Đạt.

        14. Sau khi lưu thành công:
            - Đồng bộ giỏ hàng.
            - Hiển thị kết quả từ backend.
            - Cập nhật Order Summary.
            - Hiển thị thông báo thành công.

        15. Xử lý:
            - Validation Error.
            - Product Unavailable.
            - Order Conflict.
            - Duplicate Submit.
            - Backend Failure.
            - Authorization Failure.

        16. Responsive:
            - Desktop.
            - Tablet.
            - Mobile.

        17. Kiểm thử tính chính xác
            của quá trình lưu Order Items.

        KẾT QUẢ MONG MUỐN:

        Nhân viên có thể chọn một bàn
        đang phục vụ, mở đúng Order,
        tìm món, chọn số lượng, ghi chú
        và lưu vào Order thực tế.

        Sau khi lưu, dữ liệu Order Items
        phải khớp backend và MySQL.

        Không tự tính giá bán chính thức
        tại frontend.

        Không tự tạo Order Service mới.

        Không thay đổi Payment Status.

        Không triển khai Checkout,
        Invoice hoặc Receipt trong TASK 05.
    </goal>

    <context>
        PROJECT:
        Coffee Shop Management System.

        OWNER:
        Thành Toàn.

        MODULE:
        Area &amp; Table POS Management.

        TEAM RESPONSIBILITIES:

        THÀNH TOÀN:
        - Area Management.
        - Cafe Table Management.
        - Table Floor Plan UI.
        - Table Detail UI.
        - Order-at-Table UI Integration.
        - Reservation Management.

        ĐẠT:
        - POS Backend.
        - Order Management.
        - Order Items.
        - Order Persistence.
        - Payment.
        - Receipt Backend.

        NINH:
        - Product Catalog.
        - Product Categories.
        - Product Availability.
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

        EXPECTED USER FLOW:

        Chọn khu vực
             |
             v
        Chọn bàn
             |
             v
        Mở Table Detail
             |
             v
        Xác định Order hợp lệ
             |
             v
        Mở Order-at-Table UI
             |
             v
        Chọn danh mục/sản phẩm
             |
             v
        Thêm món vào Draft
             |
             v
        Chỉnh số lượng/ghi chú
             |
             v
        Lưu Order Items bằng POS Backend
             |
             v
        Nhận Order Summary từ backend
             |
             v
        Hiển thị kết quả đã lưu.

        DATABASE:

        Các bảng có thể liên quan:
        - areas.
        - cafe_tables.
        - orders.
        - order_items.
        - products.
        - categories.
        - inventory.

        Đây là danh sách tham khảo.

        Không mặc định các bảng này
        tồn tại với đúng tên như trên.

        Phải khảo sát migration,
        Eloquent Model và quan hệ thực tế.

        QUAN TRỌNG:

        TASK 04 đã định hướng:
        - Mở đúng Table.
        - Xác định Active Order.
        - Create/Continue Order Entry.

        TASK 05 sử dụng Order ID
        đã được backend xác minh.

        Nếu Create/Continue Order
        chưa hoàn thành hoặc chưa có API,
        không được tự xây dựng
        nghiệp vụ Order thay thế.

        Phải báo cáo BLOCKED cho phần
        phụ thuộc chưa sẵn sàng.

        SOURCE OF TRUTH:

        - Product Catalog của Ninh.
        - Order/POS Backend của Đạt.
        - Inventory Service của Hợp
          nếu nghiệp vụ có liên kết.
        - Authentication/Authorization hiện có.

        Frontend không được trở thành
        nguồn dữ liệu có thẩm quyền
        cho giá, tồn kho hoặc thanh toán.
    </context>

    <scope>
        PHẠM VI TRIỂN KHAI:

        A. SOURCE CODE SURVEY:

        - TASK 01-04 Implementation.
        - Table Floor Plan.
        - Table Detail.
        - Order Model.
        - Order Items Model.
        - Product/Catalog Models.
        - Existing POS Routes.
        - Existing Order Services.
        - Inventory Integration nếu có.
        - Authentication/Authorization.
        - Existing Tests.

        B. ORDER-AT-TABLE PAGE:

        - Table Information Header.
        - Product Category Navigation.
        - Product Search.
        - Product Grid.
        - Order Draft Panel.
        - Saved Order Items Summary.
        - Save Order Action.

        C. PRODUCT DISPLAY:

        - Product Name.
        - Product Image nếu có.
        - Product Category.
        - Price từ nguồn hợp lệ.
        - Availability.
        - Product Variants/Options
          nếu hệ thống hiện có hỗ trợ.

        D. CART/DRAFT:

        - Add Item.
        - Adjust Quantity.
        - Remove Unsaved Item.
        - Per-item Notes.
        - Draft Total Estimate.
        - Save Draft to Order.

        E. ORDER INTEGRATION:

        - Existing Order ID.
        - Existing Order Items.
        - Order Item Write Contract.
        - Backend Validation.
        - Server Response Handling.
        - Updated Order Summary.

        F. UI/UX:

        - Modern Coffee POS Layout.
        - Responsive Product Cards.
        - Order Panel.
        - Search/Filter Controls.
        - Loading/Error/Conflict States.
        - Accessible Actions.

        G. TESTING:

        - Product Loading.
        - Product Filtering.
        - Cart Interaction.
        - Quantity Validation.
        - Notes Validation.
        - Order Persistence.
        - Authorization.
        - Duplicate Submit.
        - Regression.

        CÁC FILE DỰ KIẾN:

        - resources/views/admin/pos/
          table-order.blade.php

        - resources/views/admin/pos/
          components/product-card.blade.php

        - resources/views/admin/pos/
          components/order-cart.blade.php

        - resources/js/admin/
          table-order.js

        - app/Http/Controllers/Admin/
          TableOrderUIController.php

        - tests/Feature/
          TableOrderIntegrationTest.php

        - routes/web.php

        Đây chỉ là đường dẫn đề xuất.

        Phải xác minh cấu trúc dự án.

        Nếu đã có giao diện POS tương ứng,
        ưu tiên tái sử dụng hoặc tích hợp.

        Không tạo Controller/Service trùng.

        KHÔNG TRIỂN KHAI:

        - Order Business Logic mới.
        - Payment Processing.
        - Checkout.
        - Receipt Printing.
        - Invoice Generation.
        - Product CRUD.
        - Category CRUD.
        - Promotion Engine.
        - Tax Calculation mới.
        - Inventory Management mới.
        - Table Status Synchronization mới.
        - Database Migration.
        - Database Schema Changes.
        - Chuyển/Gộp/Tách bàn.
    </scope>

    <hardRules>
        1. BẮT BUỘC kiểm tra source code
           trước khi triển khai.

        2. Kiểm tra:
           - Current Branch.
           - git status.
           - git diff.
           - TASK 01 Architecture.
           - TASK 02 Area UI.
           - TASK 03 Floor Plan.
           - TASK 04 Table Detail.
           - POS Routes.
           - Order Services.
           - Order Items Model.
           - Catalog Services.
           - Product Status.
           - Authentication/Authorization.
           - Existing Tests.

        3. TRƯỚC KHI CODE:

           AI Agent phải trình bày:
           - Current POS Architecture.
           - Active Order Contract.
           - Product Data Contract.
           - Order Item Write Contract.
           - Quantity/Notes Rules.
           - Pricing Source of Truth.
           - Inventory Rules nếu có.
           - Authorization.
           - Existing/Partial/Missing APIs.
           - Files dự kiến chỉnh sửa.
           - Mini Plan.

        4. Không tự tạo Migration.

        5. Không sửa Database Schema.

        6. Không tự cài Package mới
           khi chưa được phê duyệt.

        7. Không sửa Order Service của Đạt.

        8. Không sửa Catalog của Ninh.

        9. Không sửa Inventory của Hợp.

        10. Không sửa Authentication/
            Authorization của Hợp.

        11. Không xây dựng Payment Logic.

        12. Không thay đổi Reservation Logic.

        13. ORDER CONTEXT:

            - Phải có Table ID hợp lệ.
            - Phải có Order ID hợp lệ.
            - Kiểm tra Order thuộc Table.
            - Kiểm tra quyền truy cập.
            - Kiểm tra Order còn cho phép
              bổ sung hoặc chỉnh sửa món.
            - Không dùng Order đã hoàn tất
              để nhận thêm món trái policy.
            - Không mở Order của bàn khác.

        14. ORDER CREATION BOUNDARY:

            - TASK 04 phụ trách tạo/mở Order.
            - TASK 05 thao tác với Order
              đã xác định hợp lệ.
            - Không tự INSERT vào orders.
            - Không tạo đơn mới
              khi người dùng chỉ mở Cart.
            - Nếu chưa có Order,
              sử dụng luồng TASK 04
              khi đã được xác minh.

        15. PRODUCT SOURCE:

            - Đọc từ Catalog thật.
            - Không hardcode Product.
            - Không tạo Product giả.
            - Không tự tạo Category.
            - Không tự thay đổi giá.
            - Không tự bật Product đã ngừng bán.
            - Không hiển thị Product
              không được phép bán trái policy.

        16. PRODUCT AVAILABILITY:

            Phải xác minh:
            - Product Active/Inactive.
            - Available/Unavailable.
            - Inventory Rules nếu có.
            - Product Variant Rules.
            - Price Rules.

            Nếu sản phẩm hết hàng,
            thao tác đặt món phải được
            backend kiểm tra lại.

            Frontend chỉ hỗ trợ thông báo.

        17. PRODUCT VARIANTS:

            Nếu POS có:
            - Size.
            - Topping.
            - Options.
            - Product Variants.

            Phải tái sử dụng Contract đã có.

            Không tự định nghĩa cơ chế
            tính giá riêng cho Variant.

            Không gộp các dòng món
            có Variant/Options khác nhau
            trái nghiệp vụ.

            Nếu không hỗ trợ,
            không tạo UI giả.

        18. CART STATE:

            Phân biệt rõ:

            A. Saved Order Items:
               Dữ liệu đã được backend lưu.

            B. Unsaved Draft Items:
               Dữ liệu người dùng đang soạn.

            Không mặc định món mới thêm
            đã được lưu vào MySQL.

            Không hiển thị thông báo
            lưu thành công trước phản hồi API.

            Tránh cộng trùng Saved Items
            với Draft Items trong tổng.

        19. ADD TO CART:

            - Chỉ nhận Product hợp lệ.
            - Validate Product ID.
            - Kiểm tra Product Availability.
            - Kiểm tra số lượng.
            - Giữ đúng Variant/Options nếu có.
            - Không tự tạo dữ liệu Order Item
              trong database từ thao tác
              chỉ dùng để chỉnh Draft.

        20. QUANTITY:

            - Chỉ chấp nhận kiểu số
              theo quy tắc nghiệp vụ.
            - Mặc định số nguyên dương
              nếu POS không cho phép
              số lượng phân số.
            - Kiểm tra giới hạn min/max.
            - Không cho giá trị âm.
            - Không cho NaN hoặc Infinity.
            - Không bỏ qua Validation backend.
            - Không tự giới hạn số lượng
              bằng giá trị tùy ý.

        21. REMOVE ITEM:

            - Cho phép bỏ Draft Item.
            - Không mặc định được xóa
              Saved Order Item.
            - Việc sửa/xóa món đã lưu
              phải dùng Contract hiện có
              và quyền phù hợp.
            - Nếu Backend không hỗ trợ,
              không triển khai thao tác giả.

        22. ITEM NOTES:

            - Chỉ hỗ trợ nếu Contract có Notes.
            - Validation độ dài theo schema.
            - Không lưu Script/HTML nguy hiểm.
            - Escape khi render Blade.
            - Không ghi chú vào trường
              không đúng mục đích.
            - Không sửa Notes của món khác.

        23. PRICING:

            - Đơn giá hiển thị lấy từ nguồn
              Catalog/POS đã xác minh.
            - Tổng Draft trên UI
              chỉ được xem là dự kiến.
            - Backend quyết định:
              + Price.
              + Promotion.
              + Discount.
              + Tax.
              + Final Order Total.

            - Không gửi tổng tiền do client
              tự tính như giá trị đáng tin cậy.
            - Không cho client tự sửa unit_price
              trái hợp đồng backend.
            - Nếu giá thay đổi lúc lưu,
              hiển thị giá backend xác nhận.

        24. ORDER ITEM WRITE:

            - Tái sử dụng POS API/Service.
            - Chỉ gửi field được cho phép.
            - Không sử dụng request->all()
              để Mass Assignment tùy ý.
            - Không INSERT trực tiếp
              vào order_items từ UI Controller.
            - Không bypass Order Validation.
            - Không tự thay đổi Order Status.
            - Không sửa Payment Status.
            - Không thay đổi Table Status.

        25. SAVE ORDER:

            - Kiểm tra Order còn hợp lệ.
            - Kiểm tra Authorization.
            - Kiểm tra Product Availability.
            - Kiểm tra Quantity/Options.
            - Backend xác nhận transaction.
            - Nhận Order Items và tổng tiền
              được backend xác nhận.
            - Chỉ xóa Draft sau khi
              lưu thành công.
            - Nếu API lỗi, giữ Draft
              để người dùng xử lý phù hợp.

        26. PARTIAL FAILURE:

            - Xác định API lưu theo Batch
              hay theo từng Item.
            - Không mặc định Atomic Batch
              nếu Backend không hỗ trợ.
            - Không hiển thị toàn bộ thành công
              khi một số Item bị từ chối.
            - Phân biệt Saved/Unsaved Items.
            - Nếu cần Atomicity chưa được hỗ trợ,
              báo cáo yêu cầu cho Đạt.

        27. CONCURRENCY:

            - Hai nhân viên có thể sửa
              cùng một Order.
            - Product Availability có thể đổi.
            - Order có thể được Checkout
              trong lúc nhân viên thêm món.
            - Giá có thể thay đổi.

            Frontend phải:
            - Chặn Double Submit.
            - Có Loading State.
            - Xử lý Conflict.
            - Làm mới Order Summary
              sau khi backend trả kết quả.

            Không tự triển khai
            Database Locking trong UI.

            Nếu Backend chưa có cơ chế
            Idempotency/Concurrency phù hợp,
            ghi BLOCKED hoặc rủi ro rõ ràng.

        28. SECURITY:

            - Authentication bắt buộc.
            - Authorization tại Backend.
            - CSRF với các request ghi.
            - Không dùng GET để lưu món.
            - Không tin Order ID từ Client.
            - Không cho IDOR:
              sửa Order thuộc bàn khác.
            - Không cho Mass Assignment.
            - Escape Product Name/Notes.
            - Không lộ SQL Error thô.

        29. UX DESIGN:

            Giao diện theo hướng
            Modern Coffee POS.

            Ưu tiên:
            - Chọn món nhanh.
            - Ít thao tác.
            - Danh mục rõ ràng.
            - Product Cards trực quan.
            - Cart luôn dễ quan sát.
            - Quantity Controls dễ chạm.
            - Save Action nổi bật.
            - Thông tin bàn dễ nhận biết.

            Không sao chép nguyên bản
            UI hoặc tài sản của Sapo.

        30. DESKTOP LAYOUT:

            Gợi ý bố cục:

            LEFT:
            - Table Information.
            - Category Navigation.
            - Search.
            - Product Grid.

            RIGHT:
            - Current Order.
            - Draft Items.
            - Quantity.
            - Notes.
            - Estimated Total.
            - Save Order.

            Không bắt buộc bố cục này
            nếu Admin Layout không phù hợp.

        31. TABLET/MOBILE:

            - Product Grid thích ứng.
            - Cart có thể mở dạng Drawer.
            - Không che nút Save.
            - Button đủ lớn để chạm.
            - Không mất Table Context.
            - Không overflow ngang.
            - Có cách quay về Floor Plan.

        32. ACCESSIBILITY:

            - Buttons có nhãn.
            - Keyboard Focus rõ ràng.
            - Input Quantity có Label.
            - Search dễ thao tác.
            - Error Message rõ ràng.
            - Cart Update có Feedback.
            - Không chỉ dùng màu
              để thể hiện trạng thái.

        33. UI STATES:

            - Loading Products.
            - No Products.
            - No Search Results.
            - Empty Cart.
            - Unsaved Changes.
            - Saving Order.
            - Save Success.
            - Validation Error.
            - Product Unavailable.
            - Order Closed.
            - Unauthorized.
            - Backend Unavailable.
            - Conflict.

        34. UNSAVED CHANGES:

            - Nếu rời khỏi trang khi còn Draft,
              cảnh báo hoặc giữ Draft theo
              quy tắc UI đã được duyệt.
            - Không tự lưu Order
              chỉ vì người dùng đóng trang.
            - Không tự lưu dữ liệu nhạy cảm
              vào Local Storage không kiểm soát.
            - Không làm mất Draft âm thầm
              khi API trả lỗi.

        35. PERFORMANCE:

            - Tránh N+1 Query.
            - Không tải toàn bộ Product History.
            - Không query riêng cho từng Product.
            - Ưu tiên Catalog Query hiện có.
            - Search/Pagination theo dữ liệu thật.
            - Không tự thêm Polling liên tục.
            - Không tạo nhiều request lưu
              chỉ vì người dùng điều chỉnh Draft.

        36. DATABASE SAFETY:

            - Không chạy migrate:fresh
              trên database dùng chung.
            - Không chạy db:wipe.
            - Không truncate dữ liệu.
            - Không tạo Order trên Production
              để thử nghiệm.
            - Tests ghi dữ liệu phải sử dụng
              Test Database độc lập.

        37. GIT SAFETY:

            - Không tự động commit.
            - Không tự động push.
            - Không tự động merge.
            - Không ghi đè code chưa commit
              của thành viên khác.
            - Không chuyển branch khi
              working tree chưa an toàn.

        38. TEST INTEGRITY:

            - PASS chỉ khi test chạy thành công.
            - FAIL nếu kết quả sai.
            - BLOCKED nếu thiếu API/Contract.
            - NOT RUN nếu chưa kiểm thử.
            - NOT APPLICABLE nếu không hỗ trợ
              theo thiết kế đã được xác minh.
            - Không tuyên bố UI đã kiểm tra
              trên Browser khi chưa thực hiện.

        39. SCOPE BOUNDARY:

            - Không triển khai Checkout.
            - Không triển khai Payment.
            - Không triển khai Receipt.
            - Không triển khai Invoice.
            - Không xây dựng lại POS.
            - Không sửa Product CRUD.
            - Không xây dựng Table State Sync mới.
            - Không tự thêm nghiệp vụ ngoài TASK 05.
    </hardRules>

    <tasks>
        <taskUnit id="01">
            <name>
                Khảo sát Order/Catalog Integration
            </name>

            <estimatedTime>
                10-15 phút.
            </estimatedTime>

            <steps>
                - Kiểm tra Current Branch.
                - Kiểm tra git status/git diff.
                - Đọc TASK 01-04.
                - Kiểm tra Table Order Entry.
                - Kiểm tra POS Routes.
                - Kiểm tra Order Controller.
                - Kiểm tra Order Service.
                - Kiểm tra Order Items.
                - Kiểm tra Product Catalog.
                - Kiểm tra Pricing Rules.
                - Kiểm tra Auth/Permissions.
                - Kiểm tra Existing Tests.

                - Xác định:
                  + Current Order Contract.
                  + Product Listing Contract.
                  + Add Items Contract.
                  + Update Items Contract nếu có.
                  + Notes Contract.
                  + Availability Rules.
                  + Final Total Source.
                  + Error/Conflict Handling.

                - Báo cáo API:
                  EXISTING/PARTIAL/MISSING/BLOCKED.

                - Liệt kê file sẽ sửa.
                - Trình bày Mini Plan.
            </steps>
        </taskUnit>

        <taskUnit id="02">
            <name>
                Xây dựng Product Selection UI
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. PRODUCT DATA:

                - Tái sử dụng Catalog Backend.
                - Đọc Product hợp lệ.
                - Đọc Product Categories.
                - Đọc Price hiện có.
                - Đọc Availability.
                - Không hardcode Product.

                B. CATEGORY NAVIGATION:

                - Danh sách Categories.
                - Active Category.
                - Filter theo Category.
                - Search theo Product Name.
                - Clear Filters.

                C. PRODUCT CARDS:

                - Product Image nếu có.
                - Product Name.
                - Product Price.
                - Availability.
                - Add to Cart Action.

                D. TABLE CONTEXT:

                - Hiển thị Table Code.
                - Hiển thị Area.
                - Hiển thị Order ID.
                - Hiển thị Order Status.

                E. UX:

                - Product Grid.
                - Search Bar.
                - Category Navigation.
                - Loading/Empty/Error States.
                - Responsive Cards.

                F. TESTS:

                - Product từ database.
                - Category Filter.
                - Product Search.
                - Product Availability.
                - Không hiển thị dữ liệu giả.
                - Không có N+1 nghiêm trọng.
            </steps>
        </taskUnit>

        <taskUnit id="03">
            <name>
                Xây dựng Order Cart
                và tích hợp Save Order Items
            </name>

            <estimatedTime>
                25-30 phút.
            </estimatedTime>

            <steps>
                A. DRAFT CART:

                - Thêm Product vào Cart.
                - Điều chỉnh Quantity.
                - Xóa Unsaved Item.
                - Nhập Notes nếu hỗ trợ.
                - Hiển thị Draft Estimate.
                - Phân biệt Saved/Draft Items.

                B. ORDER INTEGRATION:

                - Sử dụng Order ID hợp lệ.
                - Xác minh Order thuộc Table.
                - Kiểm tra Order còn được sửa.
                - Gửi dữ liệu theo API Contract.
                - Không tự Insert Order Items.
                - Không tự tính Final Total.

                C. SAVE WORKFLOW:

                - Validate Draft.
                - Disable Save khi đang Submit.
                - Gửi request hợp lệ.
                - Xử lý Server Validation.
                - Xử lý Conflict.
                - Xử lý Partial Failure.
                - Nhận response từ Backend.
                - Refresh Saved Order Items.
                - Refresh Order Summary.
                - Thông báo Success/Failure.

                D. SECURITY:

                - Authentication.
                - Authorization.
                - CSRF.
                - Order/Table Identity.
                - Product Validation.
                - Mass Assignment Protection.
                - Price Tampering Protection
                  tại Backend hiện có.

                E. TESTS:

                - Add Product to Draft.
                - Adjust Quantity.
                - Remove Draft Item.
                - Item Notes.
                - Save Order Items.
                - Invalid Product.
                - Invalid Quantity.
                - Order Closed.
                - Unauthorized Request.
                - Duplicate Submit.
                - Backend Error.
            </steps>
        </taskUnit>

        <taskUnit id="04">
            <name>
                Testing, Regression
                và Final Review
            </name>

            <estimatedTime>
                15-20 phút.
            </estimatedTime>

            <steps>
                A. FUNCTIONAL TESTS:

                - Open Order-at-Table UI.
                - Load Correct Table.
                - Load Correct Order.
                - Load Products.
                - Search Products.
                - Filter Categories.
                - Add to Draft.
                - Adjust Quantity.
                - Add Notes.
                - Remove Draft Item.
                - Save Items.
                - Reload Saved Items.
                - Compare Backend Total.

                B. VALIDATION TESTS:

                - Product không tồn tại.
                - Product ngừng bán.
                - Quantity âm.
                - Quantity bằng 0.
                - Quantity vượt giới hạn.
                - Notes quá dài.
                - Invalid Variants nếu có.
                - Order đã đóng.
                - Order thuộc Table khác.

                C. SECURITY TESTS:

                - Guest.
                - Unauthorized Staff.
                - Order ID Tampering.
                - Table ID Tampering.
                - CSRF.
                - XSS Notes/Product Name.
                - Price Tampering.
                - Double Submit.

                D. CONCURRENCY:

                - Two Staff Edit Same Order.
                - Order Closed During Editing.
                - Product Price Changed.
                - Product Becomes Unavailable.
                - Duplicate Request Handling.

                Nếu Backend chưa hỗ trợ,
                không tự sửa nghiệp vụ.
                Ghi BLOCKED/RISK.

                E. UI TESTS:

                - Desktop.
                - Tablet.
                - Mobile.
                - Product Grid.
                - Cart Panel.
                - Quantity Controls.
                - Loading/Error States.
                - Keyboard Accessibility.

                F. REGRESSION:

                - TASK 02 Area UI.
                - TASK 03 Floor Plan.
                - TASK 04 Table Detail.
                - Existing POS.
                - Catalog.
                - Authentication/Authorization.

                G. FINAL CHECKS:

                - Chạy Feature Tests.
                - Kiểm tra route:list.
                - Kiểm tra PHP Syntax.
                - Kiểm tra Blade.
                - Kiểm tra JS Build nếu có.
                - Kiểm tra git status/git diff.
                - Kiểm tra file ngoài Scope.

                - Xuất Final Report.
                - Không commit/push/merge.
            </steps>
        </taskUnit>
    </tasks>

    <acceptanceCriteria>
        1. Đã khảo sát TASK 01-04.

        2. Đã xác minh Product Catalog Contract.

        3. Đã xác minh Order Items Contract.

        4. Có giao diện Order-at-Table.

        5. Hiển thị đúng Table Context.

        6. Hiển thị đúng Order ID.

        7. Không mở Order của bàn khác.

        8. Danh sách Product lấy từ Backend.

        9. Không hardcode Product.

        10. Có Category Navigation.

        11. Có Product Search.

        12. Product Cards hiển thị đúng.

        13. Hiển thị Price từ nguồn hợp lệ.

        14. Product Availability được xử lý.

        15. Có chức năng Add to Draft.

        16. Có chức năng Adjust Quantity.

        17. Có chức năng Remove Draft Item.

        18. Có Notes nếu Backend hỗ trợ.

        19. Phân biệt Saved/Draft Items.

        20. Không lưu Draft tự động
            khi chỉ chọn Product.

        21. Không tự tạo Order mới.

        22. Save sử dụng POS Backend.

        23. Order Items được lưu chính xác.

        24. Không insert Order Items trực tiếp
            từ UI Controller.

        25. Có Validation cho Product/Quantity.

        26. Có xử lý Item Notes an toàn.

        27. Không tin Client Unit Price.

        28. Không tự quyết định Final Total.

        29. Order Summary lấy từ Backend.

        30. Có xử lý Backend Errors.

        31. Có xử lý Partial Failure
            nếu Contract hỗ trợ.

        32. Không báo Save Success giả.

        33. Có Loading/Disabled States.

        34. Có biện pháp chặn Double Submit UI.

        35. Concurrency Risk được xác minh
            hoặc ghi rõ blocker.

        36. Có Authentication.

        37. Có Backend Authorization.

        38. Có CSRF Protection.

        39. Có kiểm tra Order/Table Identity.

        40. Không có IDOR trong các case đã test.

        41. Không sửa Order Business Logic.

        42. Không sửa Product Catalog Logic.

        43. Không sửa Inventory Logic.

        44. Không sửa Payment Logic.

        45. Không thay đổi Table Status.

        46. Không tạo Migration.

        47. Không sửa Database Schema.

        48. Responsive Desktop/Tablet/Mobile.

        49. Có Accessibility cơ bản.

        50. Không làm hỏng TASK 02-04.

        51. Không tự động commit/push/merge.

        52. Không báo PASS khi chưa chạy test.

        53. Nếu thiếu Order Items API,
            phần Save phải báo BLOCKED;
            không giả lập lưu thành công.

        54. AI Agent phải xuất FINAL REPORT:

            A. MODULE OVERVIEW
            - Chức năng hoàn thành.
            - Chức năng còn thiếu.
            - Blocker.

            B. SOURCE CODE SURVEY
            - Table/Order Integration.
            - Product Catalog.
            - Order Items.
            - POS Routes/Services.
            - Authorization.

            C. PRODUCT SELECTION
            - Product Data Source.
            - Category Filtering.
            - Product Search.
            - Availability.
            - Product Cards.

            D. ORDER CART
            - Draft State.
            - Saved Items State.
            - Quantity.
            - Notes.
            - Remove Draft.
            - Unsaved Changes Handling.

            E. ORDER ITEMS INTEGRATION
            - Backend Route/Service.
            - HTTP Method.
            - Request Contract.
            - Response Contract.
            - Validation.
            - Save Workflow.
            - Error/Conflict Handling.
            - Atomicity nếu có.

            F. PRICING SAFETY
            - Product Price Source.
            - Draft Estimate.
            - Backend Final Total.
            - Promotion/Tax Dependencies.
            - Price Change Handling.

            G. CONCURRENCY
            - Double Submit.
            - Concurrent Editing.
            - Closed Order.
            - Unavailable Product.
            - Idempotency/Locking Status.
            - Backend Limitations.

            H. UI/UX
            - Product Grid.
            - Search/Category.
            - Cart Panel.
            - Save Button.
            - Loading/Error States.
            - Desktop/Tablet/Mobile.
            - Accessibility.

            I. SECURITY
            - Authentication.
            - Authorization.
            - CSRF.
            - Order/Table ID Validation.
            - Product Validation.
            - Price Tampering.
            - XSS.
            - Mass Assignment.

            J. FILE CHANGES
            - File tạo mới.
            - File chỉnh sửa.
            - Lý do thay đổi.
            - Xác nhận không sửa ngoài Scope.

            K. ROUTES
            - HTTP Method.
            - URL/Route Name.
            - Controller/Service.
            - Middleware/Policy.

            L. TEST RESULTS
            - Test ID.
            - Scenario.
            - Expected Result.
            - Actual Result.
            - PASS/FAIL/BLOCKED/NOT RUN.
            - Test Commands.
            - Test Database.
            - Evidence nếu có.

            M. REGRESSION
            - TASK 02.
            - TASK 03.
            - TASK 04.
            - POS.
            - Catalog.
            - Authentication/Authorization.

            N. NEXT TASK HANDOFF
            - Active Order Contract.
            - Saved Order Items Contract.
            - Order Summary Source.
            - Backend Total Source.
            - Payment Readiness.
            - Dependencies cho TASK 06.
            - Blocker cần gửi Đạt/Ninh/Hợp.

            O. FINAL STATUS
            - READY FOR REVIEW
              hoặc NOT READY FOR REVIEW.
            - Blocker còn lại.
            - Rủi ro kỹ thuật.
            - Những việc cần xác nhận.

        55. Chỉ đánh dấu TASK 05 hoàn thành
            khi giao diện Product/Cart hoạt động,
            dữ liệu Order Items được lưu
            bằng Backend thực tế và
            các kiểm thử cốt lõi đạt yêu cầu.

        56. Không tuyên bố chức năng Checkout,
            Payment, Receipt hoặc Invoice
            đã hoàn thành trong TASK 05.
    </acceptanceCriteria>
</prompt>
```