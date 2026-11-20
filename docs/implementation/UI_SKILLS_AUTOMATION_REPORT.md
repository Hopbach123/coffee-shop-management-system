# Áp dụng hai skill UI — 20/11/2026

## Task Status
PARTIAL / visual QA BLOCKED — đã có code, build và regression evidence; chưa nghiệm thu toàn bộ giao diện. Không thay báo cáo cũ thành bằng chứng nghiệm thu mới.

## Task ID
CAFE-UI-SKILLS-001

## Phase2 / Card system V3 — triển khai thực tế
Đã đọc hai prompt1b0553d3 và32e458fc. Áp dụng design-taste-frontend cho phân cấp/surface và frontend-ui-engineering cho CSS layout/semantic markup. Không gọi browser lặp khi blocker chưa thay đổi. Bổ sung source: sneat.css có page background mới, card radius16px/shadow token dùng chung; POS product surface đồng bộ, variant rows compact ngang và tự wrap label/giá, nút48px; cart không sticky để không che form khi viewport thấp. staff/tables/index.blade.php chuyển từ card icon giữa sang header icon+status, body tên/khu vực, footer sức chứa; vẫn chỉ đọc, không thêm chọn/nhận/gán bàn. Không thay routes/form/Services/DB.

Files bổ sung lượt này: resources/css/internal/sneat.css, resources/views/staff/tables/index.blade.php. Build PASS; targeted PosOrderWorkflowTest+RoleAuthorizationTest19 PASS/137 assertions sau sửa layout. Card visual375/768/1024/1280/1440 và zoom200% vẫn VISUAL_QA_BLOCKED. Reference cards Order/Kitchen/KPI và rollout create/edit Admin còn cần đối chiếu/review tiếp; không tuyên bố đã hoàn thiện tất cả variants hoặc33 IDs.

## Tách khu vực Admin/Staff theo yêu cầu mới
Đã bỏ nhóm link Staff hardcoded khỏi sidebar Admin và bỏ mảng menu cũ không được render. Sidebar Admin giữ dashboard + registry quản trị; sidebar Staff giữ POS/đơn/online/pha/bàn/đặt bàn. Nhận diện sidebar hiển thị KHU VỰC QUẢN TRỊ · ADMIN hoặc KHU VỰC NHÂN VIÊN · STAFF; topbar Staff ghi Khu vực Staff · Bán hàng / POS. Admin chuyển bằng nút riêng Mở POS/Về Quản trị, role tài khoản không thay đổi, Staff không có link quản trị. Giữ registry, auth/session/route/permission và DB. RoleAuthorizationTest13 PASS/80 assertions, build PASS; visual QA chưa xác nhận. Files bổ sung: layouts/internal.blade.php và RoleAuthorizationTest.php; không chạy lại toàn suite cho thay đổi nhãn/layout này.

## Continuation finalization — evidence reconciliation
Đã đọc prompt tiếp nối fc7fffd8 ngày20/11/2026. Đã thay ma trận33 IDs bằng ba trạng thái độc lập Implementation/Functional Tests/Visual QA; sửa route/view H12 nhập kho,H13 kiểm kê,H15–16 và N01–04 Public,SYS01 dashboard. Loại tham chiếu mockup08/03 không tồn tại cho Product Form/Order Detail. SYS03 Staff đặt bàn chỉ là danh sách đọc theo WorkspaceController, không được mô tả tiếp nhận/gán bàn. Dashboard hiện có3 KPI theo dữ liệu thật, không tự thêm4 KPI/chart như mockup giả lập.

Browser fallback inventory: repository node_modules không có playwright/puppeteer; Python importlib xác nhận không có playwright và không có AppData/Local/ms-playwright; npm global chỉ có namespace @deepseek-ai/@github. Chưa cài dependency, chưa chạy browser thay thế. Checklist chụp thủ công đã đưa vào UI_SCREEN_MATRIX.md. Đây là inventory môi trường, không phải visual PASS. Shared shell/foundation và các màn B–D còn cần review/render thực tế từng nhóm. Public chỉ regression, không mở rộng redesign. Diff check ma trận/POS đạt; chưa chạy lại467 tests vì lần này chỉ reconcile tài liệu, không thay runtime sau baseline POS6/57 và build PASS trước đó.

## Summary
User yêu cầu tự động áp dụng hai công nghệ/skill UI. Diễn giải theo catalog mới: `design-taste-frontend` và `frontend-ui-engineering`. Đây là phương pháp thiết kế/engineering, không phải cài thêm hai frontend framework. Source giữ tại ổ F, nhánh feature/order-requests-scheduled-pickup. Worktree đã có nhiều thay đổi Admin/Staff/Sales/Finance từ trước; lượt này chỉ bổ sung presentation/shared shell và kiểm chứng.

## Design contract / Implementation Details
Portal vận hành quán cà phê; mục tiêu chính là scan dữ liệu và thao tác chính xác. Tận dụng palette cream/espresso/caramel và Instrument Sans đã có. Một hệ button/card/spacing, bố cục đều cạnh, icon hiện có, không ảnh trang trí hoặc số liệu giả.

- `design-taste-frontend`: áp dụng hierarchy, typography đồng nhất, màu có mục đích, giảm texture/bóng, khoảng cách có hệ thống, review ảnh thực. Các yêu cầu hero toàn màn hình, 12–20 ảnh, scene/moment/dealer/copy marketing không phù hợp portal Admin/POS và bị giới hạn bởi scope/frontend.md; không sao chép starter/framework/assets của skill vào app.
- `frontend-ui-engineering`: áp dụng composition trên Blade hiện có, tương tác bằng button/link semantic, label/ARIA/focus, responsive drawer, inert/focus vòng khi mở drawer, xử lý localStorage bị chặn, kiểm chứng bằng test/build có sẵn.
- Tham khảo đã đọc trong thread: IBM Carbon UI shell/sidebar/data-table, Shopify Polaris typography. Không sao chép thương hiệu hoặc cài component library.

Ba gói công việc nối tiếp được user cho phép tự động triển khai: (1) nền tảng/shared shell; (2) rà và sửa gap của màn; (3) kiểm chứng/report. Mỗi gói giới hạn 30–90 phút; dependency là source Blade/auth/routes hiện có. Không tự triển khai module nghiệp vụ kế tiếp.

## Files Created
- tests/Frontend/internal-shell.test.mjs
- docs/implementation/UI_SKILLS_AUTOMATION_REPORT.md

## Files Modified (chỉ phần bổ sung của lượt này)
- resources/views/layouts/internal.blade.php: role thật tách workspace; Admin chuyển POS/quản trị ở topbar; bỏ search/notification chưa hoạt động, thêm nút đóng drawer, accessible logout.
- resources/js/internal.js: thu gọn sidebar desktop lưu preference; drawer mobile với inert, Escape, focus wrap/restore, resize và storage fallback; giữ nguyên xử lý order type.
- resources/css/internal/sneat.css: button variants có base độc lập, target48px, nền yên tĩnh, heading nội bộ cùng sans, card/border nhẹ, focus và responsive shell; presentation cho bảng đơn/khu vực bàn.
- resources/views/staff/orders/index.blade.php: mã đơn không bẻ từng đoạn, nhãn trạng thái Việt; giữ trạng thái backend.
- resources/views/staff/tables/index.blade.php: nhóm dữ liệu đã eager load theo area_id, cấp heading đúng; không thêm query hay hành động bàn.
- resources/views/staff/reservations/index.blade.php: nhãn trạng thái Việt, mô tả đúng màn đọc, không hứa khả năng duyệt chưa có trên màn này.
- tests/Feature/RoleAuthorizationTest.php: kiểm Admin ở workspace Staff vẫn mang role Admin và link quay về; Staff không có link quản trị.

## Files Deleted
None.

## Tests / Checks Executed
- RoleAuthorizationTest:13 PASS/80 assertions.
- Node native test runner, internal-shell.test.mjs:3 PASS; desktop persistence, mobile focus/Escape/inert, resize và blocked storage. Đây là kiểm tương tác mô phỏng, không thay browser.
- Default Laravel regression sau shell/foundation:467 total,451 PASS,16 SKIP,6132 assertions; storage/logs/ui-skills-regression.json. Bản sửa nhãn/nhóm bàn sau đó cần kiểm lại trước kết luận cuối.
- npm run build:PASS sau shell/foundation; cần build lại khi hoàn tất phần còn lại. Warning cũ fontaine optional và hai ảnh runtime còn tồn tại.

## Test Results
Các kết quả ở trên chỉ cho phạm vi tương ứng. Không coi16 SKIP là PASS; không chạy migration/seed trên coffee_shop. Chưa tuyên bố tất cả29 màn đã được visual QA.

## Acceptance Criteria
| AC | Trạng thái |
| --- | --- |
| Áp dụng cả hai skill vào quyết định và code thực tế | Đã triển khai, review cuối còn tiếp tục |
| Admin ↔ POS đúng quyền, role thật không đổi theo workspace | PASS feature test |
| Button đủ base/style, font nội bộ thống nhất | Source/build đạt; visual UNVERIFIED |
| Sidebar desktop/mobile, keyboard/focus/persistence | PASS mô phỏng JS; browser UNVERIFIED |
| Màn Admin/Staff responsive, không overflow ở320/375/768/1024/1440 và zoom200% | UNVERIFIED |
| Không đổi nghiệp vụ/backend/dependency/DB | Không có thay đổi các lớp này trong lượt hiện tại |

## Risks
Stylesheet nội bộ có nhiều lớp legacy; cần kiểm CSS cascade trên màn thật. UI state DOM tests không chứng minh visual/focus trong browser thật. Thay font có thể thay chiều rộng nội dung. Drawer phụ thuộc JS; không có JS thì navigation HTML vẫn có, hành vi responsive cũ cần kiểm.

## Blockers
Computer Use đã được thử lại trong lượt triển khai và tự dừng vì không xác định chắc URL Edge để enforce policy. Không tiếp tục input sau khi công cụ dừng. Visual QA vẫn chưa đạt; không suy luận từ feature tests.

## Evidence follow-up
Kiểm tra HTTP có đăng nhập demo: `/staff/brewing` và `/admin/dashboard` trả200 và đúng bundle `sneat-C9nWgSPk.css`, `app-BJI4hbrs.css`; Kanban/KPI có trong HTML. Không coi đây là visual QA. Tiến trình regression cuối kết thúc exit0 nhưng file output rỗng: kết quả lần này UNVERIFIED, đang chạy lại để lấy output trực tiếp. Diff check mở rộng thấy trailing whitespace có sẵn ở staff/online-requests/show.blade.php:67; không sửa file ngoài phần bổ sung chỉ để làm sạch diff.

### Kết quả cập nhật sau chờ tiến trình
File storage/logs/ui-skills-regression-final.json đã có kết quả:467 total,451 PASS,16 SKIP,6132 assertions,duration174139ms. Kết quả này thay kết luận file rỗng/UNVERIFIED phía trên;16 SKIP vẫn không được tính PASS. Lần chạy trực tiếp bổ sung session69340 vẫn chưa trả output tại lần poll cuối, không dùng nó làm bằng chứng hoặc khởi chạy thêm lần trùng. Diff check riêng tám file sửa trong lượt này exit0. Browser discovery tiếp tục báo `The browser access host is unavailable` qua các lượt liên tiếp; chưa thể thực hiện responsive/focus/contrast visual gate. Goal chưa hoàn thành; cần khôi phục kết nối browser để tiếp tục kiểm chứng và sửa theo ảnh thực, không hợp thức hóa bằng test backend.

## Assumptions
“Hai công nghệ” là hai skill vừa bổ sung. Không thêm React/Vue/Bootstrap, không nâng dependency. Khu vực Public giữ nguyên.

## Out-of-Scope Findings
Menu Admin demo có thể trống do registry chưa có dữ liệu; không hardcode/seed menu để che thiếu dữ liệu. Các warning ảnh/fonts build có từ trước. Các thay đổi Service/migration có sẵn không phải do lượt này.

## Unverified Items
Browser responsive/keyboard/contrast/rendered font toàn bộ nhóm màn; gate visual của skill chưa được chạy, không dùng gate marketing để chứng nhận portal.

## Engineer Review Required
Review riêng phần bổ sung giữa worktree dirty. Không commit/push/merge. Không coi báo cáo old SUCCESS là kết quả lượt này.

## Recommended Next Task
Tiếp tục kiểm chứng visual và sửa gap của các màn trong cùng goal; không mở rộng workflow/backend.

## Triển khai tiếp theo bộ ZIP
Đã đọc SCREEN_MAPPING/QUY_CHUAN_THIET_KE, mở ảnh mockup POS và đọc HTML; giải nén tham khảo PNG/HTML vào storage/app/ui-redesign-reference, không chạy make_mockups.py. Phát hiện báo cáo cũ viện dẫn sai mockup03/08 và sai route/view Recipe trong matrix; đã chỉnh matrix và đánh dấu visual UNVERIFIED. POS bỏ nhãn mở cửa hardcoded, thêm label tìm kiếm, bỏ inline height36px; PosOrderWorkflowTest6 PASS/57 assertions, build PASS. Các nhóm màn còn tiếp tục đối chiếu; chưa chứng nhận hoàn thành toàn bộ.


## V5 implementation continuation — 2026-11-21

Implemented the user-supplied V5 reference on actual Admin pages before final sidebar styling. Fixed configuration remains 12 items / 6 groups; no dynamic-menu rollback or backend changes. Current detail supersedes earlier Admin presentation descriptions: [V5 implementation report](ADMIN_12_SCREEN_V5_REPORT.md) and [12-screen matrix](ADMIN_12_SCREEN_V5_MATRIX.md).

Verification: focused PHP 298 passed / 1 skipped; overlapping final form rerun 58 passed; 7 Node tests passed; build passed; HTTP roots/116 child pages/11 empty searches verified. Visual QA remains UNVERIFIED because CUA was policy-stopped; no real browser screenshots produced. An initial broader test filter exposed an unrelated Authentication fixture failure, recorded in the V5 report; no claim of a full green project suite.
