```xml
<?xml version="1.0" encoding="UTF-8"?>
<prompt>
    <goal>
        TRIỂN KHAI TASK 06:
        CHECKOUT &amp; INVOICE PRINTING INTEGRATION

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
        và các công nghệ thực tế của dự án.

        MỤC TIÊU CHÍNH:

        1. Khảo sát kết quả TASK 01-05.

        2. Khảo sát module Payment, Checkout
           và Receipt/Invoice hiện có.

        3. Tích hợp chức năng xem tạm tính
           của Order đang phục vụ tại bàn.

        4. Hiển thị thông tin thanh toán:
           - Mã bàn.
           - Khu vực.
           - Mã Order.
           - Danh sách món đã lưu.
           - Số lượng.
           - Đơn giá chính thức.
           - Thành tiền.
           - Giảm giá nếu có.
           - Thuế/phí nếu có.
           - Tổng tiền cần thanh toán.
           - Số tiền đã thanh toán nếu có.
           - Số tiền còn lại nếu có.

        5. Tái sử dụng dữ liệu tổng tiền
           do POS/Order Backend xác nhận.

        6. Xây dựng Checkout UI hiện đại.

        7. Tích hợp điều hướng hoặc thao tác
           thanh toán thông qua Payment
           Controller/Service/API hiện có.

        8. Hiển thị phương thức thanh toán
           theo những phương thức thực tế
           đã được hệ thống hỗ trợ.

        9. Xử lý phản hồi từ Payment Backend:
           - Success.
           - Failed.
           - Pending.
           - Cancelled nếu có.
           - Partial Payment nếu hỗ trợ.
           - Unknown/Unconfirmed Result.

        10. Chỉ xác nhận thành công
            sau khi Backend xác minh
            trạng thái thanh toán chính thức.

        11. Tích hợp giao diện xem
            phiếu tạm tính/biên nhận/hóa đơn
            theo các chức năng đã có.

        12. Tạo giao diện in phù hợp
            với cơ chế hiện tại:
            - Print Preview.
            - Printable Receipt.
            - Browser Printing hoặc
              Print Service đã tích hợp.

        13. Phân biệt:
            - Phiếu tạm tính.
            - Biên nhận thanh toán.
            - Hóa đơn điện tử hợp lệ.

        14. Bảo vệ thao tác thanh toán:
            - Không thanh toán trùng.
            - Không xác nhận giao dịch giả.
            - Không tin số tiền từ client.
            - Không bypass Authorization.
            - Không tự sửa Payment Status.

        15. Bảo đảm không tự giải phóng bàn
            bằng thao tác in hoặc
            thay đổi giao diện.

        16. Xử lý:
            - Loading State.
            - Payment Failed.
            - Pending Payment.
            - Duplicate Request.
            - Stale Order.
            - Backend Unavailable.
            - Printing Error.
            - Authorization Failure.

        17. Kiểm thử chức năng Checkout,
            Payment Integration và Printing.

        KẾT QUẢ MONG MUỐN:

        Nhân viên có quyền có thể:

        - Mở Order tại bàn.
        - Xem tổng tiền chính xác.
        - Xem chi tiết tạm tính.
        - Chuyển đến luồng Checkout.
        - Thực hiện thao tác thanh toán
          được Payment Backend hỗ trợ.
        - Xem kết quả giao dịch.
        - Xem hoặc in chứng từ hợp lệ.
        - Quay lại sơ đồ bàn.

        Toàn bộ dữ liệu thanh toán
        phải được xác nhận từ Backend.

        Không tự viết Payment Service mới.

        Không thay đổi Order/POS Backend.

        Không thay đổi Database Schema.

        Không tự cập nhật trạng thái bàn.

        Không tự phát hành hóa đơn điện tử.

        Không tự động commit, push hoặc merge.
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

        TEAM RESPONSIBILITIES:

        THÀNH TOÀN:
        - Area Management.
        - Cafe Table Management.
        - Reservation Management.
        - Table Floor Plan.
        - Table Detail UI.
        - Order-at-Table UI.
        - Checkout/Receipt Frontend Integration.
        - Table Status Presentation.

        ĐẠT:
        - POS Backend.
        - Order Management.
        - Order Items.
        - Payment Business Logic.
        - Transaction Processing.
        - Receipt/Invoice Backend nếu có.

        NINH:
        - Catalog.
        - Product Pricing.
        - Promotion Rules.

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

        EXPECTED USER WORKFLOW:

        Chọn khu vực
             |
             v
        Chọn bàn
             |
             v
        Mở Active Order
             |
             v
        Xem Order Items đã lưu
             |
             v
        Xem tạm tính từ Backend
             |
             v
        Bắt đầu Checkout
             |
             v
        Chọn phương thức hợp lệ
             |
             v
        Payment Backend xử lý
             |
             v
        Kiểm tra kết quả chính thức
             |
             +--> Success:
             |    Xem/In chứng từ hợp lệ
             |
             +--> Failed:
             |    Hiển thị lỗi phù hợp
             |
             +--> Pending/Unknown:
                  Kiểm tra lại trạng thái,
                  không tự tạo giao dịch mới.

        Sau khi thanh toán, trạng thái bàn
        được đọc lại từ dữ liệu nghiệp vụ
        theo chính sách thực tế.

        Việc xây dựng cơ chế đồng bộ
        trạng thái bàn hoàn chỉnh thuộc TASK 07.

        DATABASE:

        Những bảng có thể liên quan:

        - areas.
        - cafe_tables.
        - orders.
        - order_items.
        - payments.
        - products.
        - promotions.
        - receipts/invoices nếu có.

        Đây là danh sách tham khảo.

        AI Agent phải xác minh Migration,
        Models, Relationships và Schema.

        Không mặc định tồn tại bảng invoices.

        Không tự tạo bảng/cột mới.

        PAYMENT METHODS:

        Có thể bao gồm:
        - Cash.
        - Bank Transfer.
        - QR Payment.
        - Các phương thức khác.

        Chỉ hiển thị phương thức
        đã được Backend hỗ trợ thực tế.

        Không tự tạo Payment Gateway.

        PAYMENT STATUS:

        Phải xác minh Enum/Status thực tế
        trong Payment/Order Backend.

        Không giả định mọi hệ thống đều có
        paid, unpaid, pending, failed.

        RECEIPT/INVOICE TERMINOLOGY:

        PHIẾU TẠM TÍNH:
        Thể hiện số tiền dự kiến/cần thanh toán
        theo Order Summary chính thức,
        không chứng minh đã thanh toán.

        BIÊN NHẬN THANH TOÁN:
        Thể hiện giao dịch đã được
        Backend xác nhận thành công.

        HÓA ĐƠN ĐIỆN TỬ:
        Chứng từ chỉ được coi là đã phát hành
        khi hệ thống có nghiệp vụ và
        tích hợp phát hành hợp lệ.

        Không gắn nhãn hóa đơn điện tử
        cho một trang HTML tự in.

        SOURCE OF TRUTH:

        - Order Summary từ POS Backend.
        - Payment Status từ Payment Backend.
        - Transaction Reference từ Backend.
        - Receipt Data từ Service được xác minh.
        - Permission từ Authorization hiện có.

        Không sử dụng Frontend làm nguồn
        dữ liệu thanh toán có thẩm quyền.
    </context>

    <scope>
        PHẠM VI ĐƯỢC PHÉP:

        A. SOURCE CODE REVIEW:

        - TASK 01-05.
        - Table Detail.
        - Order-at-Table UI.
        - POS/Order Controller.
        - Payment Controller/Service.
        - Checkout Routes.
        - Receipt/Invoice Services.
        - Existing Print Templates.
        - Authentication/Authorization.
        - Existing Tests.

        B. ORDER CHECKOUT SUMMARY:

        - Table Context.
        - Order ID.
        - Saved Order Items.
        - Amount Summary.
        - Discounts/Taxes/Fees nếu có.
        - Payment Status.
        - Outstanding Amount nếu có.

        C. CHECKOUT UI:

        - Checkout Panel/Page.
        - Payment Method Selection
          nếu Backend hỗ trợ.
        - Payment Action.
        - Loading/Disabled State.
        - Success/Failure Feedback.
        - Return to Order/Floor Plan.

        D. PAYMENT INTEGRATION:

        - Existing Payment Route/API.
        - Request Validation.
        - Authorization.
        - Server-confirmed Amount.
        - Transaction Result.
        - Safe Error Handling.

        E. RECEIPT/PRINT UI:

        - Receipt/Preview Data Contract.
        - Printable Layout.
        - Print Action.
        - Print-only CSS nếu phù hợp.
        - Reprint nếu Backend/Policy cho phép.
        - Error State.

        F. SECURITY:

        - Authentication.
        - Authorization.
        - CSRF.
        - IDOR Prevention.
        - Duplicate Payment Prevention.
        - Transaction Status Validation.
        - Sensitive Data Protection.

        G. TESTING:

        - Checkout Summary.
        - Payment Method Display.
        - Payment Integration.
        - Success/Failed/Pending Handling.
        - Receipt Rendering.
        - Printing.
        - Authorization.
        - Regression.

        CÁC FILE DỰ KIẾN:

        - resources/views/admin/pos/
          table-checkout.blade.php

        - resources/views/admin/pos/
          components/order-summary.blade.php

        - resources/views/admin/pos/
          components/payment-result.blade.php

        - resources/views/admin/pos/
          receipt-print.blade.php

        - resources/js/admin/
          table-checkout.js

        - resources/css/
          receipt-print.css

        - app/Http/Controllers/Admin/
          TableCheckoutUIController.php

        - tests/Feature/
          TableCheckoutIntegrationTest.php

        - tests/Feature/
          TableReceiptRenderingTest.php

        - routes/web.php

        Đây chỉ là đường dẫn đề xuất.

        Phải kiểm tra cấu trúc thực tế.

        Nếu Checkout/Receipt UI đã tồn tại,
        ưu tiên tái sử dụng và tích hợp.

        Không tạo Controller, Route
        hoặc View trùng chức năng.

        KHÔNG TRIỂN KHAI:

        - Payment Service mới.
        - Payment Gateway mới.
        - Refund Logic mới.
        - Split Payment mới.
        - Partial Payment mới.
        - Tax Calculation mới.
        - Discount Calculation mới.
        - Order Total Calculation mới.
        - Electronic Invoice Issuing mới.
        - Database Migration.
        - Database Schema Changes.
        - Table Release Logic mới.
        - Order Items CRUD mới.
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
           - TASK 01-05.
           - Order Model.
           - Payment Model.
           - Order/Payment Relationships.
           - Checkout Controller/Service.
           - Receipt/Invoice Backend.
           - Existing Routes.
           - Existing Print Views.
           - Middleware/Authorization.
           - Existing Tests.

        3. TRƯỚC KHI CODE:

           AI Agent phải báo cáo:
           - Checkout Architecture hiện tại.
           - Order Summary Contract.
           - Payment Contract.
           - Payment Status Rules.
           - Supported Payment Methods.
           - Receipt/Invoice Contract.
           - Print Mechanism.
           - Authorization Rules.
           - Duplicate Payment Protection.
           - Existing/Partial/Missing APIs.
           - File dự kiến thay đổi.
           - Mini Plan.

        4. Nếu API chưa tồn tại:
           - Không tự tạo Payment Backend.
           - Không tạo giao dịch giả.
           - Có thể hoàn thiện phần
             Order Summary read-only.
           - Báo cáo MISSING/BLOCKED.
           - Nêu yêu cầu cụ thể gửi Đạt.

        5. Không tạo Migration.

        6. Không sửa Database Schema.

        7. Không tự cài package mới.

        8. Không sửa:
           - Order Service của Đạt.
           - Payment Service của Đạt.
           - Catalog/Pricing của Ninh.
           - Authentication của Hợp.
           - Authorization của Hợp.
           - Reservation Business Logic.

        9. ORDER IDENTITY:

           - Dùng Order ID thực tế.
           - Xác minh Order thuộc Table.
           - Kiểm tra quyền xem Order.
           - Không cho truy cập Order
             của bàn khác trái quyền.
           - Không tin Order ID từ Client.
           - Không sử dụng mã bàn
             thay thế Order ID.

        10. ORDER SUMMARY:

            - Chỉ lấy Saved Order Items.
            - Không cộng Unsaved Draft
              vào tổng tiền chính thức.
            - Không hiển thị tổng tiền
              tự tính như số tiền Backend xác nhận.
            - Sử dụng Amount/Total
              từ nghiệp vụ POS.
            - Phải ghi rõ loại tiền tệ
              theo cấu hình thực tế.

        11. PRICING:

            - Không tự thay đổi Unit Price.
            - Không tự tính lại Discount.
            - Không tự tính lại Tax.
            - Không tự áp dụng Promotion.
            - Không chấp nhận tổng tiền
              do Client tự gửi như đáng tin cậy.
            - Nếu Order Summary thay đổi,
              hiển thị giá trị Backend mới nhất.

        12. PAYMENT ELIGIBILITY:

            Phải kiểm tra:
            - Order có tồn tại không?
            - Order có thuộc bàn không?
            - Order có được Checkout không?
            - Order đã thanh toán chưa?
            - Order có bị hủy không?
            - Order có đang Pending không?
            - Có giao dịch đang xử lý không?
            - User có quyền Checkout không?

            Không tự phê duyệt Checkout
            trái Business Rules.

        13. PAYMENT METHODS:

            - Chỉ hiển thị phương thức có thật.
            - Không tạo QR thanh toán giả.
            - Không sinh tài khoản nhận tiền giả.
            - Không thêm Gateway mới.
            - Không tự đánh dấu phương thức
              thanh toán là khả dụng.

        14. PAYMENT INTEGRATION:

            - Gọi Payment Route/Service hiện có.
            - Dùng HTTP Method đúng Contract.
            - Dùng CSRF cho request ghi.
            - Validate Input từ Client.
            - Không INSERT Payment trực tiếp
              từ UI Controller.
            - Không tự cập nhật Order
              thành Paid.
            - Không tự cập nhật Payment Status.
            - Không bypass Payment Validation.

        15. PAYMENT RESULT:

            - Success chỉ khi Backend
              xác nhận trạng thái chính thức.
            - Failed phải hiển thị lỗi phù hợp.
            - Pending không được coi Success.
            - Unknown không được coi Failed
              hoặc Success khi chưa xác minh.
            - Không tự xác nhận kết quả
              chỉ dựa vào URL/Query Parameter.
            - Không tin Callback từ Client
              như bằng chứng giao dịch hoàn tất.

        16. PAYMENT CONFIRMATION:

            - Sau khi gửi Payment Request,
              đọc hoặc xác minh trạng thái
              từ Backend theo Contract.
            - Nếu có Payment Gateway,
              trạng thái chính thức phải được
              Backend xác thực an toàn.
            - Không tự phát minh cơ chế Callback.
            - Không tự thay đổi Gateway Logic.

        17. DUPLICATE PAYMENT:

            - Disable Button trong lúc xử lý.
            - Không cho Double Submit tùy tiện.
            - Không tự Retry request thanh toán
              khi chưa biết kết quả lần trước.
            - Backend cần cơ chế Idempotency
              hoặc kiểm soát giao dịch tương đương.
            - Nếu Backend chưa bảo đảm an toàn,
              ghi HIGH-RISK/BLOCKED.
            - Không tuyên bố an toàn
              chỉ vì frontend khóa Button.

        18. STALE ORDER:

            - Order có thể thay đổi tổng tiền
              trong lúc Checkout.
            - Order có thể được nhân viên khác
              thanh toán trước.
            - Phải kiểm tra trạng thái mới nhất
              trước hành động quan trọng.
            - Nếu có Conflict:
              + Không ép thanh toán.
              + Hiển thị thông báo.
              + Yêu cầu Refresh.
            - Không tự ghi đè Order State.

        19. PARTIAL PAYMENT:

            - Chỉ hỗ trợ nếu Backend có Contract.
            - Không tự thêm UI thanh toán một phần
              khi chưa có nghiệp vụ.
            - Nếu có, sử dụng Outstanding Amount
              chính thức từ Backend.
            - Không tự đánh dấu Paid
              khi mới thanh toán một phần.
            - Không tự tạo Split Payment Logic.

        20. RECEIPT TYPES:

            A. PHIẾU TẠM TÍNH:

            - Dùng Order Summary chính thức.
            - Có thể in trước thanh toán
              nếu nghiệp vụ cho phép.
            - Phải có nhãn "Phiếu tạm tính".
            - Không thể hiện đã thanh toán
              nếu chưa có giao dịch thành công.

            B. BIÊN NHẬN THANH TOÁN:

            - Chỉ in như chứng từ đã thanh toán
              khi Backend xác nhận Payment.
            - Sử dụng Payment Reference thật.
            - Sử dụng Amount/Time thật.
            - Không tự tạo Transaction ID.

            C. HÓA ĐƠN ĐIỆN TỬ:

            - Chỉ dùng chức năng đã tích hợp.
            - Không tự phát hành.
            - Không giả mã hóa đơn, mã cơ quan thuế
              hoặc trạng thái phát hành.
            - Nếu Backend không hỗ trợ,
              ghi rõ NOT SUPPORTED/BLOCKED.

        21. RECEIPT CONTENT:

            Chỉ hiển thị dữ liệu thực tế:

            - Shop Name nếu có.
            - Shop Contact/Address nếu có.
            - Receipt/Order Reference.
            - Table Code.
            - Area Name.
            - Order Items.
            - Quantity.
            - Price.
            - Discount/Tax nếu có.
            - Total Amount.
            - Paid Amount nếu có.
            - Payment Method nếu có.
            - Payment Time nếu có.
            - Transaction Reference nếu có.

            Không tự bịa thông tin doanh nghiệp,
            mã số thuế hoặc số chứng từ.

        22. PRINTING:

            - Tái sử dụng Print Template hiện có.
            - Nếu chưa có, có thể xây dựng
              View in cho chứng từ được phép.
            - Ưu tiên @media print hoặc
              Print Service đã tồn tại.
            - Ẩn Navigation/Button khi in.
            - Kiểm tra bố cục giấy phù hợp.
            - Không cài package in mới
              nếu chưa được duyệt.
            - Không tự gửi lệnh đến máy in mạng
              không có Integration Contract.

        23. PRINTING SAFETY:

            - Nhấn "In" không tạo Payment.
            - Nhấn "In" không sửa Order Status.
            - Nhấn "In" không sửa Table Status.
            - In thất bại không hoàn tác
              giao dịch đã thanh toán.
            - Không báo "Đã in thành công"
              khi chưa có xác nhận thực tế
              từ cơ chế in.
            - Nếu chỉ mở hộp thoại trình duyệt,
              chỉ thông báo đã mở chế độ in.

        24. REPRINT:

            - Nếu Backend cho phép in lại,
              phải dùng Receipt Reference thật.
            - Áp dụng Authorization hiện có.
            - Không tự đổi nội dung giao dịch
              đã được xác nhận.
            - Không tự phát hành bản mới
              khi chỉ muốn in lại.

        25. TABLE STATUS:

            - Không tự cập nhật Table Status
              trong Checkout UI.
            - Không tự giải phóng bàn sau khi in.
            - Không coi Payment Success
              là điều kiện duy nhất
              để Table Available.
            - TASK 07 phụ trách
              Table Status Synchronization.
            - Có thể Refresh Table State
              từ Backend hiện có nếu phù hợp.

        26. AUTHORIZATION:

            - Kiểm tra quyền xem Checkout.
            - Kiểm tra quyền thực hiện Payment.
            - Kiểm tra quyền xem Receipt.
            - Kiểm tra quyền In/Reprint.
            - Không chỉ ẩn Button ở Blade.
            - Không tạo Role/Permission mới.
            - Không sửa Middleware của Hợp.

        27. SECURITY:

            - CSRF cho thao tác ghi.
            - Chống IDOR của Order/Payment.
            - Không tin số tiền từ Client.
            - Không nhận Payment Status tùy ý.
            - Không lộ Secret/API Key.
            - Không log dữ liệu thanh toán nhạy cảm.
            - Không lưu thông tin thẻ nhạy cảm
              trong Form/Log/Local Storage.
            - Escape dữ liệu trong Blade.
            - Không hiển thị SQL Exception thô.

        28. UI DESIGN:

            Modern Coffee POS Checkout.

            Gợi ý:

            LEFT:
            - Table Information.
            - Order Items.
            - Price Summary.

            RIGHT:
            - Total Due.
            - Payment Status.
            - Payment Methods.
            - Checkout Action.
            - Receipt Preview.
            - Print Action nếu đủ điều kiện.

            Không bắt buộc bố cục này
            nếu Admin Layout có hướng tốt hơn.

        29. UX PRINCIPLES:

            - Tổng tiền nổi bật.
            - Phân biệt tạm tính và đã thanh toán.
            - Nút Checkout rõ ràng.
            - Không nhầm nút In với Thanh toán.
            - Có Loading State.
            - Có Success/Failed/Pending State.
            - Có nút quay lại Order.
            - Có đường quay lại Floor Plan.
            - Không dùng dữ liệu giả.

        30. RESPONSIVE:

            - Desktop.
            - Tablet.
            - Mobile.
            - Checkout Panel dễ thao tác.
            - Print Preview phù hợp.
            - Không overflow ngang.
            - Button dễ chạm.
            - Không che thông tin tổng tiền.

        31. ACCESSIBILITY:

            - Button có nhãn.
            - Focus State rõ ràng.
            - Input có Label.
            - Error Message dễ hiểu.
            - Keyboard Navigation.
            - Không chỉ dùng màu
              để phân biệt trạng thái.
            - Không tự thông báo Paid
              bằng animation đơn thuần.

        32. PERFORMANCE:

            - Không tạo N+1 Query.
            - Không tải toàn bộ Payment History
              nếu chỉ cần Checkout Summary.
            - Không query từng Order Item
              riêng lẻ khi không cần.
            - Không tự Poll Payment liên tục
              ngoài Contract đã xác minh.
            - Không tạo request thanh toán
              lặp lại khi Refresh UI.

        33. DATABASE SAFETY:

            - Không chạy migrate:fresh
              trên database dùng chung.
            - Không chạy db:wipe.
            - Không truncate dữ liệu.
            - Không tạo giao dịch thật
              để thử trên Production.
            - Tests dùng database độc lập.
            - Payment Integration Tests
              phải dùng Fake/Mock/Sandbox
              theo khả năng hiện có.
            - Không dùng tiền thật để test.

        34. GIT SAFETY:

            - Không tự động commit.
            - Không tự động push.
            - Không tự động merge.
            - Không ghi đè code chưa commit
              của thành viên khác.
            - Không đổi branch khi
              Working Tree chưa an toàn.

        35. TEST INTEGRITY:

            - PASS chỉ khi test chạy thật.
            - FAIL khi kết quả sai.
            - BLOCKED khi thiếu Contract.
            - NOT RUN khi chưa kiểm thử.
            - NOT APPLICABLE nếu chức năng
              không được hệ thống hỗ trợ.
            - Không khẳng định máy in vật lý
              hoạt động nếu chưa kiểm chứng.
            - Không khẳng định giao dịch thành công
              nếu chỉ dùng response giả lập.

        36. SCOPE BOUNDARY:

            - Không xây dựng Payment Gateway.
            - Không xây dựng Payment Backend mới.
            - Không xây dựng Tax Engine.
            - Không xây dựng Refund Logic.
            - Không xây dựng Invoice Issuance mới.
            - Không sửa Order Business Logic.
            - Không sửa Table Status Sync.
            - Không triển khai ngoài TASK 06.
    </hardRules>

    <tasks>
        <taskUnit id="01">
            <name>
                Khảo sát Checkout, Payment
                và Receipt Contracts
            </name>

            <estimatedTime>
                10-15 phút.
            </estimatedTime>

            <steps>
                - Kiểm tra Current Branch.
                - Kiểm tra git status/git diff.
                - Đọc kết quả TASK 01-05.
                - Kiểm tra Order Model.
                - Kiểm tra Payment Model.
                - Kiểm tra POS Controller/Service.
                - Kiểm tra Checkout Routes.
                - Kiểm tra Payment Methods.
                - Kiểm tra Receipt/Invoice Backend.
                - Kiểm tra Print Templates.
                - Kiểm tra Authorization.
                - Kiểm tra Existing Tests.

                - Xác định:
                  + Checkout Summary Source.
                  + Final Total Source.
                  + Payment Write Contract.
                  + Payment Result Contract.
                  + Receipt Read Contract.
                  + Print Route/Mechanism.
                  + Idempotency/Concurrency Rules.
                  + Các Business Rules chưa rõ.

                - Phân loại API:
                  EXISTING/PARTIAL/MISSING/
                  BLOCKED/PROPOSED.

                - Liệt kê file sẽ chỉnh sửa.

                - Trình bày Mini Plan
                  trước khi code.
            </steps>
        </taskUnit>

        <taskUnit id="02">
            <name>
                Xây dựng Checkout UI
                và Order Payment Summary
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. ORDER CONTEXT:

                - Nhận Table ID hợp lệ.
                - Nhận Order ID hợp lệ.
                - Kiểm tra Order thuộc Table.
                - Kiểm tra Authorization.
                - Không mở Order không được phép.

                B. SUMMARY DATA:

                - Đọc Saved Order Items.
                - Đọc Unit Prices chính thức.
                - Đọc Discounts nếu có.
                - Đọc Taxes/Fees nếu có.
                - Đọc Final Total từ Backend.
                - Đọc Payment Status.
                - Đọc Outstanding Amount nếu có.

                C. CHECKOUT UI:

                - Table Information.
                - Order Items Summary.
                - Total Amount.
                - Payment Status.
                - Payment Method Selection
                  nếu được Backend hỗ trợ.
                - Checkout Button phù hợp.
                - Back to Order.
                - Back to Floor Plan.

                D. UI STATES:

                - Loading.
                - Empty Order.
                - Invalid Order.
                - Already Paid.
                - Pending Payment.
                - Unauthorized.
                - Backend Error.

                E. TESTS:

                - Đúng Table.
                - Đúng Order.
                - Đúng Saved Items.
                - Đúng Final Total.
                - Không cộng Draft chưa lưu.
                - Không hiển thị Payment Method giả.
                - Không cho Checkout
                  trái Business Rules.
            </steps>
        </taskUnit>

        <taskUnit id="03">
            <name>
                Tích hợp Payment Result,
                Receipt Preview và Printing
            </name>

            <estimatedTime>
                20-25 phút.
            </estimatedTime>

            <steps>
                A. PAYMENT INTEGRATION:

                - Tái sử dụng Checkout/Payment
                  Route/Service hiện có.

                - Kiểm tra Authorization.

                - Sử dụng Request Contract
                  đã được Backend phê duyệt.

                - Không gửi Client Total
                  như giá trị có thẩm quyền.

                - Disable Button khi xử lý.

                - Gọi Payment Action đúng Method.

                - Không tạo Payment Record
                  trực tiếp từ UI Controller.

                B. PAYMENT RESULT:

                - Xử lý Success.
                - Xử lý Failed.
                - Xử lý Pending.
                - Xử lý Unknown/Conflict.

                - Đọc trạng thái từ Backend.

                - Không báo Paid giả.

                - Không Retry nguy hiểm.

                C. RECEIPT VIEW:

                - Kiểm tra Receipt Contract.

                - Tái sử dụng Template hiện có.

                - Hiển thị:
                  + Shop Information nếu có.
                  + Order/Receipt Reference.
                  + Table/Area.
                  + Order Items.
                  + Amount.
                  + Payment Details nếu có.
                  + Timestamp nếu có.

                - Không tự tạo số hóa đơn.

                D. PRINTING:

                - Tạo Print Preview
                  nếu cần và trong Scope.

                - Áp dụng Print-only CSS
                  phù hợp môi trường.

                - Ẩn Navbar/Buttons khi in.

                - Dùng Print Route có quyền.

                - Không tự tạo Payment khi in.

                - Không giải phóng bàn khi in.

                E. DEPENDENCY HANDLING:

                Nếu Payment Backend chưa sẵn sàng:
                - Không tự xây dựng Payment Service.
                - Hoàn thiện Checkout read-only.
                - Ghi BLOCKED cho Payment Action.

                Nếu Receipt Backend chưa có:
                - Xác minh có đủ Contract
                  để in phiếu tạm tính hay không.
                - Không giả lập biên nhận đã trả tiền.
                - Ghi BLOCKED cho chứng từ
                  chưa có dữ liệu hợp lệ.

                F. TESTS:

                - Checkout Success.
                - Checkout Failed.
                - Checkout Pending.
                - Unknown Payment Result.
                - Double Submit.
                - Already Paid Order.
                - Receipt Preview.
                - Print Rendering.
                - Unauthorized Print.
                - Print không đổi trạng thái.
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
                A. FUNCTIONAL TESTS:

                - Mở Checkout đúng Order.
                - Hiển thị tổng tiền Backend.
                - Hiển thị danh sách món.
                - Payment Method hợp lệ.
                - Payment Success.
                - Payment Failed.
                - Payment Pending.
                - Already Paid Order.
                - Payment Backend Error.

                B. PAYMENT SAFETY:

                - Double Submit.
                - Retry khi trạng thái Unknown.
                - Stale Order Total.
                - Concurrent Checkout.
                - Không sửa Paid bằng Client.
                - Không tạo giao dịch trùng
                  trong những case kiểm thử
                  mà Backend có hỗ trợ.

                C. RECEIPT/PRINT TESTS:

                - Phiếu tạm tính hợp lệ.
                - Biên nhận đã thanh toán.
                - Receipt của Order khác.
                - Print Preview.
                - Nội dung Print CSS.
                - In không đổi Payment Status.
                - In không đổi Table Status.
                - Reprint nếu được phép.

                D. SECURITY:

                - Guest.
                - Unauthorized Staff.
                - Order ID Tampering.
                - Payment ID Tampering.
                - Receipt ID Tampering.
                - CSRF.
                - XSS Receipt Data.
                - Sensitive Data Exposure.

                E. UI TESTS:

                - Desktop.
                - Tablet.
                - Mobile.
                - Loading.
                - Success/Failed/Pending.
                - Error Messages.
                - Print Layout.
                - Keyboard Navigation.

                F. REGRESSION:

                - TASK 03 Floor Plan.
                - TASK 04 Table Detail.
                - TASK 05 Order-at-Table.
                - Existing POS.
                - Payment Module.
                - Authentication/Authorization.
                - Table Status không bị sửa
                  ngoài Business Rules.

                G. FINAL CHECKS:

                - Chạy Feature Tests.
                - Kiểm tra route:list.
                - Kiểm tra PHP Syntax.
                - Kiểm tra Blade Views.
                - Kiểm tra JS/CSS Build nếu có.
                - Kiểm tra git status/git diff.
                - Kiểm tra file ngoài Scope.

                - Tổng hợp:
                  PASS/FAIL/BLOCKED/
                  NOT RUN/NOT APPLICABLE.

                - Xuất Final Report.
                - Không commit/push/merge.
            </steps>
        </taskUnit>
    </tasks>

    <acceptanceCriteria>
        1. Đã khảo sát TASK 01-05.

        2. Đã xác minh Payment Contract.

        3. Đã xác minh Receipt Contract
           hoặc ghi rõ blocker.

        4. Có Checkout UI.

        5. Hiển thị đúng Table Context.

        6. Hiển thị đúng Order ID.

        7. Không mở Order của bàn khác
           trái quyền truy cập.

        8. Order Items lấy từ Backend.

        9. Tổng tiền chính thức lấy
           từ POS/Payment Backend.

        10. Không cộng Draft chưa lưu
            vào tổng thanh toán chính thức.

        11. Discounts/Taxes/Fees
            theo nghiệp vụ hiện có.

        12. Hiển thị Payment Methods
            đã được hỗ trợ thực tế.

        13. Không tạo Payment Method giả.

        14. Có Checkout Action
            khi Backend hỗ trợ.

        15. Payment sử dụng Backend hiện có.

        16. Không Insert Payment trực tiếp
            từ UI Controller.

        17. Không tự cập nhật Order thành Paid.

        18. Không tự cập nhật Payment Status.

        19. Payment Success chỉ dựa
            trên xác nhận Backend.

        20. Failed/Pending/Unknown
            được xử lý đúng.

        21. Có Loading/Disabled States.

        22. Không tự Retry giao dịch
            chưa xác định kết quả.

        23. Duplicate Payment Protection
            được xác minh hoặc ghi blocker.

        24. Có Receipt/Preview UI
            phù hợp dữ liệu hiện có.

        25. Phân biệt tạm tính và biên nhận.

        26. Không giả phát hành hóa đơn điện tử.

        27. Có chức năng in chứng từ
            nếu Contract hỗ trợ.

        28. Print Layout rõ ràng.

        29. In không tạo Payment mới.

        30. In không thay đổi Order Status.

        31. In không thay đổi Table Status.

        32. Có kiểm tra Authorization.

        33. Có CSRF Protection
            cho thao tác ghi.

        34. Có kiểm tra Order/Payment/Receipt ID.

        35. Không cho IDOR trong case đã test.

        36. Không tin số tiền Client.

        37. Không lộ dữ liệu thanh toán nhạy cảm.

        38. Có Responsive Desktop.

        39. Có Responsive Tablet.

        40. Có Responsive Mobile.

        41. Có Error/Empty/Conflict States.

        42. Không sửa Order Business Logic.

        43. Không sửa Payment Business Logic.

        44. Không sửa Catalog/Promotion Logic.

        45. Không sửa Authentication/Authorization.

        46. Không tạo Migration.

        47. Không thay đổi Database Schema.

        48. Không tự giải phóng bàn.

        49. Không làm hỏng TASK 03-05.

        50. Tests chạy trong môi trường an toàn.

        51. Không tự động commit/push/merge.

        52. Không báo PASS khi chưa test.

        53. Nếu thiếu Payment API,
            ghi BLOCKED cho Payment Integration.

        54. Nếu thiếu Receipt Backend,
            ghi BLOCKED cho chứng từ
            phụ thuộc vào Backend đó.

        55. AI Agent phải xuất FINAL REPORT:

            A. MODULE OVERVIEW
            - Chức năng hoàn thành.
            - Chức năng chưa hoàn thành.
            - Blocker còn tồn tại.

            B. SOURCE CODE SURVEY
            - Order Model.
            - Payment Model.
            - Existing Controllers/Services.
            - Routes.
            - Receipt/Invoice Implementation.
            - Authorization.

            C. CHECKOUT UI
            - Table Context.
            - Order Summary.
            - Total Amount.
            - Payment Method Selection.
            - Checkout Action.
            - Loading/Error States.

            D. PAYMENT INTEGRATION
            - Existing Backend Contract.
            - HTTP Method.
            - Request Fields.
            - Response Fields.
            - Success/Failed/Pending Handling.
            - Authoritative Payment State.
            - Duplicate Payment Protection.
            - Error Handling.

            E. RECEIPT &amp; PRINTING
            - Supported Document Types.
            - Receipt Data Contract.
            - Print Template.
            - Print Preview.
            - Browser/Printer Integration.
            - Reprint Policy.
            - Missing Invoice Features.

            F. FINANCIAL SAFETY
            - Backend Total Source.
            - Pricing/Discount/Tax Dependencies.
            - Payment State Validation.
            - Stale Order Handling.
            - Idempotency/Concurrency.
            - Unverified Risks.

            G. TABLE STATUS BOUNDARY
            - Các trạng thái được đọc.
            - Các sự kiện Payment liên quan.
            - Không tự sửa Table Status.
            - Dependencies cho TASK 07.

            H. SECURITY
            - Authentication.
            - Authorization.
            - CSRF.
            - IDOR.
            - Client Amount Tampering.
            - Sensitive Data Protection.
            - Blade Escaping.

            I. UI/UX
            - Checkout Panel.
            - Total Summary.
            - Payment States.
            - Receipt Preview.
            - Print Layout.
            - Desktop/Tablet/Mobile.
            - Accessibility.

            J. FILE CHANGES
            - File tạo mới.
            - File chỉnh sửa.
            - Lý do thay đổi.
            - Xác nhận không sửa ngoài Scope.

            K. ROUTES
            - HTTP Method.
            - Route Name/Path.
            - Controller/Service.
            - Middleware/Policy.
            - Contract Status.

            L. TEST RESULTS
            - Test ID.
            - Scenario.
            - Expected Result.
            - Actual Result.
            - PASS/FAIL/BLOCKED/NOT RUN.
            - Test Commands.
            - Test Database/Mock/Sandbox.
            - Evidence nếu có.

            M. REGRESSION
            - TASK 03.
            - TASK 04.
            - TASK 05.
            - POS.
            - Payment.
            - Authentication/Authorization.

            N. NEXT TASK HANDOFF
            - Payment Event/Status Contract.
            - Order Completion Contract.
            - Table Status Dependencies.
            - Table Release Policy.
            - Refresh/Sync Requirements.
            - Các blocker cho TASK 07.
            - Việc cần phối hợp với Đạt.

            O. FINAL STATUS
            - READY FOR REVIEW
              hoặc NOT READY FOR REVIEW.
            - Blocker còn lại.
            - Rủi ro kỹ thuật.
            - Các quyết định cần xác nhận.

        56. Chỉ đánh dấu TASK 06 hoàn thành
            khi Checkout, Payment Integration
            và Receipt/Printing trong phạm vi
            hỗ trợ thực tế được kiểm thử đạt.

        57. Nếu Payment/Receipt Backend
            chưa hoàn thiện, không được
            giả lập giao dịch thành công
            để báo TASK đã hoàn thành.

        58. Không tuyên bố đã hoàn thành
            Table Status Synchronization
            của TASK 07.

        59. Không tuyên bố toàn bộ
            Area &amp; Table POS Management
            đã hoàn thành trước TASK 09.
    </acceptanceCriteria>
</prompt>
```