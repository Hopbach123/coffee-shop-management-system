# Coffee Shop – AI Agent Engineering Rules

## 1. Purpose

Tài liệu này quy định bắt buộc cách mọi AI Agent đọc, tạo hoặc sửa source trong Coffee Shop Management System. AI Agent là trợ lý triển khai, không phải chủ sở hữu phạm vi dự án. Khả năng cải thiện một phần của hệ thống không đồng nghĩa với quyền thay đổi phần đó.

AI MUST đọc trước khi viết, hiểu trước khi sửa, ở đúng phạm vi, kiểm chứng trước khi báo thành công và trình bày trung thực mọi điều chưa chắc chắn. Engineer/team giữ trách nhiệm cuối cùng đối với review, test, acceptance, commit và merge.

## 2. Authority and Document Priority

Thứ tự thẩm quyền:

1. Task/prompt hiện tại đã được phê duyệt xác định mục tiêu và phạm vi tức thời.
2. `requirements.md` xác định hành vi nghiệp vụ được phê duyệt.
3. `architecture.md` xác định kiến trúc kỹ thuật.
4. `frontend.md` xác định quy tắc thiết kế frontend khi task liên quan UI.
5. `plan.md` xác định phase, thứ tự, dependency, Definition of Ready và Definition of Done.
6. `AGENTS.md` xác định kỷ luật thực thi của AI Agent.

Task hiện tại không được tự ý ghi đè yêu cầu nghiệp vụ hoặc kiến trúc đã duyệt. Nếu các tài liệu mâu thuẫn và mâu thuẫn ảnh hưởng đáng kể đến triển khai, AI MUST dừng phần bị ảnh hưởng, báo cáo rõ xung đột và yêu cầu engineer giải quyết; AI MUST NOT tự chọn một cách im lặng.

## 3. Required Reading Before Coding

Trước khi sửa source, AI MUST:

1. Đọc đầy đủ task/prompt hiện tại và xác định goal, dependencies, scope, out-of-scope, constraints, Acceptance Criteria và Test Plan.
2. Đọc đầy đủ `AGENTS.md`, `architecture.md` và `requirements.md`.
3. Đọc `plan.md`, xác nhận task hiện tại, dependency và task sizing.
4. Đọc đầy đủ `frontend.md` nếu task chạm Blade, CSS, JavaScript, layout, component, public page, Admin UI hoặc Staff/POS UI.
5. Đọc source liên quan và các test hiện có trước khi chỉnh sửa.

Không được bắt đầu viết code chỉ từ tên task hoặc từ giả định về cấu trúc Laravel mặc định.

## 4. Standard AI Agent Workflow

Mọi implementation task MUST theo quy trình:

1. **Read task:** đọc toàn bộ prompt; trích xuất mục tiêu, phạm vi, cấm, dependency, criteria và test plan.
2. **Read context:** đọc các tài liệu thẩm quyền và source liên quan.
3. **Inspect before modify:** kiểm tra implementation hiện có trước khi tạo hoặc sửa code.
4. **Confirm scope internally:** xác định file dự kiến thay đổi; nếu thay đổi bắt buộc nằm ngoài scope, dừng và báo blocker/risk.
5. **Implement minimum change:** chỉ làm thay đổi tối thiểu cần thiết để đạt criteria.
6. **Self-review:** kiểm tra thay đổi ngoài phạm vi, duplicate logic, vi phạm kiến trúc, validation thiếu, assumption không an toàn, dependency ngoài ý muốn, debug/dead code và formatting ngoài phạm vi.
7. **Test/build:** chạy các kiểm tra phù hợp và an toàn.
8. **Review diff:** xem lại toàn bộ file thay đổi và Git diff/status khi có Git.
9. **Report:** báo trạng thái, file, hành vi, kiểm tra, criteria, risk, blocker, assumption và phần còn lại.
10. **Stop:** không tự động triển khai task kế tiếp.

Ví dụ inspect bắt buộc:

- Trước Controller: xem Controller tương tự và route/request liên quan.
- Trước Service: xem Service hiện có và xác nhận workflow đủ phức tạp.
- Trước Blade component: xem layout/component có thể tái sử dụng.
- Trước migration: xem migration hiện tại, FK và thứ tự phụ thuộc.
- Trước Model: xem Base/Custom Model strategy và relationship hiện có.

## 5. Scope Control

**Scope là ranh giới cứng.** AI chỉ được thay đổi file và hành vi cần thiết cho task được giao.

AI MUST NOT:

- Thêm tính năng ngoài yêu cầu.
- Sửa bug không liên quan.
- Refactor module không liên quan.
- Đổi tên class hoặc tái tổ chức folder ngoài task.
- Đổi kiến trúc vì một cách khác có vẻ tốt hơn.
- Cập nhật dependency hoặc UI không liên quan.
- Thực hiện tối ưu mang tính dự phòng.

Nếu phát hiện vấn đề ngoài phạm vi, ghi tại **Out-of-Scope Findings** và không sửa nếu chưa được yêu cầu. Khi một thay đổi ngoài scope là điều kiện bắt buộc để hoàn thành an toàn, AI MUST dừng phần đó và báo blocker thay vì âm thầm mở rộng task.

## 6. Refactoring Policy

Refactor không mặc nhiên được phép. Cleanup cục bộ rất nhỏ chỉ được thực hiện khi đồng thời:

- Trực tiếp cần cho task hiện tại.
- Giữ nguyên hành vi ngoài phần được yêu cầu.
- Giới hạn trong khu vực nhỏ.
- Dễ review và có thể kiểm tra.

Nếu task không yêu cầu rõ, AI MUST NOT thực hiện project-wide refactor, architecture rewrite, folder restructuring, naming overhaul, mass formatting, abstraction lớn hoặc generic “code cleanup”.

Ví dụ đúng: thêm product search chỉ trong các file cần cho feature. Ví dụ sai: đồng thời viết lại Service, đổi tên Controllers, chuyển folder và đổi nhiều components.

## 7. Dependency Policy

Ưu tiên khả năng và dependency hiện có. AI MUST NOT tự chạy `composer require`, `npm install`, `npm install <package>`, thay đổi version hoặc sửa dependency manifest/lockfile nếu task chưa cho phép rõ và engineer chưa phê duyệt.

Trước khi đề xuất/thêm dependency, phải xác định:

1. Vì sao khả năng hiện có không đủ.
2. Package nào cần dùng và vì sao phù hợp.
3. File nào sẽ thay đổi.
4. Rủi ro bảo mật, tương thích, bundle/build và bảo trì.
5. Engineer đã phê duyệt hay chưa.

Không được dùng dependency mới để né việc hiểu source hoặc yêu cầu hiện tại.

## 8. Architecture Rules

AI MUST tuân thủ `architecture.md`:

- Kiến trúc là Laravel MVC + Selective Service Layer.
- Controller phải mỏng và tập trung vào HTTP coordination.
- Simple CRUD có thể dùng Controller → Eloquent Model.
- Workflow phức tạp có thể dùng Controller → Service → Models.
- FormRequest xử lý request validation/authorization khi phù hợp.
- Middleware xử lý authentication, authorization và request-level access.
- Route không chứa business logic.
- Model đại diện dữ liệu, relationships, casts và query behavior phù hợp.
- Blade không truy vấn dữ liệu và không chứa business logic phức tạp.
- Migration là source of truth duy nhất của database schema.
- Repository Pattern bị cấm trừ khi `architecture.md` được cập nhật và phê duyệt.
- Không đặt HTTP response concern trong Model.
- Không thêm abstraction để dự phòng.

## 9. Database and Migration Rules

Khi task ảnh hưởng database, AI MUST inspect migrations hiện tại, dependency order, foreign keys, naming conventions và thiết kế dữ liệu đã duyệt.

- Không sửa database local/production bằng thao tác thủ công để thay thế migration.
- Schema change phải nằm trong migration có thể review và có khả năng rollback hợp lý.
- Không tự đặt table, column, enum, status hoặc business constraint chưa được duyệt.
- Không sửa migration đã chia sẻ theo cách phá lịch sử nếu task không cho phép.
- Không chạy lệnh destructive một cách mù quáng.
- Phải báo cáo nguy cơ mất dữ liệu, rollback limitation và environment impact.

Tùy scope, kiểm tra có thể gồm `php artisan migrate:status`, `php artisan migrate`, `php artisan migrate:rollback` hoặc `php artisan migrate:fresh`. `migrate:fresh` chỉ được dùng trên database an toàn dành cho development/test sau khi đã xác minh target; không dùng trên dữ liệu cần giữ.

## 10. Model Rules

Tôn trọng chiến lược model:

- `app/Models/Base/` là generated layer có thể bị regenerate.
- `app/Models/` là custom application layer.

AI MUST NOT thêm business logic duy trì thủ công vào Base Model có thể regenerate. Trước khi sửa model/generated configuration, phải đọc policy trong `architecture.md`, inspect generator strategy và xác định nguy cơ bị ghi đè. Workflow liên nhiều Models không được nhét vào một Model chỉ để tránh Service.

## 11. Controller Rules

Controller chỉ điều phối HTTP request/response. AI MUST tránh:

- Transaction nhiều Models trong Controller.
- Calculation block lớn.
- Validation trùng lặp hoặc validation array lớn khi FormRequest phù hợp.
- Business workflow dài.
- Persistence logic không liên quan đến action.

Workflow phức tạp phải được tổ chức theo `architecture.md`; không tạo logic trong Controller chỉ vì triển khai nhanh hơn.

## 12. Service Rules

Không tạo Service chỉ vì một module hoặc Model tồn tại. Service được dùng khi business logic đủ phức tạp, đặc biệt khi điều phối nhiều Models, transaction, Order, Payment, Promotion, Reservation hoặc Inventory.

Simple CRUD không tự động cần Service. Service không được trở thành utility container chung, không lặp lại Eloquent CRUD và không thay thế FormRequest/Middleware/Controller responsibilities.

## 13. Frontend Rules

Nếu task ảnh hưởng frontend, AI MUST:

- Đọc đầy đủ `frontend.md` trước khi sửa.
- Inspect layouts, components và pages hiện có.
- Tái sử dụng design tokens và component phù hợp.
- Giữ visual language, responsive behavior và accessibility basics.
- Kiểm tra common widths khi layout thay đổi, tối thiểu theo viewports được task/frontend standard yêu cầu.
- Giữ JavaScript ở vai trò progressive enhancement; business rules thuộc backend.

AI MUST NOT thêm arbitrary style, redesign trang không liên quan, tạo component trùng, cài React/Vue, thêm UI framework thứ hai hoặc để default framework appearance phá design standard. Bootstrap chỉ được thêm/dùng theo approval và quy tắc trong `frontend.md`.

## 14. Security and Secret Handling

AI MUST NOT hardcode hoặc commit database password, API key, access token, private key, mail password hay secret khác. Dùng environment/config conventions; không commit `.env`.

Nếu phát hiện secret:

- Không lặp lại giá trị secret trong report hoặc output không cần thiết.
- Báo security risk và file/vị trí ở mức đủ để engineer xử lý.
- Không tự ý rotate/revoke secret nếu task không cấp quyền.
- Không đưa dữ liệu nhạy cảm vào test fixture, screenshot hoặc log.

## 15. Generated and Third-Party Files

AI MUST NOT sửa thủ công:

- `vendor/`
- `node_modules/`

Đây là dependency-generated directories. Không sửa source của third-party package để giải quyết application problem. Tránh sửa generated/build artifact như `public/build/` trừ khi project/task quy định rõ artifact đó được version control. Với Base Models hoặc file generated khác, phải tuân thủ strategy tương ứng và báo overwrite risk.

## 16. Git / Existing Work Safety

AI phải bảo vệ thay đổi hiện có của engineer:

- Inspect `git status` và `git diff` trước khi hoàn thành khi Git khả dụng.
- Tách rõ thay đổi do task tạo ra và thay đổi có sẵn từ trước.
- Không overwrite, discard hoặc “cleanup” thay đổi của người dùng nếu chưa có chỉ dẫn rõ.
- Không force push, rewrite history, reset unrelated changes, xóa user work hoặc mass format unrelated source.
- Không tự commit/push trừ khi task cấp quyền rõ; báo commit recommendation sau review.
- Nếu worktree dirty làm diff không thể phân biệt an toàn, báo risk/blocker trước khi sửa file chồng lấn.

## 17. Testing Policy

“Đã viết code” không đồng nghĩa “đã hoàn thành task”. AI MUST chạy các test/check phù hợp và an toàn với phạm vi.

Ví dụ Laravel/PHP: `php artisan test`, targeted test, `php artisan route:list`, `php artisan migrate:status`, hoặc migration check phù hợp. Ví dụ frontend: `npm run build`, browser interaction, accessibility/responsive checklist.

- Chỉ chạy destructive database check sau khi xác minh environment an toàn.
- Không tuyên bố test PASS nếu test chưa chạy, bị abort hoặc không quan sát được kết quả.
- Nếu không thể chạy test, report rõ test nào chưa chạy, lý do và risk còn lại.
- Failure ngoài phạm vi phải được ghi là pre-existing/out-of-scope; không refactor lan rộng để làm test xanh.

## 18. Build Policy

Khi task chạm source có build, AI MUST chạy build command phù hợp nếu có và an toàn. Với frontend hiện tại, kiểm tra thường gồm `npm run build`.

Build failure phải được điều tra trong phạm vi task. Nếu failure có sẵn từ trước hoặc sửa nó yêu cầu mở rộng scope, báo rõ failure/risk/blocker và không âm thầm thay đổi module khác. Build thành công không thay thế feature tests hoặc manual checks liên quan.

## 19. Acceptance Criteria Policy

- Đọc và ánh xạ từng Acceptance Criterion trước khi triển khai.
- Mỗi criterion phải được báo PASS hoặc FAIL với evidence/test tương ứng khi được định nghĩa.
- Không tự sửa wording để dễ đạt hơn.
- Criterion không kiểm chứng được phải ghi UNVERIFIED, không được mặc định PASS.
- Chỉ báo SUCCESS khi tất cả criterion bắt buộc đạt và không còn blocker ảnh hưởng outcome.
- Nếu một criterion thất bại, trạng thái phải là PARTIAL hoặc BLOCKED tùy ảnh hưởng.

## 20. Reporting Requirements

Mọi implementation report phải có cấu trúc tối thiểu:

```text
## Task Status
SUCCESS / PARTIAL / BLOCKED

## Task ID

## Summary

## Files Created

## Files Modified

## Files Deleted

## Implementation Details

## Tests / Checks Executed

## Test Results

## Acceptance Criteria
PASS / FAIL / UNVERIFIED per criterion

## Risks

## Blockers

## Assumptions

## Out-of-Scope Findings

## Unverified Items

## Engineer Review Required

## Recommended Next Task
Do not implement automatically.
```

Nếu một mục không có nội dung, ghi `None` thay vì bỏ qua. Files Deleted thường là `None` trừ khi task yêu cầu rõ. Report phải phân biệt thay đổi hiện tại với pre-existing worktree changes.

## 21. Risk Handling

AI MUST báo mọi risk có ý nghĩa, ví dụ:

- Data migration hoặc backward-compatibility risk.
- Requirement/business rule chưa rõ.
- Branch chưa test hoặc responsive uncertainty.
- Dependency incompatibility.
- Generated-file overwrite.
- Security hoặc possible concurrency concern.

Không che giấu uncertainty để report trông thành công. Với risk có thể kiểm tra an toàn trong scope, kiểm tra và báo evidence. Với risk cần authority hoặc scope mới, giữ nguyên và nêu hành động cần thiết.

## 22. Blocker Handling

Blocker là điều khiến task không thể hoàn thành an toàn/chính xác vì thiếu requirement, approval, file, environment, connection, dependency, secret/config hoặc vì architecture conflict.

Khi blocked, AI MUST:

1. Dừng phần không an toàn.
2. Giữ lại phần an toàn đã hoàn thành mà không che giấu trạng thái partial.
3. Mô tả chính xác blocker và evidence.
4. Nêu thông tin/quyền/environment cần để tiếp tục.
5. Không phát minh workaround làm thay đổi scope.

## 23. Assumption Handling

Giảm assumption đến mức thấp nhất. Assumption cần thiết phải được nêu rõ, không mâu thuẫn requirement và được ghi trong report.

Khi business behavior chưa biết, dùng TBD hoặc yêu cầu làm rõ. Không được biến assumption thành business rule lâu dài, enum, database constraint hoặc permission một cách im lặng.

## 24. Out-of-Scope Findings

Vấn đề không thuộc task phải được liệt kê trong `Out-of-Scope Findings` với ảnh hưởng và khuyến nghị ngắn. AI MUST NOT sửa vấn đề đó nếu chưa được yêu cầu.

Nếu finding trực tiếp ngăn task hiện tại hoàn thành, chuyển nó thành blocker. Nếu không ảnh hưởng outcome, tiếp tục task trong phạm vi và chỉ báo finding.

## 25. Forbidden AI Behaviors

AI bị cấm:

1. Code trước khi đọc task và source liên quan.
2. Bỏ qua `architecture.md`.
3. Bỏ qua `requirements.md`.
4. Bỏ qua `plan.md` khi chọn/thực hiện roadmap task.
5. Bỏ qua `frontend.md` trong UI task.
6. Âm thầm mở rộng scope.
7. Refactor code không liên quan.
8. Cài hoặc nâng dependency khi chưa được phê duyệt.
9. Sửa `vendor/`.
10. Sửa `node_modules/`.
11. Hardcode hoặc làm lộ secret.
12. Phát minh business rule.
13. Sửa database thủ công thay migration.
14. Đặt workflow phức tạp trong Controller.
15. Đặt business logic trong Blade.
16. Sao chép implementation khi code/component phù hợp đã tồn tại.
17. Tuyên bố test đã pass khi chưa chạy hoặc không có kết quả.
18. Che giấu failure, risk hoặc unverified item.
19. Tự động triển khai task kế tiếp.
20. Xóa, overwrite hoặc reset công việc không liên quan của engineer.
21. Mass format source không liên quan.
22. Đổi architecture theo sở thích cá nhân.
23. Báo task DONE/SUCCESS khi Acceptance Criteria bắt buộc thất bại.

## 26. Definition of Ready

Trước khi implementation bắt đầu, task phải có:

- Task ID và goal cụ thể.
- Requirement đã biết/được phê duyệt.
- Dependency đã xác định và hoàn thành khi bắt buộc.
- Scope và forbidden/out-of-scope rõ ràng.
- Expected output.
- Acceptance Criteria kiểm chứng được.
- Test Plan.
- Source context cần đọc.
- Ước lượng thông thường 30–90 phút.

Nếu thiếu thông tin quan trọng, AI phải báo trước khi thực hiện phần triển khai rủi ro. Task có TBD ảnh hưởng trực tiếp hoặc vượt quá 90 phút chưa được chia nhỏ là **NOT READY**.

## 27. Definition of Done

AI chỉ được báo SUCCESS khi:

- Requirement đã được hiểu.
- Tài liệu và source liên quan đã được đọc.
- Thay đổi ở đúng scope và đúng architecture.
- Không có dependency trái phép.
- Self-review và diff review đã hoàn thành.
- Test/build/check liên quan đã chạy và đạt, hoặc giới hạn kiểm chứng đã được phản ánh đúng trong trạng thái.
- Tất cả Acceptance Criteria bắt buộc đạt.
- Files, risks, blockers, assumptions và unverified items đã được báo cáo.
- Engineer có thể hiểu và review thay đổi.

AI output không thay thế engineer approval. Code generation hoặc build pass một mình không đủ để đạt DONE.

## 28. Engineer Responsibility

Engineer/team là bên chịu trách nhiệm cuối cùng cho:

- Xác nhận requirement và scope.
- Review prompt và source diff.
- Đánh giá architecture/security/data risk.
- Xác nhận test/build evidence.
- Chấp nhận hoặc từ chối Acceptance Criteria.
- Quyết định commit, merge, deploy hoặc release.

AI Agent phải cung cấp evidence trung thực và dễ review, nhưng không tự coi output của mình là phê duyệt cuối cùng.

## 29. Mandatory Final Checklist

Mọi coding Agent phải kiểm tra trước final response:

- [ ] Tôi đã đọc đầy đủ task hiện tại.
- [ ] Tôi đã đọc `AGENTS.md`.
- [ ] Tôi đã đọc `architecture.md`.
- [ ] Tôi đã đọc `requirements.md`.
- [ ] Tôi đã đọc `plan.md` khi liên quan.
- [ ] Tôi đã đọc `frontend.md` nếu frontend bị ảnh hưởng.
- [ ] Tôi đã inspect source liên quan trước khi code.
- [ ] Tôi đã ở đúng scope.
- [ ] Tôi không refactor ngoài yêu cầu.
- [ ] Tôi không thêm dependency trái phép.
- [ ] Tôi không sửa third-party/generated files không đúng quy tắc.
- [ ] Tôi đã self-review thay đổi.
- [ ] Tôi đã chạy test/check liên quan.
- [ ] Tôi đã chạy build liên quan khi áp dụng.
- [ ] Tôi đã review changed files/diff.
- [ ] Tôi đã liệt kê file created/modified/deleted.
- [ ] Tôi đã báo risks.
- [ ] Tôi đã báo blockers.
- [ ] Tôi đã báo assumptions.
- [ ] Tôi đã báo unverified items.
- [ ] Tôi không tự động triển khai task kế tiếp.
