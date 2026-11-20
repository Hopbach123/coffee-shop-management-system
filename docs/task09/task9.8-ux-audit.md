# Task9.8 — UX audit và mini plan

## Phạm vi / baseline trước code
Branch feature/area-table-pos-ui. Worktree có toàn bộ task9.1–9.7 chưa commit và task9.9 do engineer chỉnh trước; giữ nguyên các thay đổi đó. Task hiện tại chỉ polish UI đang tồn tại, không bổ sung chức năng Payment/Receipt còn thiếu. Tái sử dụng internal layout/Sneat, coffee tokens, public.button, icon, card, filter và status contract9.7. Không có UI package/browser automation package trong package.json. Có Edge/Brave local; sẽ thử headless kiểm chứng viewport mà không cài package.

## Audit từ source trước triển khai
| Page | Issue / severity | Root cause / viewport | Fix dự kiến | Verification trước code |
| --- | --- | --- | --- | --- |
| Area | P1: summary 8 ô nhưng grid cố định 5 cột; text muted tương phản thấp trong theme hiện tại | Grid và token Sneat; desktop/tablet | Cân summary 4/2 cột, scoped text contrast; giữ count | Source confirmed, chưa browser |
| Floor/detail | P1: status reserved/occupied và conflict/maintenance dùng cùng màu; chưa có affordance selected bàn ở trang detail | CSS; mọi viewport | Tách token màu sẵn có, border pattern/icon/label; selected detail aria-current trên link tương ứng nếu áp dụng | Source confirmed |
| Floor/detail | P1: tên/mã dài, header actions, pagination có thể ép chiều ngang | Flex min-content và nested elements | min-width:0, wrap, touch targets, giới hạn grid | Risk cần browser xác minh |
| Order-at-table | P1: mobile phải đi qua toàn bộ catalog mới tới draft; không có shortcut; product/cart thiếu phân nhóm rõ | DOM catalog trước cart; <=992px | Jump navigation, native details mở sẵn cho giỏ có thể thu/mở, không thay DOM khi resize | Source confirmed |
| Draft | P1: number/note input chưa có style/touch target; bỏ dòng làm mất focus; add label giống nhau | JS tạo DOM và CSS chỉ width100% | Ô nhập >=48px, label cụ thể, +/- buttons; restore focus sau remove | Source confirmed |
| Read Order | P1: không báo loading/disable GET refresh; có thể gửi nhiều GET đồng thời và snapshot tới sai thứ tự | JS chưa có read-busy guard | Read busy + finally, khóa control trong lúc đọc, không đổi HTTP contract | Source confirmed |
| Checkout / estimate | P1: mã dài và bảng 5 cột có thể khó đọc trên mobile; tổng tiền ít nổi bật | Table natural sizing + flex | Label/value rõ, responsive screen rows với data-label; print giữ bảng gốc/thead/totals | Risk cần browser/print kiểm chứng |
| Module controls | P1: Sneat button/input specificity ghi đè min-height48px; disabled/focus/pressed thiếu nhất quán | Global internal sheet tải sau app.css | Scoped overrides chỉ trên module pages, reduced-motion | Source confirmed |
| Sidebar layout | Shared drawer có Escape/backdrop nhưng chưa focus trap/inert/return focus | resources/js/internal.js toàn ứng dụng | Ghi nhận ngoài polish cục bộ; không refactor shell toàn cục | Source confirmed; không tạo drawer modal mới |

Các state rỗng/no-results/error/conflict đã có; giữ nguyên nội dung/backend. Payment chỉ đọc record tham chiếu, không thêm lựa chọn thanh toán hoặc success giả. Print là phiếu tạm tính đã duyệt, không hóa đơn.

## Mini plan
1. Scoped CSS polish các trang Area/Floor/detail/menu/checkout/estimate; giữ theme đang dùng, không redesign shell.
2. Menu: native disclosure và jump links, draft quantity/focus/loading, không đổi policy lưu từng dòng hay timeout/uncertain guard.
3. Checkout: tổng tiền/records dễ scan, mobile table và print CSS tách biệt, không tính lại tiền.
4. Build, targeted/full PHP + Node regression; render fixture chỉ từ SQLite :memory:, browser ở375/768/1024/1440px, kiểm tra overflow/focus/draft/print với request giả chỉ trong harness, không ghi MySQL.
5. Report AC, evidence, browser limitations và handoff task9.9; không tự triển khai task tiếp.

## File dự kiến
resources/css/admin/{areas,table-floor-plan}.css; resources/css/staff/{table-menu,checkout-summary}.css; module scoped polish stylesheet và app.css import nếu cần; resources/views/staff/orders/{table-menu,checkout-summary}.blade.php; components/estimate-items.blade.php; resources/views/admin/tables/detail.blade.php nếu cần; resources/js/staff/table-menu.js; tests/JavaScript/table-menu.test.mjs; UI/browser harness và docs/task09/task9.8-report.md. Không sửa backend/routes/auth/schema/dependencies.

## Dependencies / blockers
Payment backend, receipt chính thức và release policy chưa sẵn sàng (task9.6/9.7 PARTIAL). Chỉ polish phần đọc và proforma đã duyệt. Browser nếu launch bị sandbox chặn cần quyền chạy headless với profile tạm; không dùng profile cá nhân.
