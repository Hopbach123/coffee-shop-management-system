# UI seed asset và chuẩn hóa card

## Phạm vi

- Bổ sung ảnh local cho bốn product fixture của `seed_data.sql`.
- Chuẩn hóa nhịp card sản phẩm giữa Public Menu và Admin Product Catalog.
- Không đổi schema, route, quyền, nghiệp vụ bán hàng hay ảnh do quản trị viên tự tải lên.

## Asset fixture

| Product slug | File local |
| --- | --- |
| `demo-v1-product-1` | `public/images/products/demo-latte.png` |
| `demo-v1-product-2` | `public/images/products/demo-ca-phe-sua.png` |
| `demo-v1-product-3` | `public/images/products/demo-tra-sua.png` |
| `demo-v1-unavailable` | `public/images/products/demo-tra-tam-het.png` |

Các ảnh là asset cục bộ tạo cho fixture, không dùng URL bên thứ ba. `Product::imageUrl()` đã hỗ trợ path `images/...`, nên không cần sửa Model hoặc migration.

Khi chạy lại seed, chỉ ảnh `NULL` hoặc đường dẫn fixture cũ được chuyển sang ảnh mới. Ảnh custom do Admin tải lên không bị thay thế.

## Quy ước hiển thị

- Public và Admin dùng khung media `4:3`, `object-fit: cover`, overflow ẩn và responsive grid hiện có.
- Card giữ typography sans-serif nhất quán cho tên/mô tả; mô tả có chiều cao tối thiểu để thao tác nằm cùng hàng ngang.
- Nút `Chọn món` của Public Menu được neo đáy card để card nhiều nội dung không làm lệch thao tác; ở màn hình nhỏ nút trải đủ chiều rộng chạm.
- Hero và phần giới thiệu menu có quy tắc mobile riêng: copy co trong container, CTA không tràn và tiêu đề giảm về cấp đọc được ở 390px.
- Action Admin giữ một hàng; nếu chiều ngang không đủ thì scroll ngang cục bộ thay vì xếp thành nhiều hàng cao.
- Topping/ingredient chưa có cột ảnh trong schema. Chúng tiếp tục dùng icon/nhãn chuẩn; không thêm dữ liệu ảnh khi chưa có nơi lưu hoặc migration được duyệt.

## Kiểm chứng

- `npm.cmd run build`: PASS. Vite cảnh báo hai URL ảnh runtime đã tồn tại trên đĩa (`/assets/video/maison-coffee-poster.jpg`, `/images/auth-bg-golden.jpg`) nhưng không đóng gói chúng.
- `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test --filter='(PublicMenu|Product|PromotionManagement|CustomerPickup|OnlineOrderWorkflow)'`: PASS, 89 tests, 1.204 assertions.
- Đã chạy `database/seed_data.sql` và `database/verify_seed_data.sql` trên database development `coffee_shop`, không reset dữ liệu.
- Đọc lại database sau import: đủ bốn đường dẫn ảnh mới theo slug fixture, 6 online request DEMO-V2 và 2 promotion active có ảnh.
- `GET /menu`: HTTP 200; HTML tham chiếu đủ bốn ảnh `/images/products/demo-*.png`.
- Khôi phục `public/storage` bằng `php artisan storage:link`. Một ảnh sản phẩm do Admin tải lên trước đó còn nguyên ở `storage/app/public/products/` nhưng không hiện vì thiếu link; sau khi tạo junction, URL `/storage/products/...` trả HTTP 200 `image/png`.
- `git diff --check`: không có lỗi diff; chỉ có cảnh báo CRLF ở các file thay đổi có sẵn ngoài phạm vi này.

## Chưa xác minh

- Native browser automation không kết nối được, nên chưa có bằng chứng ảnh chụp runtime ở desktop/tablet/mobile.
- Cần lặp lại `php artisan storage:link` khi clone/deploy sang máy chủ mới nếu public storage chưa được liên kết.

## Việc cần làm trước nghiệm thu UI

1. Kiểm tra bốn file `/images/products/*.png` qua browser, Admin → Sản phẩm và Public → Thực đơn ở desktop/tablet/mobile.
2. Quyết định phạm vi Public Promotion trước khi thêm ô áp mã vào checkout; tài liệu hiện hành đang mâu thuẫn ở điểm này.
