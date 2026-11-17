# Task9.9 — Final test matrix

## Inventory / mini plan trước sửa code
Branch feature/area-table-pos-ui, worktree task9.1–9.8 và task9.9.md có thay đổi từ trước; không reset/commit. Đã đọc task9.9, nguồn quy định dự án trong phiên trước cùng chuỗi công việc, đối chiếu routes/source/tests hiện tại. Môi trường feature: phpunit.xml APP_ENV=testing, SQLite :memory:, DB_URL rỗng, session/cache array. Không có .env.testing; mỗi fixture kiểm tra driver và database trước tạo dữ liệu. MySQL chỉ dùng SELECT hoặc probe riêng đã có isolation guard và xóa đúng schema mới do probe tạo.

API thực tế: Admin GET areas/tables?view=floor/tables/{id}/detail; active Admin/Staff GET staff/tables/{table}/orders/{order}/menu, POST .../items trả201+saved_item_id+snapshot; GET .../checkout và .../estimate chỉ đọc. POST staff/orders dùng backend tạo đơn hiện có, không phải endpoint tạo từ sơ đồ. Pending+dine_in đúng table_id, duy nhất1 đơn, Area active và bàn không maintenance; read/display contract task9.7 phân biệt conflict/unknown/reserved. Display không cấp quyền ghi. Giá/snapshot từ OrderService; draft memory giữ khi lỗi theo task9.5/9.8. Không thay schema/auth/writers.

Ưu tiên: (1) thêm cross-page HTTP test từ Admin Area→Floor→detail→menu→save→checkout/proforma→refresh; (2) CSRF thực sự bật và identity/input/price/replay diagnostics; (3) query/time measurements trên fixture cô lập; (4) full PHP/Node/build/routes; (5) Edge fixture responsive, SELECT-only MySQL + isolated MySQL probe nếu sẵn; (6) bug/dependency/readiness reports. File dự kiến: tests/Feature/TableOrderIntegrationTest.php, docs/task09/task9.9-{test-matrix,bugs,report}.md; UI source/harness chỉ khi có lỗi xác nhận.

Không mock Payment để kết luận thành công. Staff không được mở Admin Floor là policy hiện tại, không mở quyền chỉ để khớp sơ đồ trong prompt. Replay Item chưa có idempotency nhiều tab/máy, cần đo/report chứ không tự sửa Order Service. Release/cancel với Reservation là finding cũ cần tái hiện trong test DB. Sidebar mobile focus đã biết từ task9.8. Kết luận READY chỉ khi không còn P0/blocker ngăn luồng cốt lõi, không suy từ test suite xanh.

## Execution matrix
Mỗi ID là một nhóm case; không cộng số nhóm này với số PHPUnit/Node test. Kết quả cuối: **19 PASS, 2 FAIL, 4 BLOCKED, 1 NOT RUN** trên26 nhóm. Test runner xanh không có nghĩa các nhóm nghiệm thu an toàn đều đạt. Evidence/output chi tiết trong task9.9-report.md và task9.9-bugs.md.

| ID | Priority | Scenario / expected | Method / environment | Status |
| --- | --- | --- | --- | --- |
| F01 | P0 | Admin Area→Floor→detail đúng identity và count | HTTP integration / SQLite memory | PASS |
| F02 | P0 | Continue→catalog→save quantity/note→reload→server total | HTTP backend thật / SQLite memory | PASS |
| F03 | P0 | Summary/proforma đúng bàn, tiền đã lưu; GET không ghi Order/Payment/Table | HTTP/query log / SQLite | PASS |
| F04 | P0 | Floor/detail/Area cùng display sau ghi/read; không tự release | HTTP / SQLite | PASS |
| S01 | P0 | Guest/inactive/Staff/Admin theo quyền; sai table/order ID bị chặn | Existing feature tests / SQLite | PASS |
| S02 | P0 | CSRF missing/wrong rejected, valid token accepted; HTTP methods | Real middleware enabled / SQLite | PASS |
| S03 | P0 | Client price/total/status/table_id không được ghi; invalid quantity/product/note bị chặn, XSS escaped | HTTP / SQLite | PASS |
| D01 | P0 | Repeated create không thêm active order trong các case đã kiểm tra | Existing Order tests + HTTP / SQLite | PASS |
| D02 | P0 | Repeated item POST / multi-client idempotency risk | Diagnostic HTTP / SQLite | FAIL —2 items /2 POST giống nhau |
| D03 | P0 | Reservation + cancellation/release chưa có policy đầy đủ | Diagnostic / SQLite; owner needed | FAIL —persisted available dù còn lịch; display reserved |
| C01 | P0 | Stale closed Order/maintenance/inactive Area rejected khi save | Feature / SQLite | PASS |
| C02 | P0 | Real MySQL simultaneous create/item/checkout | Isolated multi-process test chưa có Payment contract | BLOCKED |
| P01 | P0 | Payment pending/failed không thành paid, query params không giả success | Read-only checkout regression / SQLite | PASS |
| P02 | P0 | Cash/bank transfer payment confirmation/idempotency | Payment backend absent | BLOCKED |
| P03 | P0 | Paid→Order completion→release theo Reservation/cleanup | Payment/release contracts absent | BLOCKED |
| R01 | P1 | Official receipt đúng Payment hợp lệ | Receipt backend absent | BLOCKED |
| R02 | P1 | Proforma print media/PDF, không thay dữ liệu | Feature + Edge synthetic fixtures | PASS |
| Q01 | P1 | Floor/Area/detail/menu/summary query count, durations, dataset sizes | Isolated SQLite fixture, bounded scale | PASS —số liệu, chưa SLA |
| U01 | P1 | 375/768/1024/1440px, no overflow, controls48px, draft giữ khi resize/disclosure | Edge real browser / synthetic fixture, no business write | PASS |
| U02 | P1 | Offline/read/save uncertainty, no duplicate click, loading, print failure | Node/Edge fixture | PASS |
| U03 | P1 | Keyboard/focus/labels/status/reduced-motion review; shared sidebar limitations | Source + Edge fixture | PASS review; sidebar chưa đạt đầy đủ |
| M01 | P0 | Actual migrations, create/save/server money on isolated MySQL | Existing guarded probe, never shared schema | PASS |
| M02 | P1 | Actual MySQL read buckets reconcile | SELECT-only existing script | PASS |
| G01 | P1 | Area/CafeTable CRUD, Reservation, auth, Catalog/Order regression | Full PHP suite / SQLite memory | PASS —416 passed/2 skipped |
| G02 | P1 | Node suite/build/syntax/routes/Git review | Local tooling | PASS |
| N01 | P1 | Physical printer/80mm/multipage/hardware touch/screen reader | No hardware/tool coverage | NOT RUN |
