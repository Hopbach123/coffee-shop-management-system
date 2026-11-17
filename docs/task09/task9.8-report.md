## Task Status
PARTIAL — READY FOR REVIEW cho polish các UI hiện có. Responsive/interaction/build/regression đã kiểm tra. Chưa thể hoàn tất UI thanh toán/chứng từ chính thức khi task9.6/9.7 vẫn thiếu Payment/Receipt backend; không thay bằng chức năng giả. Shared mobile sidebar còn giới hạn accessibility có sẵn.

## Task ID
task9.8 — UI/UX Polish & Responsive Optimization

## Summary
Hoàn thiện Area/Floor/detail/menu/tạm tính bằng tokens/theme hiện có. Controls trong module tối thiểu48px, focus rõ, text/mã dài xuống dòng. Draft có chuyển nhanh tới giỏ, native disclosure thu/mở, nút +/- và đọc lại đơn có loading/khóa request lặp. Màn hình tạm tính làm nổi bật tổng tiền đã lưu và chuyển bảng thành các dòng có nhãn trên mobile; print vẫn là bảng gốc, không đổi số liệu.

## Files Created
- docs/task09/task9.8-ux-audit.md — audit và mini plan đã viết trước source changes.
- docs/task09/task9.8-report.md — báo cáo này.
- resources/css/staff/table-pos-polish.css — overrides chỉ trong các root UI Area/Table/POS.
- tests/manual/render-pos-ui.php — render8 fixture Blade tổng hợp, không ghi DB, dùng SQLite :memory: để cách ly layout/sidebar reads.
- tests/manual/pos-ui-browser.mjs — kiểm tra Edge headless qua CDP/Node built-in, không thêm package; server chỉ GET fixture/assets trên loopback.

## Files Modified
- resources/css/admin/areas.css — summary4 cột ở desktop; giữ breakpoint3/2 cột hiện có.
- resources/css/admin/table-floor-plan.css — màu reserved và maintenance, dashed maintenance để không chỉ dựa màu.
- resources/css/staff/table-menu.css — product hierarchy, cart disclosure/sticky có giới hạn chiều cao, input/quantity controls, jump navigation, saved/draft hierarchy, mobile layout.
- resources/css/staff/checkout-summary.css — nhấn tổng, responsive table/caption/data labels, actions mobile; media print giữ nguyên cấu trúc bảng.
- resources/css/app.css — import stylesheet scoped mới.
- resources/js/staff/table-menu.js — +/- theo giới hạn quantity hiện có, label/focus, count draft, mở giỏ từ shortcut; read-busy guard/loading/20s abort GET, không tự retry. Không đổi POST contract, acknowledgement/uncertain/partial-save policy.
- resources/views/staff/orders/table-menu.blade.php — headings, jump links, product action labels, native details mở sẵn cho giỏ.
- resources/views/staff/orders/checkout-summary.blade.php — class cho payment-readiness callout.
- resources/views/staff/orders/components/estimate-items.blade.php — data-label cho các ô số liệu trên screen nhỏ; giá trị tiền giữ nguyên.
- resources/views/admin/tables/floor-plan.blade.php — diễn đạt đúng trạng thái tổng hợp từ dữ liệu đã lưu theo task9.7.
- tests/JavaScript/table-menu.test.mjs — test controls quantity, duplicate read/save guard, read failure giữ draft; giữ regression partial save/uncertain/double-click.

Thay đổi task9.1–9.7 và docs/task09/task9.9.md tồn tại trước lượt này, được giữ nguyên. Các backend/routes/models/test PHP hiện trong Git diff thuộc công việc trước, không phải task9.8. Bản baseline những file dự kiến nằm trong storage/app/task98-before (ignored) để review delta lượt này. Không sửa shared layout/internal.js, dịch vụ nghiệp vụ, controller, request, auth, schema, migration hay dependency manifests.

## Files Deleted
None

## Implementation Details
### UX audit / fixes
Audit trước source changes nằm trong task9.8-ux-audit.md. P1 đã xử lý: giỏ khó tiếp cận trên mobile, quantity/note controls nhỏ, refresh không có pending guard, header/mã dài có nguy cơ tràn, tổng chưa nổi bật, muted text/status thiếu tương phản, trạng thái cùng màu. Khi kiểm tra ảnh phát hiện caption mobile co thành từng ký tự do table-caption trong block-table; đã sửa display/width và thêm assertion caption width. Khi mở rộng đo cả input/select phát hiện theme tải sau ghi đè xuống43px; đã tăng specificity trong module lên48px. Không sửa theme toàn ứng dụng.

### Design system / responsive
Giữ Sneat/internal theme đang sử dụng, font hiện có, coffee tokens, radius/spacing. Primary dùng --coffee-cocoa, outline nền surface/text cocoa; không thêm HEX hoặc framework. Text phụ pha từ text-muted và text để dễ đọc. Mã/tên dài wrap thay vì ellipsis mất thông tin. Status có text/icon; maintenance thêm dashed border, reserved dùng accent, conflict dùng danger. Không đổi resolver/status meaning.

Grid Area4/3/2 cột theo breakpoint; Floor3/2/1. Menu dưới75rem xếp catalog/cart thành một cột; desktop có cart sticky giới hạn100dvh và vùng cuộn riêng khi nhiều dòng. Jump links giúp tới draft/saved mà không tải lại. Checkout <=48rem dùng các ô có data-label; caption toàn chiều rộng, column headers chỉ visually hidden để không bỏ thông tin semantic. Mobile actions full-width. Print media không dùng layout block của screen, giữ table-row/thead/total và ẩn controls.

### Interaction / draft / states
Native details mở sẵn, không overlay/modal mới. Thu/mở bằng chuột hoặc Enter và chuyển kích thước giữ nguyên DOM/draft, không gọi server. Shortcut mở panel và focus summary. Quantity +/- không giảm dưới1 hoặc vượt giới hạn2147483647 đã có; input vẫn sửa trực tiếp, note giữ maxlength500. Bỏ draft line trả focus về input còn lại hoặc Add action. Draft chỉ trong memory, giữ beforeunload warning; không thêm localStorage.

Add chỉ soạn draft; Save từng dòng vẫn chỉ loại dòng sau acknowledgement201. Busy/uncertain guard vẫn chặn submit lặp. Read GET có loading, disable controls khi pending và chặn click lặp; tự abort sau20s rồi phục hồi control, giữ draft, không retry. Không cho Save/Read chạy chồng để tránh snapshot cũ ghi đè UI. Empty/no-results/conflict/error giữ từ task trước. Các trang GET render server không thêm skeleton hoặc animation tải giả. Message aria-live, disabled rõ ràng, focus3px, reduced motion dựa hệ thống hiện có.

### Checkout / financial integrity
Chỉ làm nổi bật $order->total_amount và các giá trị đã lưu; không tính lại tổng/giá/thuế/discount/payment balance. Payment record vẫn hiển thị nguyên status/method/amount tham chiếu, không xác minh paid từ in hoặc màu. Không có nút thu tiền/phương thức thanh toán giả. Tài liệu in tiếp tục ghi “Phiếu tạm tính”, không phải biên nhận/hóa đơn điện tử; không báo máy in đã in.

### Accessibility / navigation
Nút, input/select/summary đo tối thiểu48px trong module, named product/quantity/remove actions, keyboard native controls, focus-visible và reduced-motion. Không có custom cart dialog nên không cần trap/inert hoặc Escape để đóng modal; summary thu/mở inline, không khóa scroll. Shared mobile sidebar có Escape/backdrop có sẵn nhưng chưa có focus trap/return focus/inert; không tuyên bố đã sửa shell toàn cục. Contrast đã cải thiện theo tokens và xem ảnh, chưa đo WCAG toàn bộ cặp màu hoặc test screen reader.

## Tests / Checks Executed
| Check | Result / evidence |
| --- | --- |
| Full PHP artisan test --compact | PASS 411, SKIPPED2 trên tổng413; 5,823 assertions; storage/logs/task98-tests.txt |
| Sau các sửa Blade/CSS cuối: targeted AreaManagementUI/TableFloorPlan/TableStatusSynchronization/TableOrderIntegration/TableCheckoutIntegration | PASS30, 412 assertions |
| Node --test tests/JavaScript/*.test.mjs | PASS11, gồm6 menu,3 print,2 status refresh |
| npm run build | PASS; final log storage/logs/task98-build.txt |
| Pint tests/manual/render-pos-ui.php | PASS |
| PHP fixture renderer | PASS8 trang, SQLite :memory:, không DB writes/live data |
| Edge headless CDP browser | PASS32 page/viewport combinations +3 interaction groups, không Runtime JS exception |
| Git status/diff + diff --check | PASS, chỉ cảnh báo CRLF/LF có sẵn; không commit/push/merge |

## Test Results
Browser thật Edge headless với profile riêng trong storage/app/task98-ui, server fixture chỉ GET trên127.0.0.1:8798; không kết nối Laravel server/MySQL. Mọi fetch trong scenario lỗi mạng bị thay bằng stub chỉ ở harness, không gửi business writes. Fixture là dữ liệu tổng hợp stress tên/mã dài, không phải bằng chứng backend hay dữ liệu demo được seed. Sidebar fallback trong ảnh là do fixture không có admin_menus, không kết luận dữ liệu sidebar thật lỗi.

### Responsive matrix
| Width × height | Pages | Assertions | Result |
| --- | --- | --- | --- |
| 375×900 mobile portrait | Area/Floor/detail/menu/checkout/estimate/floor-empty/floor-error | document scrollWidth<=viewport+1; visible controls>=47px (CSS48px); mobile caption full width | PASS8 |
| 768×900 tablet | Cùng8 trang | Overflow/targets/caption | PASS8 |
| 1024×900 laptop/tablet landscape width | Cùng8 trang | Overflow/targets | PASS8 |
| 1440×900 desktop | Cùng8 trang | Overflow/targets | PASS8 |

Đây là viewport mô phỏng trên browser desktop, không phải thiết bị touch thật. Screenshot full-page375/1440 và browser-results.json nằm tại storage/app/task98-ui. Đã xem trực tiếp ảnh menu mobile/desktop, checkout mobile, Floor desktop; không tuyên bố đã nhìn bằng mắt mọi screenshot hoặc mọi kích thước.

### Interaction / print
- PASS: Add2 dòng, tăng quantity, nhập note, resize1440→375, thu/mở và jump giữ count/quantity/note; focus về summary, outline3px; Enter thu panel.
- PASS: reduced-motion có effective transition<=1ms (global design system dùng0.01ms).
- PASS: GET offline báo lỗi, giữ draft; Save offline giữ/khóa uncertain draft, không gửi lại. Node kiểm tra refresh click lặp chỉ1 request, save không chạy trong lúc read, unlock sau response/error.
- PASS: media print ẩn controls, giữ table-row, không sidebar, tổng123456.70 VNĐ còn trong DOM; xuất estimate-a4.pdf (fixture, không hóa đơn thật).
- NOT RUN: máy in vật lý, in khổ80mm, PDF text extraction/pagination nhiều trang, screen reader, Safari/Firefox, touch hardware, zoom200%, bộ lọc/giao dịch thực tế từ browser này.

### Evidence (local, ignored artifacts)
- storage/app/task98-ui/browser-results.json —32 checks + interaction results.
- storage/app/task98-ui/{areas,floor,detail,menu,checkout,estimate}-{375,1440}.png —ảnh thật từ browser, không mock screenshot.
- storage/app/task98-ui/estimate-a4.pdf —A4 fixture.
- tests/manual/render-pos-ui.php và pos-ui-browser.mjs có thể chạy lại sau build. Browser harness cần quyền launch Edge; không dùng profile cá nhân. Các assets build/profile/log/fixtures không được thêm vào Git.

## Acceptance Criteria
| AC | Result | Evidence / limitation |
| --- | --- | --- |
| 1 | PASS | Audit UI tasks2–7, reports/source inspected |
| 2 | PASS | task9.8-ux-audit.md trước source changes |
| 3,4,5,6 | PASS từng mục | Scoped styles, Area/Floor fixtures32 viewport checks, label/icon/border |
| 7 | PASS | Detail page native navigation, responsive fixture; không custom modal |
| 8,9,10,11 | PASS từng mục | Product labels, jump/cart disclosure, +/-48px, Save loading/disabled; Node/browser |
| 12 | PASS | Total emphasis giữ nguyên số liệu, checkout screenshots |
| 13 | PASS cho records hiện có | Raw status/reference và chưa xác minh rõ; Payment action/backend BLOCKED |
| 14 | PASS | Print và unavailable Payment tách rõ; không nút thu tiền |
| 15 | PASS cho proforma A4 fixture | Print media/PDF generated; official receipt/physical print chưa có/chưa kiểm tra |
| 16,17,18,19,20 | PASS từng mục trong viewport matrix |32 checks không page overflow, controls>=48px, tổng/actions còn DOM; hardware/zoom chưa kiểm tra |
| 21 | PASS | Hover/pressed/focus, selected Area aria-current hiện có; không thêm chọn bàn giả |
| 22,23 | PASS từng mục | Busy/reading/uncertain disabled, loading labels, Node tests |
| 24,25,26 | PASS từng mục | Existing feature regression + empty/error fixtures; offline browser giữ draft |
| 27 | PASS cho cart inline | Native summary close/open/Enter; không overlay modal mới; shared sidebar limitation bên dưới |
| 28 | UNVERIFIED toàn shell | Module native keyboard/focus PASS; sidebar focus containment và screen reader chưa đạt review đầy đủ |
| 29,30,31 | PASS từng mục | Named controls, labels, focus3px browser; status text/icon |
| 32,33 | PASS từng mục | No new animation, reduced-motion browser check |
| 34,35 | PASS từng mục | Resize/disclosure giữ draft; duplicate read/save guards, uncertain tests |
| 36,37,38,39,40 | PASS từng mục | Không Payment request/success giả/status writer; amount và pricing không đổi |
| 41,42,43,44,45,46 | PASS từng mục | Không backend/catalog/auth/schema/migration/dependency changes |
| 47 | PASS |411 PHP PASS +11 Node PASS,2 PHP skipped được báo riêng |
| 48,49,50,51 | PASS từng mục | Scope delta, không Git mutation, evidence/report trung thực |
| 52 | UNVERIFIED đầy đủ | Các P1 module đã sửa; shared shell accessibility/Payment integration chưa hoàn tất |
| 53,54 | PASS từng mục | Browser evidence thực tế, không tuyên bố toàn bộ POS hoàn thành |

## Risks
Draft vẫn chỉ tồn tại trong trang; đổi URL/filter/pagination cần xác nhận beforeunload và có thể mất draft theo policy cũ, resize/disclosure không mất. Không có idempotency nhiều tab/máy mới. Bảng responsive dùng CSS nên cần screen-reader/hardware review. Stress fixtures không bao phủ dữ liệu nhiều trang/caption cực lớn/print nhiều trang. CSS color-mix/:has dựa browser hiện đại; chưa kiểm tra browser cũ. Hai test PHP skipped không được tính đạt. Build giữ cảnh báo optional fontaine và poster chưa resolve có sẵn; không tự cài dependency/sửa asset ngoài scope.

## Blockers
- Đạt/Payment/Receipt owner: backend xử lý/xác minh thanh toán, methods/result contract và chứng từ chính thức chưa có để polish toàn bộ flow; chưa triển khai UI success giả.
- Review shared shell accessibility/focus với owner trước khi yêu cầu toàn bộ giao diện mobile đạt accessibility đầy đủ; module này không refactor layout chung.

## Assumptions
None ngoài policy đã duyệt các task trước. Dùng theme/layout/token hiện hành; menu cart là disclosure inline, không modal/drawer overlay. Tổng authoritative vẫn do backend. Native details không auto-close theo resize nên user giữ lựa chọn mở/đóng.

## Out-of-Scope Findings
Shared mobile navigation thiếu focus trap/return focus/inert và hidden-sidebar keyboard review; unchanged resources/js/internal.js. Payment/Receipt/release blockers task9.6/9.7 vẫn còn. Source Order cancellation release chưa kiểm tra Reservation là finding cũ task9.7, không sửa. Không sửa sidebar data/seeding qua fixture hoặc deploy.

## Unverified Items
Screen reader/contrast audit toàn bộ, hardware touch, Safari/Firefox/browser cũ, zoom200%, physical print/80mm/multipage; Payment end-to-end; stress performance production. Browser harness kiểm tra fixture UI và stub network, không chứng minh checkout Payment thành công.

## Engineer Review Required
Review delta task9.8 riêng với worktree task9.1–9.7. Kiểm tra /admin/areas, /admin/tables?view=floor, /admin/tables/{id}/detail; từ bàn có đúng một pending dine_in vào menu và tạm tính. Xem screenshots/PDF và thử keyboard/touch thực tế, filter/pagination có draft, print dài. Chấp nhận/điều chỉnh inline disclosure trước khi merge. Không tự commit/push/merge.

## Recommended Next Task
TASK9.9 Testing & Final Integration sau engineer review và chốt các dependency còn thiếu. Handoff: scoped styles/native cart, viewport matrix và fixtures/harness có thể chạy lại; bổ sung real-device/browser/screen-reader/multipage-print và Payment owner integration checks. Không triển khai tự động.
