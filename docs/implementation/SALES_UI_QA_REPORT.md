# Sales UI QA — 17/11/2026

## Task Status
**UI NOT_RUN / browser automation BLOCKED**. Không đánh dấu PASS từ build hoặc feature test.

## Task ID
SALES-FINANCE-QA-UI-001

## Environment / bằng chứng
Đã gọi computer-use inventory. Trả về browsers=[] và lỗi `The browser access host is unavailable`. Native Edge/Brave có trong inventory, nhưng chưa thực hiện UI browser/native navigation, resize/keyboard hoặc đăng nhập. Không nói máy không có browser; blocker là kết nối browser automation cho lượt QA. Không có screenshot/viewport evidence. Không tạo dữ liệu trên DB dự án để demo.

## Matrix
| Trang | 375 | 768 | 1440 | Keyboard/actions |
| --- | --- | --- | --- | --- |
| Menu / Cart / Checkout | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN |
| POS / Counter | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN |
| Online/Scheduled / Staff Inbox | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN |
| Brewing / Order Detail / Payment | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN |
| Tracking | NOT_RUN | NOT_RUN | NOT_RUN | NOT_RUN |

## Có bằng chứng ở layer khác
- npm run build PASS, warnings cũ fontaine optional và ảnh runtime; không là browser QA.
- Targeted feature HTTP68 PASS /565 assertions trước lượt full; default regression467 =451 pass/16 skip. Form methods/CSRF/validation/paid guard có feature coverage, không đủ chứng minh responsive/loading/keyboard.
- Hai POST cùng intent qua HTTP kernel/MySQL đạt C12. Counter/online/scheduled qua Service thật đến completed trên MySQL đạt; chưa thao tác ba flow trên giao diện browser.

## Finding runtime render ngoài Sales
Bộ OnlinePickupSchemaMySqlTest khi mở trên MySQL phát hiện thiếu “Lịch sử kho” ở trang recipe CRUD đang render `admin/product-recipes/index`. Template legacy `admin/product-variants/recipe.blade.php` có link nhưng không phải trang đang phục vụ. Giữ assertion FAIL, không xóa/nới test hoặc sửa module recipe ngoài scope QA Sales. Nhãn paid test cũ được cập nhật từ “Đã thu tiền” sang “Đã thanh toán” đúng UI hiện có.

## Demo / config
DB dự án chưa migrate receipt; giờ quán thực tế chưa cấu hình. QA MySQL dùng giờ fixture08:00–20:00 trong test duy nhất, không ghi vào config quán hoặc .env. Scheduled config thật vẫn BLOCKED chờ Toàn. Khi có browser connection: dùng DB demo riêng đã migrate, chọn xác nhận giờ test, chuẩn bị fixture Catalog/recipe/kho/user, chạy ba flow bằng thao tác thật. Không fake paid qua insert DB.

## Files / kiểm tra
Không sửa frontend trong task QA này. Các CSS/Blade dirty từ trước được giữ. Report này tạo mới; build output ignored, không commit artifacts.

## Remaining / Engineer Review Required
Khôi phục browser host hoặc chạy manual QA có evidence375/768/1440, keyboard/no overflow/validation/cash-bank receipt, double submit, start/ready/served và complete guard. Chưa đạt Gate4. Không nâng DB dự án trước review/backup/phê duyệt riêng.


## Final QA follow-up — 17/11/2026 (thay thế kết luận finding Recipe chưa xử lý ở trên)
Task final QA cho phép bổ sung link tối thiểu: admin.product-recipes.index đã có Lịch sử kho khi nguyên liệu chưa soft delete, dùng route có sẵn và quyền Admin. Targeted MySQL HTTP test PASS/46 assertions; assertion danh sách variant kiểm URL công thức thật, không phụ thuộc nhãn cũ. Không sửa Recipe/Inventory workflow.

UI sửa cục bộ: mã nhận thay input inline 60px bằng class dùng tokens, nút Giao dùng component và disabled trước paid, Brewing grid có min(100%,20rem) để tránh chiều rộng tối thiểu 320px vượt vùng chứa. Đây là sửa theo source, chưa là bằng chứng responsive. Build PASS; warnings fontaine optional và hai ảnh runtime còn cần kiểm browser.

Đã chuẩn bị demo DB riêng, giờ fixture08:00–20:00; không đổi .env/DB dự án. Browser inventory vẫn báo host unavailable. Native Brave fallback bị Computer Use tự dừng vì không xác định được URL đủ chắc để enforce policy. Đã dừng thao tác ngay; không đăng nhập/submit/resize hoặc ghi screenshot. PHP demo và MySQL QA riêng đã shutdown. Toàn bộ matrix375/768/1440 phía trên vẫn NOT_RUN.

### Checklist manual QA khi môi trường sẵn sàng
Chạy trên demo DB riêng với giờ fixture được ghi rõ, không dùng dữ liệu thật. Với từng màn menu/cart/checkout, POS, inbox/detail, brewing, Order/payment và tracking: kiểm375/768/1440, không overflow ngang, label/error/old input/empty state, thứ tự Tab/focus và nút thao tác không khuất; lưu screenshot/kết quả theo viewport.

1. Counter: tạo nhiều món/size/topping, tổng server; start pha một lần, ready; thử giao chưa trả tiền bị chặn; thu cash đủ/tiền thừa rồi giao, completed đúng điều kiện.
2. Online lấy sớm: gửi request, thử double submit cùng intent, Staff accept; accept không trừ kho; sai token/mã nhận/quyền bị từ chối; bank chỉ paid sau xác nhận thực nhận và ref hợp lệ; paid chưa giao không completed.
3. Scheduled: nhập giờ Việt Nam hợp lệ, ngoài giờ/quá khứ và ranh giới ngày; chỉ takeaway; accept/show đúng giờ; Staff chủ động pha; thiếu công thức/tồn không đổi pending; ready chưa completed; giao đủ + paid mới completed.
4. Kiểm network retry, lỗi validation, back/refresh, nút Giao sau paid, tracking không lộ token danh sách, hủy sau pha/paid không tự refund hoặc hồi tồn. Không tạo tình huống sai bằng sửa thẳng DB.

Ghi rõ browser/version, viewport, fixture, người chạy và artifact cho từng bước; chưa có evidence thì giữ NOT_RUN.
