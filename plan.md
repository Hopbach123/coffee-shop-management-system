# Coffee Shop Management System – Implementation Plan

## 1. Purpose

Tài liệu này là roadmap triển khai chính thức của Coffee Shop Management System. Roadmap xác định thứ tự phụ thuộc từ nền tảng, dữ liệu, xác thực, nghiệp vụ cốt lõi, frontend, kiểm thử đến bàn giao.

`requirements.md` quyết định hệ thống cần hỗ trợ gì; `architecture.md` quyết định cấu trúc kỹ thuật; `frontend.md` quyết định tiêu chuẩn giao diện; `plan.md` chỉ quyết định thứ tự và cách chia nhỏ công việc. Khi các tài liệu mâu thuẫn, không được tự suy đoán hoặc triển khai tiếp.

## 2. Planning Principles

1. Lập kế hoạch theo dependency: thiết kế dữ liệu được duyệt trước migration; migration trước model; model trước workflow nghiệp vụ; xác thực trước chức năng được bảo vệ; Menu trước Order; Order trước Payment; dữ liệu bán hàng cốt lõi trước Dashboard.
2. Mỗi technical task phải có một kết quả cụ thể, có thể review và test độc lập, thông thường hoàn thành trong **30–90 phút**.
3. Task được ước lượng trên 90 phút phải được chia nhỏ trước khi tạo `prompt.xml`.
4. Không trộn database, backend, frontend và cải tiến tùy chọn vào một task nếu chúng có thể kiểm chứng riêng.
5. Chỉ lập task cho actor và domain đã được `requirements.md` phê duyệt.
6. Chi tiết nghiệp vụ chưa được duyệt phải ghi **TBD / Deferred**; task tương ứng không đạt Definition of Ready.
7. P0/P1 không được bị chặn bởi cải tiến P3.
8. Mỗi task kết thúc bằng engineer review, test/build/checklist, báo cáo và đề xuất commit; không tự động chạy task kế tiếp.
9. Roadmap được phân rã dần khi phase sắp bắt đầu, tránh tạo hàng trăm task mang tính suy đoán.

## 3. AI Agent Development Workflow

Mọi technical task bắt buộc đi theo quy trình:

1. Requirement được xác nhận trong `requirements.md` hoặc tài liệu yêu cầu đã duyệt.
2. Chọn task từ `plan.md` và xác nhận dependency đã hoàn thành.
3. Review phạm vi, ngoài phạm vi, Acceptance Criteria và Test Plan.
4. Tạo một `prompt.xml` cho task nhỏ đã chọn.
5. Engineer/team review prompt trước khi thực thi.
6. Agent đọc đầy đủ `architecture.md`, `requirements.md`, `plan.md`, `AGENTS.md`, `frontend.md` nếu liên quan frontend và source liên quan.
7. Agent chỉ triển khai trong phạm vi task.
8. Engineer review diff và hiểu các thay đổi.
9. Chạy test, build và checklist thích hợp.
10. Đánh giá từng Acceptance Criteria.
11. Tạo task report gồm file thay đổi, kết quả kiểm tra, rủi ro và blocker.
12. Chỉ đề xuất commit sau khi review đạt; engineer quyết định commit.
13. Chỉ chuyển sang task tiếp theo sau khi task hiện tại được chấp nhận.

Luồng chuẩn: **Requirement → Plan → Small task → prompt.xml → Agent implementation → Engineer review → Test/Build → Report → Commit**.

## 4. Task Definition Standard

Mỗi task trước khi được chuyển thành `prompt.xml` phải có tối thiểu:

| Trường | Yêu cầu |
| --- | --- |
| Task ID | ID ổn định theo quy ước tại mục 5 |
| Task Name | Tên hành động rõ ràng, một kết quả chính |
| Goal | Một outcome cụ thể |
| Dependencies | Các task bắt buộc hoàn thành trước |
| Scope | File, layer hoặc hành vi được phép thay đổi |
| Out of Scope | Những gì tuyệt đối không thay đổi trong task |
| Expected Output | File, hành vi hoặc tài liệu dự kiến |
| Acceptance Criteria | Điều kiện hoàn thành có thể kiểm tra |
| Test Plan | Lệnh test/build hoặc checklist review |
| Estimate | Thông thường 30–90 phút |
| Priority | P0, P1, P2 hoặc P3 |

Các bảng roadmap bên dưới là task charter ở mức dự án. Trước khi thực thi, charter phải được đưa vào template trên; nếu phạm vi chưa rõ hoặc vượt 90 phút, phải chia tiếp và cập nhật roadmap.

## 5. Task Naming Convention

- Chuẩn chính: `CAFE-{DOMAIN}-{TYPE}-{NNN}`.
- Task liên phase có thể dùng dạng ngắn ổn định đã được roadmap phê duyệt như `CAFE-DB-001`, `CAFE-MODEL-001`, `CAFE-SEED-001`.
- `DOMAIN`: `AUTH`, `TABLE`, `MENU`, `ORDER`, `PAYMENT`, `RES`, `PROMO`, `INV`, `DASH`, `PUBLIC`, `TEST`, `DELIVERY` hoặc domain/phạm vi đã duyệt.
- `TYPE`: `INIT`, `DOC`, `DB`, `MODEL`, `SEED`, `BE`, `FE`, `TEST`, `FIX`, `REFACTOR`.
- `NNN`: số tăng dần có ba chữ số.
- ID đã dùng trong prompt, report hoặc commit không được đổi hoặc tái sử dụng.
- `FIX` và `REFACTOR` chỉ được tạo khi có lỗi/phạm vi cụ thể, không dùng làm tên cho công việc mơ hồ.

## 6. Priority Levels

| Mức | Ý nghĩa | Ví dụ |
| --- | --- | --- |
| P0 | Nền tảng hoặc blocker cho nhiều phase | Tài liệu điều hành, ERD được duyệt, migration foundation, auth foundation |
| P1 | Nghiệp vụ cốt lõi để vận hành | Menu, Table, Order, Payment, Reservation cơ bản |
| P2 | Quan trọng để hoàn thiện sản phẩm | Promotion, Inventory, Dashboard, public UX, test coverage |
| P3 | Tùy chọn, chỉ làm khi được phê duyệt | QR menu, PDF/in ấn nâng cao, CI/CD mở rộng |

## 7. Project Roadmap

Quy ước trạng thái: **Done** = đã hoàn thành và có source/tài liệu; **Planned** = đã có requirement đủ ở mức roadmap; **Deferred/TBD** = chưa được phép triển khai cho đến khi requirement liên quan được duyệt.

Mỗi task trong các bảng chỉ được thay đổi phạm vi/output đã nêu. Mọi thay đổi khác là out of scope. Cột kiểm tra tóm tắt Acceptance Criteria và Test Plan bắt buộc.

### Phase 0 – Project Foundation

Mục tiêu: thiết lập dự án và các nguồn thẩm quyền trước khi triển khai nghiệp vụ.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức / trạng thái |
| --- | --- | --- | --- | --- | --- |
| `CAFE-INIT-001` | Khởi tạo Laravel project | Không | Laravel project chạy được | Boot/test cơ bản đạt | Done |
| `CAFE-INIT-002` | Chuẩn hóa cấu trúc | INIT-001 | Cấu trúc folder và route scope nền tảng | Review tree/diff | Done |
| `CAFE-DOC-001` | Tạo chuẩn kiến trúc | INIT-002 | `architecture.md` | Tài liệu được review | Done |
| `CAFE-DOC-002` | Tạo yêu cầu nghiệp vụ | DOC-001 | `requirements.md` | Actor/domain/scope được review | Done |
| `CAFE-DOC-003` | Tạo implementation plan | DOC-002 | `plan.md` | Phase, dependency, DoR/DoD hợp lệ | 30–60 phút / P0 / Current |
| `CAFE-DOC-004` | Tạo quy tắc cho AI Agent | DOC-003 | `AGENTS.md`, không đổi application code | Review rule và Git diff | 30–60 phút / P0 / Planned |
| `CAFE-FE-001` | Thiết lập frontend design standard và foundation | DOC-001 | `frontend.md`, layout/component/public home foundation | Build và responsive check đạt | Done |

### Phase 1 – Database Design and Migration

Mục tiêu: chuyển thiết kế dữ liệu đã phê duyệt thành Laravel migrations theo nhóm phụ thuộc. Không tự quyết định bảng, cột, enum hoặc quan hệ từ roadmap này.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-DB-001` | Review và phê duyệt ERD/dependency graph | DOC-004 | Audit thiết kế dữ liệu theo SRS | Engineer review với requirements/architecture | 60–90 phút / P0 |
| `CAFE-DB-001B` | Hoàn thiện Frozen Database Design | DB-001 audit finding | Chốt đúng 18 bảng, toàn bộ matrix/rule/dependency/order; chỉ tài liệu | Static consistency review, không đổi migration/database | 120–240 phút / P0 |
| `CAFE-DB-RE-AUDIT-001` | Re-audit DB-001/002/003 | DB-001B | Đối chiếu migration read-only với Frozen Design | Báo cáo mismatch và kết luận re-audit | 60–90 phút / P0 |
| `CAFE-DB-CORRECTION-001` | Align Existing Migrations with Frozen Database Design | DB-RE-AUDIT-001; chỉ chạy khi có mismatch | Sửa riêng migration DB-002/003 theo findings đã duyệt | Migrate/rollback/schema review | 60–90 phút / P0 |
| `CAFE-DB-002` | Migration nhóm foundation | DB-001 | Chỉ entity foundation theo ERD đã duyệt | Migrate/rollback nhóm và schema review | 60–90 phút / P0 |
| `CAFE-DB-003` | Migration nhóm Menu/Table | DB-002 | Chỉ entity Menu/Table được ERD xác định | Migrate/rollback và FK check | 60–90 phút / P1 |
| `CAFE-DB-004` | Migration nhóm Reservation/variant | DB-001B, DB-RE-AUDIT-001, DB-CORRECTION-001 nếu cần | Chỉ `product_variants`, `reservations` theo Frozen Design | Migrate/rollback và FK check | 60–90 phút / P1 |
| `CAFE-DB-005` | Migration nhóm Recipe/Order core | DB-004 | Theo canonical order: `product_recipes`, `orders`, `order_items` | Migrate/rollback và constraint check | 60–90 phút / P1 |
| `CAFE-DB-006` | Migration nhóm Payment/Promotion history | DB-005 | Theo canonical order: `payments`, `order_promotions`; không gateway/voucher engine | Migrate/rollback và FK check | 60–90 phút / P1 |
| `CAFE-DB-007` | Migration nhóm Inventory/final Order detail | DB-006 | Theo canonical order: `inventory_transactions`, `order_item_toppings`; không procurement | Migrate/rollback và FK check | 60–90 phút / P2 |
| `CAFE-DB-008` | Kiểm tra toàn bộ migration | DB-002…007 | Sửa lỗi migration trong phạm vi thiết kế duyệt | `migrate`, `rollback`, `migrate:fresh` đạt | 30–60 phút / P0 |

Nếu DB-002…007 vượt 90 phút sau khi ERD được duyệt, từng nhóm phải tách thành ID mới trước khi tạo prompt.

### Phase 2 – Eloquent Model Foundation

Mục tiêu: tạo và kiểm chứng model từ schema; không đưa workflow nghiệp vụ lớn vào model.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-MODEL-001` | Chọn và phê duyệt chiến lược model generation | DB-008 | Quyết định công cụ/cấu hình; package chỉ cài khi task cho phép rõ | Review tương thích Base/Custom Model policy | 30–60 phút / P0 |
| `CAFE-MODEL-002` | Sinh Base Models | MODEL-001 | `app/Models/Base/` từ schema hiện hành | Generation log, lint và diff review | 60–90 phút / P1 |
| `CAFE-MODEL-003` | Review casts và relationships | MODEL-002 | Chỉ sửa generated configuration/source theo strategy duyệt | Relationship/cast tests hoặc checklist | 60–90 phút / P1 |
| `CAFE-MODEL-004` | Tạo và kiểm chứng Custom Models | MODEL-003 | `app/Models/`, không business workflow lớn | Inheritance, boot và model tests đạt | 60–90 phút / P1 |

### Phase 3 – Seeder and Development Data

Mục tiêu: cung cấp dữ liệu phát triển an toàn, không biến seed data thành business rule.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-SEED-001` | Seed Admin/Staff phát triển | MODEL-004 | Tài khoản nội bộ mẫu, không secret production | Seed idempotency và login precondition check | 30–60 phút / P0 |
| `CAFE-SEED-002` | Seed khu vực/bàn | MODEL-004 | Fixture Table theo schema duyệt | Count/relation check | 30–60 phút / P1 |
| `CAFE-SEED-003` | Seed Menu mẫu | MODEL-004 | Category/Product và dữ liệu liên quan đã duyệt | Count/relation/availability check | 60–90 phút / P1 |
| `CAFE-SEED-004` | Seed Inventory fixture an toàn | MODEL-004 | Ingredient/recipe đã duyệt, không supplier/procurement | Relation and fixture review | 30–60 phút / P2 |
| `CAFE-SEED-005` | Kiểm tra fresh seed | SEED-001…004 | Chỉ sửa lỗi seeder/factory trong scope | `migrate:fresh --seed` và test đạt | 30–60 phút / P0 |

### Phase 4 – Authentication and Authorization

Mục tiêu: bảo vệ khu vực Admin/Staff trước khi xây dựng chức năng nội bộ. Customer không bắt buộc tài khoản.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-AUTH-DOC-001` | Chốt ma trận truy cập nội bộ tối thiểu | DOC-004, SEED-001 | Admin/Staff access rules; quyền còn thiếu ghi TBD | Requirements review và approval | 30–60 phút / P0 |
| `CAFE-AUTH-BE-001` | Thiết lập auth foundation | AUTH-DOC-001, MODEL-004 | Cấu hình auth cho internal User, không Customer account | Auth boot/config tests | 60–90 phút / P0 |
| `CAFE-AUTH-BE-002` | Login/logout nội bộ | AUTH-BE-001 | Request/controller/session flow cho Admin/Staff | Valid/invalid/login/logout feature tests | 60–90 phút / P0 |
| `CAFE-AUTH-FE-001` | Giao diện login | AUTH-BE-002, FE-001 | Blade login responsive/accessibility | Browser, validation UI và build check | 60–90 phút / P1 |
| `CAFE-AUTH-BE-003` | Bảo vệ khu vực Admin | AUTH-BE-002 | Middleware/authorization Admin | Guest/Staff/Admin access tests | 30–60 phút / P0 |
| `CAFE-AUTH-BE-004` | Bảo vệ khu vực Staff | AUTH-BE-002 | Middleware/authorization Staff | Guest/Admin/Staff access tests theo rule duyệt | 30–60 phút / P0 |
| `CAFE-AUTH-TEST-001` | Kiểm tra auth/role tổng hợp | AUTH-BE-003, AUTH-BE-004 | Test auth và access matrix, không thêm quyền mới | Auth suite đạt | 30–60 phút / P0 |

### Phase 5 – Master Data Management

Mục tiêu: triển khai CRUD đơn giản trước workflow phức tạp. Mỗi backend task gồm validation, authorization, search/pagination/status chỉ khi requirement/schema đã duyệt; mỗi frontend task tuân theo `frontend.md`.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-TABLE-BE-001` | CRUD khu vực/bàn backend | AUTH-TEST-001, MODEL-004 | Admin CRUD và Staff read theo quyền duyệt | Feature/validation/authorization tests | 60–90 phút / P1 |
| `CAFE-TABLE-FE-001` | UI quản lý và xem bàn | TABLE-BE-001 | Admin/Staff Blade pages responsive | Browser/accessibility/build check | 60–90 phút / P1 |
| `CAFE-MENU-BE-001` | CRUD Category backend | AUTH-TEST-001, MODEL-004 | Admin Category CRUD | Feature/validation tests | 60–90 phút / P1 |
| `CAFE-MENU-FE-001` | UI Category | MENU-BE-001 | Admin list/create/edit views | Browser/build/responsive check | 60–90 phút / P1 |
| `CAFE-MENU-BE-002` | CRUD Product backend | MENU-BE-001 | Admin Product CRUD theo schema duyệt | Feature/validation/search tests | 60–90 phút / P1 |
| `CAFE-MENU-FE-002` | UI Product cơ bản | MENU-BE-002 | Admin list/create/edit views | Browser/build/responsive check | 60–90 phút / P1 |
| `CAFE-MENU-BE-003` | Product availability/status | MENU-BE-002 | Trạng thái được duyệt, không tự tạo enum | State/authorization tests | 30–60 phút / P1 |
| `CAFE-MENU-FE-003` | Search và pagination Product | MENU-BE-002 | Filter/list UI, không image upload ngoài approval | Query/browser/build check | 30–60 phút / P2 |

Product Variant/Size và Topping đã thuộc baseline SRS v1.2; task BE/FE vẫn phải bám trạng thái, giá, snapshot và validation trong Frozen Design. Product image upload cần requirement riêng nếu phạm vi vượt đường dẫn ảnh đã có trong schema. Không tạo Customer account/Customer CRUD trong phase này; Customer chưa bắt buộc có tài khoản, còn contact đặt bàn thuộc Reservation.

### Phase 6 – Order / POS Core

Mục tiêu: triển khai từng thao tác Order độc lập theo baseline SRS v1.2 gồm dine-in, takeaway, variant, topping, item notes, snapshot và state sets đã duyệt. Task nghiệp vụ vẫn phải chốt transition/concurrency và test cases trước khi code.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-ORDER-DOC-001` | Chi tiết hóa state transition và calculation rules | TABLE-FE-001, MENU-FE-002 | Bám baseline dine-in/takeaway, item status, snapshot và totals đã Frozen | Engineer/business approval | 60–90 phút / P1 |
| `CAFE-ORDER-BE-001` | Tạo active dine-in order cho bàn | ORDER-DOC-001 | Một workflow tạo order theo rule duyệt | Feature, authorization, conflict tests | 60–90 phút / P1 |
| `CAFE-ORDER-FE-001` | Hiển thị POS order workspace | ORDER-BE-001 | Staff Blade workspace responsive/touch-friendly | Browser/build/accessibility check | 60–90 phút / P1 |
| `CAFE-ORDER-BE-002` | Thêm product vào order | ORDER-BE-001, MENU-BE-003 | Add item theo availability/rule duyệt | Feature and validation tests | 60–90 phút / P1 |
| `CAFE-ORDER-BE-003` | Cập nhật số lượng item | ORDER-BE-002 | Quantity operation và recalculation đã duyệt | Boundary/calculation tests | 30–60 phút / P1 |
| `CAFE-ORDER-BE-004` | Xóa item khỏi order | ORDER-BE-002 | Remove operation theo state rule | State/authorization tests | 30–60 phút / P1 |
| `CAFE-ORDER-BE-005` | Tính subtotal/total | ORDER-DOC-001, ORDER-BE-002 | Chỉ công thức đã phê duyệt | Calculation test matrix | 60–90 phút / P1 |
| `CAFE-ORDER-BE-006` | Chuyển trạng thái order | ORDER-DOC-001, ORDER-BE-005 | Transition được duyệt | Valid/invalid transition tests | 60–90 phút / P1 |
| `CAFE-ORDER-FE-002` | AJAX add/update/remove item | ORDER-BE-003, ORDER-BE-004, ORDER-FE-001 | Progressive enhancement, không business rule trong JS | Browser, fallback, error-state/build check | 60–90 phút / P1 |
| `CAFE-ORDER-TEST-001` | Kiểm tra workflow order cốt lõi | ORDER-BE-006, ORDER-FE-002 | Test end-to-end trong scope đã duyệt | Order suite và manual POS checklist đạt | 60–90 phút / P1 |

Chưa có task triển khai riêng: cấu hình thuế/phụ phí, rounding/discount allocation ngoài baseline; hoàn tiền/điều chỉnh sau bán. Takeaway, item notes, variant/size và topping không còn là schema TBD.

### Phase 7 – Payment and Invoice

Mục tiêu: ghi nhận Payment nhất quán sau khi Order cốt lõi ổn định. Invoice/PDF/printing là tùy chọn chưa được phê duyệt.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-PAYMENT-DOC-001` | Chi tiết hóa checkout rules | ORDER-TEST-001 | Bám phương thức `cash`/`bank_transfer`, trạng thái Frozen, không split/partial và tối đa một payment paid | Requirement approval | 30–60 phút / P1 |
| `CAFE-PAYMENT-BE-001` | Validate order trước thanh toán | PAYMENT-DOC-001 | Checkout preconditions | Valid/invalid order tests | 30–60 phút / P1 |
| `CAFE-PAYMENT-BE-002` | Ghi nhận payment | PAYMENT-BE-001 | Payment record theo rule duyệt, transaction khi cần | Success/failure/rollback tests | 60–90 phút / P1 |
| `CAFE-PAYMENT-BE-003` | Hoàn tất order sau thanh toán | PAYMENT-BE-002 | Order completion đồng bộ | Transaction/state tests | 30–60 phút / P1 |
| `CAFE-PAYMENT-BE-004` | Cập nhật trạng thái bàn sau checkout | PAYMENT-BE-003 | Table release theo rule duyệt | Payment/order/table consistency tests | 30–60 phút / P1 |
| `CAFE-PAYMENT-FE-001` | UI checkout và kết quả thanh toán | PAYMENT-BE-004 | Staff Blade flow | Browser/accessibility/build check | 60–90 phút / P1 |
| `CAFE-PAYMENT-TEST-001` | Kiểm tra payment workflow | PAYMENT-FE-001 | Feature/transaction/manual checklist | Payment suite đạt | 60–90 phút / P1 |

Deferred/TBD: hóa đơn in/PDF, cổng thanh toán ngoài và các trường hợp thanh toán đặc biệt.

### Phase 8 – Reservation

Mục tiêu: hỗ trợ đặt bàn sau khi Table và auth foundation ổn định theo lifecycle, khoảng thời gian, nullability và quy tắc overlap/capacity đã duyệt. Phí hủy hoặc tiền cọc vẫn ngoài baseline và cần phê duyệt riêng.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-RES-DOC-001` | Chi tiết hóa lifecycle và availability rules | TABLE-BE-001 | Bám lifecycle, time range, nullable assignment, capacity/overlap đã Frozen; không đổi schema | Requirement approval | 60–90 phút / P1 |
| `CAFE-RES-BE-001` | Tạo/cập nhật reservation nội bộ | RES-DOC-001, AUTH-TEST-001 | Staff/Admin operation theo quyền duyệt | Feature/validation/auth tests | 60–90 phút / P1 |
| `CAFE-RES-BE-002` | Kiểm tra table availability | RES-BE-001 | Conflict validation theo rule duyệt | Boundary/conflict tests | 60–90 phút / P1 |
| `CAFE-RES-BE-003` | Chuyển trạng thái reservation | RES-DOC-001, RES-BE-001 | Lifecycle operation | Transition tests | 30–60 phút / P1 |
| `CAFE-RES-FE-001` | Staff reservation view | RES-BE-003 | Internal Blade list/form | Browser/build/responsive check | 60–90 phút / P1 |
| `CAFE-RES-BE-004` | Public reservation submission | RES-BE-002 | Unauthenticated Customer flow, không Customer account | Validation/security/feature tests | 60–90 phút / P2 |
| `CAFE-RES-FE-002` | Public reservation form | RES-BE-004, FE-001 | Responsive accessible Blade form | Browser/keyboard/build check | 60–90 phút / P2 |

### Phase 9 – Promotion

Mục tiêu: chỉ triển khai promotion sau khi eligibility và discount rules được phê duyệt.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-PROMO-DOC-001` | Chốt promotion eligibility/discount rules | ORDER-TEST-001 | Rule đơn giản được duyệt; không voucher engine phức tạp | Requirement approval | 60–90 phút / P2 |
| `CAFE-PROMO-BE-001` | CRUD Promotion backend | PROMO-DOC-001, AUTH-TEST-001 | Admin management | Feature/validation/auth tests | 60–90 phút / P2 |
| `CAFE-PROMO-FE-001` | UI quản lý Promotion | PROMO-BE-001 | Admin Blade pages | Browser/build/responsive check | 60–90 phút / P2 |
| `CAFE-PROMO-BE-002` | Kiểm tra eligibility | PROMO-BE-001 | Rule đã duyệt | Eligible/ineligible matrix tests | 60–90 phút / P2 |
| `CAFE-PROMO-BE-003` | Áp dụng và tính lại discount | PROMO-BE-002, ORDER-BE-005 | Order recalculation theo rule duyệt | Calculation/regression tests | 60–90 phút / P2 |
| `CAFE-PROMO-TEST-001` | Kiểm tra promotion workflow | PROMO-BE-003 | Test validation, dates, eligibility, calculation | Promotion suite đạt | 60–90 phút / P2 |

Usage tracking nâng cao và voucher engine là Deferred/TBD.

### Phase 10 – Inventory

Mục tiêu: hỗ trợ ingredient, recipe, movement và consumption; không mở rộng sang supplier/procurement/ERP.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-INV-DOC-001` | Chi tiết hóa recipe/movement/consumption rules | MENU-BE-002, ORDER-TEST-001 | Bám recipe, four movement types, pending-to-preparing deduction và low-stock baseline đã Frozen | Requirement approval | 60–90 phút / P2 |
| `CAFE-INV-BE-001` | CRUD Ingredient backend | INV-DOC-001 | Admin ingredient management | Feature/validation tests | 60–90 phút / P2 |
| `CAFE-INV-FE-001` | UI Ingredient | INV-BE-001 | Admin Blade pages | Browser/build/responsive check | 60–90 phút / P2 |
| `CAFE-INV-BE-002` | Quản lý Product Recipe | INV-BE-001, MENU-BE-002 | Recipe relation theo rule duyệt | Relation/quantity validation tests | 60–90 phút / P2 |
| `CAFE-INV-BE-003` | Ghi nhận stock adjustment/movement | INV-BE-001 | Movement history theo rule duyệt | Audit/validation/transaction tests | 60–90 phút / P2 |
| `CAFE-INV-BE-004` | Ghi nhận consumption từ operation | INV-BE-002, INV-BE-003, ORDER-TEST-001 | Consumption trigger đã duyệt | Success/rollback/idempotency tests | 60–90 phút / P2 |
| `CAFE-INV-TEST-001` | Kiểm tra inventory workflow | INV-BE-004 | Ingredient/recipe/movement/consumption tests | Inventory suite đạt | 60–90 phút / P2 |

Low-stock indication chỉ được tạo task khi requirement cụ thể được duyệt.

### Phase 11 – Dashboard and Reporting

Mục tiêu: xây dashboard sau khi Order/Payment đã tạo dữ liệu tin cậy. Chỉ số, kỳ báo cáo và chart cụ thể phải được duyệt trước.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-DASH-DOC-001` | Chốt metric và reporting period | PAYMENT-TEST-001 | Revenue/order/product/operation metrics trong requirements | Requirement and data-source review | 60–90 phút / P2 |
| `CAFE-DASH-BE-001` | Revenue và order-count metrics | DASH-DOC-001 | Aggregation backend theo định nghĩa duyệt | Fixture/query/result tests | 60–90 phút / P2 |
| `CAFE-DASH-BE-002` | Best-selling product indicators | DASH-DOC-001 | Product sales aggregation | Fixture/query/result tests | 30–60 phút / P2 |
| `CAFE-DASH-BE-003` | Filter theo kỳ được duyệt | DASH-BE-001, DASH-BE-002 | Date/period filter | Boundary/time-period tests | 30–60 phút / P2 |
| `CAFE-DASH-FE-001` | Dashboard summary UI | DASH-BE-003 | Admin metrics layout theo frontend standard | Browser/build/responsive check | 60–90 phút / P2 |
| `CAFE-DASH-FE-002` | Chart visualization | DASH-FE-001 | Chart chỉ khi thư viện/cách dùng được phê duyệt | Data/accessibility/build check | 60–90 phút / P2 |
| `CAFE-DASH-TEST-001` | Kiểm tra report/dashboard | DASH-FE-002 | Metric correctness và manual checklist | Dashboard suite đạt | 60–90 phút / P2 |

### Phase 12 – Public Website / UX

Mục tiêu: hoàn thiện website công khai theo `frontend.md` sau khi Menu/Reservation cung cấp dữ liệu thật.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-PUBLIC-FE-001` | Refine home page với nội dung thật | MENU-BE-002, FE-001 | Public home, không redesign design system | Desktop/tablet/mobile/build check | 60–90 phút / P2 |
| `CAFE-PUBLIC-BE-001` | Cung cấp public menu data | MENU-BE-003 | Read-only public Menu, không Customer auth | Feature/query tests | 30–60 phút / P1 |
| `CAFE-PUBLIC-FE-002` | Public menu page | PUBLIC-BE-001 | Responsive Blade menu | Browser/accessibility/build check | 60–90 phút / P1 |
| `CAFE-PUBLIC-FE-003` | Menu filter/search | PUBLIC-FE-002 | Chỉ filter được requirement/UI task duyệt | Query/browser/build check | 30–60 phút / P2 |
| `CAFE-PUBLIC-FE-004` | About/contact content | FE-001 | Public café information trong scope | Content/responsive/accessibility review | 30–60 phút / P2 |
| `CAFE-PUBLIC-FE-005` | Reservation CTA integration | RES-FE-002, PUBLIC-FE-001 | Link/CTA tới reservation flow | Navigation/browser/build check | 30–60 phút / P2 |
| `CAFE-PUBLIC-TEST-001` | Public responsive/UX review | PUBLIC-FE-002…005 | 1440/768/375, keyboard, no overflow | Screenshot/manual/build checklist đạt | 60–90 phút / P2 |

Product detail riêng và QR menu chưa được `requirements.md` phê duyệt, vì vậy chưa tạo task triển khai; QR menu là candidate P3 Deferred.

### Phase 13 – Testing and Quality

Mục tiêu: bổ sung kiểm tra xuyên module sau các test nhỏ của từng task, không thay thế việc test liên tục ở các phase trước.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-TEST-001` | Audit feature-test coverage | Core phases accepted | Coverage matrix theo requirement, không refactor ngoài scope | Engineer review và gap list | 30–60 phút / P2 |
| `CAFE-TEST-002` | Bổ sung validation/authorization regression | TEST-001 | Missing tests theo gap list đã duyệt | Targeted suite đạt | 60–90 phút / P2 |
| `CAFE-TEST-003` | Order/Payment regression | ORDER-TEST-001, PAYMENT-TEST-001 | Cross-workflow tests | Suite và transaction cases đạt | 60–90 phút / P1 |
| `CAFE-TEST-004` | Reservation/Promotion/Inventory regression | Respective phase tests | Cross-domain cases đã duyệt | Targeted suite đạt | 60–90 phút / P2 |
| `CAFE-TEST-005` | Full Laravel test/build check | TEST-002…004 | Chỉ sửa lỗi được tách thành FIX task | `php artisan test`, `npm run build` đạt | 30–60 phút / P1 |
| `CAFE-TEST-006` | Browser/manual responsive checklist | TEST-005 | Key Admin/Staff/Public flows và viewports | Signed checklist/screenshots | 60–90 phút / P2 |

### Phase 14 – Finalization and Delivery

Mục tiêu: làm sạch, xác minh khả năng thiết lập lại và chuẩn bị tài liệu/bàn giao. Không tự thêm CI/CD hoặc tính năng mới.

| ID | Tên / mục tiêu | Dependency | Scope và output | Kiểm tra hoàn thành | Ước lượng / mức |
| --- | --- | --- | --- | --- | --- |
| `CAFE-DELIVERY-REFACTOR-001` | Audit dead/temporary code | TEST-006 | Chỉ lập danh sách; sửa lớn phải tách task | Static/diff review | 30–60 phút / P2 |
| `CAFE-DELIVERY-DOC-001` | Hoàn thiện `.env.example` và setup README | TEST-005 | Setup docs/config example, không secret | Fresh setup checklist | 60–90 phút / P1 |
| `CAFE-DELIVERY-TEST-001` | Xác minh database reset/seed | DELIVERY-DOC-001 | Reset/seed validation | Fresh database test đạt | 30–60 phút / P1 |
| `CAFE-DELIVERY-DOC-002` | Cập nhật ERD cuối | DB accepted, core schema stable | Tài liệu ERD khớp migrations | Schema/ERD review | 60–90 phút / P2 |
| `CAFE-DELIVERY-DOC-003` | Tạo Use Case diagrams | Requirements stable | Chỉ actor/domain đã duyệt | Requirements traceability review | 60–90 phút / P2 |
| `CAFE-DELIVERY-DOC-004` | Tạo Sequence diagrams cốt lõi | Core workflows stable | Order/Payment và flow được chọn đã duyệt | Architecture/workflow review | 60–90 phút / P2 |
| `CAFE-DELIVERY-DOC-005` | Chuẩn bị screenshots và final report | TEST-006 | Evidence, result, limitations | Engineer completeness review | 60–90 phút / P2 |
| `CAFE-DELIVERY-DOC-006` | Chuẩn bị presentation slides | DELIVERY-DOC-005 | Nội dung đồ án từ tài liệu đã duyệt | Presentation review | 60–90 phút / P2 |
| `CAFE-DELIVERY-001` | Chuẩn bị GitHub repository | DELIVERY-DOC-001…006 | Ignore/secrets/history/readiness; không publish nếu chưa được phép | Secret scan/status/setup review | 60–90 phút / P1 |

CI/CD chỉ được thêm bằng task P3 riêng khi có phê duyệt. Mọi cleanup phát hiện từ audit phải thành FIX/REFACTOR task nhỏ 30–90 phút, không sửa hàng loạt trong audit task.

## 8. Dependency Overview

```text
Foundation documentation
  -> Approved ERD / dependency graph
  -> Laravel migrations
  -> Base + Custom Models
  -> Seed data
  -> Authentication + authorization
  -> Table + Menu master data
  -> Order / POS core
  -> Payment
  -> Promotion / Inventory integrations (after their rules are approved)
  -> Dashboard (after reliable Order/Payment data exists)
  -> Public data-driven UX
  -> Cross-module quality checks
  -> Finalization and delivery
```

Reservation phụ thuộc Table và Authentication nhưng có thể triển khai song song với Order sau khi rule đặt bàn được duyệt. Public menu phụ thuộc Menu; public reservation phụ thuộc Reservation. Optional P3 không nằm trên critical path.

Các dependency cứng quan trọng:

- Database design → Migration → Model generation.
- Authentication → Admin/Staff protected modules.
- Table + Menu → Order.
- Order → Payment.
- Order + Payment data → Dashboard.
- Requirement approval → mọi task đang Deferred/TBD.

## 9. Definition of Ready

Task chỉ **READY** khi:

- Requirement đã được hiểu và không mâu thuẫn.
- Dependency bắt buộc đã hoàn thành và được chấp nhận.
- Scope và Out of Scope rõ ràng.
- Quy tắc kiến trúc liên quan đã được xác định.
- Acceptance Criteria có thể kiểm tra.
- Test Plan cụ thể.
- Ước lượng nằm trong 30–90 phút; nếu không thì đã được chia nhỏ.
- Source context cần đọc đã được chỉ rõ.
- Không còn TBD ảnh hưởng trực tiếp đến cách triển khai.

## 10. Definition of Done

Task chỉ **DONE** khi:

- Thay đổi đúng scope và không mở rộng requirements.
- Agent output/diff đã được engineer review và hiểu rõ.
- Từng Acceptance Criteria đạt.
- Test, build và checklist bắt buộc đạt.
- File thay đổi, rủi ro và blocker đã được báo cáo.
- Không còn thay đổi không liên quan do task tạo ra.
- Engineer phê duyệt kết quả.
- Commit chỉ được thực hiện hoặc đề xuất sau review thành công.

## 11. Change Management

- Tính năng hoặc thay đổi hành vi mới phải được duyệt và cập nhật `requirements.md` trước khi tạo task triển khai.
- Thay đổi cấu trúc kỹ thuật phải cập nhật hoặc có quyết định phù hợp với `architecture.md` trước khi lập task.
- Thay đổi visual language phải được duyệt trong `frontend.md`.
- Thay đổi thứ tự, dependency, trạng thái hoặc ID task phải được cập nhật tại `plan.md`; ID đã phát hành không được tái sử dụng.
- Task phát sinh phải có trace về requirement và phase tương ứng.
- Nếu prompt mâu thuẫn với tài liệu thẩm quyền, dừng task, báo cáo mâu thuẫn và yêu cầu phê duyệt.
- Không tự động thực thi task kế tiếp sau khi cập nhật kế hoạch.

## 12. Current Next Tasks

1. **Engineer review CAFE-DB-001B.** Xác nhận `database-design.md` đạt Frozen và không có schema-affecting conflict.
2. **`CAFE-DB-RE-AUDIT-001`.** Re-audit DB-001/002/003 theo Frozen Design, chỉ sau khi CAFE-DB-001B được chấp nhận.
3. **`CAFE-DB-CORRECTION-001` nếu re-audit xác nhận mismatch.** Không tự sửa migration trong task tài liệu.

`CAFE-DB-004` vẫn BLOCKED cho đến khi re-audit và correction cần thiết đều PASS; không tự động thực thi task kế tiếp.