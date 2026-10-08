# Coffee Shop Management System – Requirements

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

### 5.6 Payment

Mục tiêu của domain Payment là ghi nhận lịch sử thử thanh toán bằng `cash` hoặc `bank_transfer`, với trạng thái `pending`, `paid`, `failed`, `refunded`. Phiên bản 1 không hỗ trợ split/partial payment; một Order có thể có nhiều lần thử nhưng tối đa một Payment `paid`. Tích hợp cổng thanh toán bên ngoài không thuộc phạm vi hiện tại.

### 5.7 Promotion

Mục tiêu của domain Promotion là hỗ trợ giảm theo phần trăm hoặc số tiền cố định, khoảng hiệu lực, giá trị đơn tối thiểu, mức giảm tối đa và giới hạn lượt dùng. Phiên bản 1 cho phép tối đa một Promotion trên mỗi Order; `used_count` chỉ tăng sau khi Payment thành công. Voucher engine ngoài baseline này cần phê duyệt riêng.

### 5.8 Inventory

Mục tiêu của domain Inventory là quản lý nguyên liệu, tồn hiện tại, định mức tối thiểu, công thức theo biến thể và lịch sử biến động `import`, `consume`, `adjustment`, `waste`. Tồn kho chỉ bị trừ trong transaction khi Order Item chuyển `pending` sang `preparing`; mọi thay đổi tồn phải có log và không được làm tồn âm. Nhà cung cấp, mua hàng và ERP kho nằm ngoài phạm vi.

### 5.9 Dashboard

Mục tiêu của domain Dashboard là cung cấp cái nhìn tổng quan cho quản lý. Phạm vi gồm tổng quan doanh thu, thống kê đơn hàng, chỉ số bán sản phẩm và thống kê vận hành cơ bản.

Biểu đồ, kỳ báo cáo và cách tổng hợp cụ thể là TBD.

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
- Giao diện công khai đáp ứng nhiều kích thước màn hình.
- Tương tác không tải lại toàn trang tại các vị trí phù hợp.

## 9. Out-of-Scope Features

Các nội dung sau nằm ngoài phạm vi, trừ khi được bổ sung bằng một yêu cầu được phê duyệt trong tương lai:

- Quản lý nhiều chi nhánh.
- Hệ thống nhân sự và tiền lương đầy đủ.
- Chấm công nhân viên.
- Quy trình mua hàng từ nhà cung cấp.
- Hệ thống kế toán đầy đủ.
- Vận hành giao nhận.
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
