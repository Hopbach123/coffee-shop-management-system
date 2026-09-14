# Prompt Management Standard

## 1. Purpose

`prompts/` lưu các chỉ dẫn đã được chuẩn bị cho từng task triển khai của Coffee Shop Management System. Một prompt phải mô tả một task nhỏ, có phạm vi rõ, thông thường trong 30–90 phút và có thể review/test độc lập.

Prompt là **instruction**, không phải kết quả triển khai và không thay thế `requirements.md`, `architecture.md`, `plan.md` hoặc `AGENTS.md`. Không lưu task prompt tại project root, `app/`, `resources/` hoặc `database/`.

## 2. Relationship with plan.md and AGENTS.md

- `requirements.md` xác định business behavior được duyệt.
- `architecture.md` xác định kiến trúc kỹ thuật.
- `frontend.md` xác định design rules cho frontend task.
- `plan.md` xác định phase, task, dependency và thứ tự triển khai.
- `AGENTS.md` xác định kỷ luật bắt buộc khi AI Agent thực thi.
- Prompt chuyển một task đã duyệt thành chỉ dẫn triển khai cụ thể; prompt không được tạo requirement hoặc architecture song song.

Mỗi prompt MUST ánh xạ tới một Task ID trong `plan.md` hoặc một task được engineer phê duyệt rõ. Nếu prompt mâu thuẫn với tài liệu thẩm quyền, phải dừng và yêu cầu engineer giải quyết thay vì tự chọn.

## 3. Directory Structure

```text
prompts/
├── README.md
├── phase-00-foundation/
├── phase-01-database/
├── phase-02-models/
├── phase-03-seeders/
├── phase-04-auth/
├── phase-05-master-data/
├── phase-06-order/
├── phase-07-payment/
├── phase-08-reservation/
├── phase-09-promotion/
├── phase-10-inventory/
├── phase-11-dashboard/
├── phase-12-public-ui/
├── phase-13-testing/
└── phase-14-finalization/
```

Prompt phải nằm trong phase phù hợp. `.gitkeep` chỉ giữ thư mục rỗng trong Git và không phải prompt. Khi có prompt thật, team có thể giữ hoặc xóa `.gitkeep` theo convention mà không ảnh hưởng lịch sử task.

## 4. Phase Mapping

| Thư mục | Phase chính thức trong `plan.md` |
| --- | --- |
| `phase-00-foundation/` | Phase 0 – Project Foundation |
| `phase-01-database/` | Phase 1 – Database Design and Migration |
| `phase-02-models/` | Phase 2 – Eloquent Model Foundation |
| `phase-03-seeders/` | Phase 3 – Seeder and Development Data |
| `phase-04-auth/` | Phase 4 – Authentication and Authorization |
| `phase-05-master-data/` | Phase 5 – Master Data Management |
| `phase-06-order/` | Phase 6 – Order / POS Core |
| `phase-07-payment/` | Phase 7 – Payment and Invoice |
| `phase-08-reservation/` | Phase 8 – Reservation |
| `phase-09-promotion/` | Phase 9 – Promotion |
| `phase-10-inventory/` | Phase 10 – Inventory |
| `phase-11-dashboard/` | Phase 11 – Dashboard and Reporting |
| `phase-12-public-ui/` | Phase 12 – Public Website / UX |
| `phase-13-testing/` | Phase 13 – Testing and Quality |
| `phase-14-finalization/` | Phase 14 – Finalization and Delivery |

Nếu `plan.md` được phê duyệt thay đổi phase, cập nhật bảng này và đường dẫn cho prompt mới. Không tự duy trì một hệ thống phase thứ hai và không tùy tiện đổi đường dẫn prompt lịch sử đã thực thi.

## 5. Task ID Convention

- Dùng Task ID ổn định từ `plan.md` hoặc task được engineer phê duyệt.
- Tuân thủ convention `CAFE-{DOMAIN}-{TYPE}-{NNN}` và các short-form đã được `plan.md` công nhận.
- Task ID phải viết hoa, duy nhất và không được tái sử dụng.
- Task bị hủy vẫn giữ ID đã cấp; không dùng ID đó cho công việc khác.
- Task ID trong filename, `<meta><taskId>` và task report MUST giống nhau.

## 6. Prompt Filename Convention

Convention chính thức:

```text
{TASK-ID}__{short-kebab-name}.xml
```

Quy tắc:

1. Task ID đứng đầu và viết hoa.
2. Dùng đúng hai dấu gạch dưới `__` sau Task ID.
3. Description viết lowercase kebab-case, không khoảng trắng và không ký tự tiếng Việt có dấu.
4. Dùng extension `.xml` cho Agent task prompt.
5. Filename phải ổn định sau khi task được thực thi.
6. Không dùng tên mơ hồ như `prompt.xml`, `final.xml`, `latest.xml`, `new.xml` hoặc `task-4.xml`.

Ví dụ:

```text
CAFE-DB-001__finalize-erd.xml
CAFE-AUTH-BE-001__auth-foundation.xml
CAFE-ORDER-BE-003__update-order-quantity.xml
```

## 7. Prompt Revision Convention

Khi prompt còn **DRAFT** và chưa được thực thi, có thể cập nhật cùng file trong quá trình review.

Sau khi prompt đã **APPROVED/EXECUTED**, một thay đổi material cần chạy lại phải tạo revision mới:

```text
{TASK-ID}__{short-kebab-name}__r02.xml
{TASK-ID}__{short-kebab-name}__r03.xml
```

Revision tăng tuần tự từ `r02`. Không dùng `final`, `final2`, `newest`, `latest` hoặc `fixed-final`. Revision mới không xóa hoặc âm thầm ghi đè prompt lịch sử; report phải ghi revision đã dùng. Prompt cũ có thể được đánh dấu SUPERSEDED nhưng vẫn được giữ làm evidence.

## 8. Prompt Lifecycle

| Trạng thái | Ý nghĩa |
| --- | --- |
| `DRAFT` | Prompt đang được chuẩn bị hoặc review; chưa được phép triển khai |
| `APPROVED` | Engineer/team đã chấp nhận scope và cho phép thực thi |
| `EXECUTED` | Agent đã dùng prompt để triển khai; phải có report/evidence tương ứng |
| `SUPERSEDED` | Prompt được thay thế bởi revision đã duyệt mới hơn nhưng vẫn giữ lịch sử |

Không bắt buộc encode trạng thái vào filename. Trạng thái nên nằm trong metadata/task tracking/report để tránh rename file liên tục.

## 9. Required Prompt Structure

Prompt XML cho technical task thông thường phải có:

```text
prompt
├── meta
│   ├── taskId
│   ├── taskName
│   ├── projectName
│   ├── taskType
│   ├── priority
│   ├── estimatedTime
│   └── dependencies
├── goal
├── requirementReference hoặc frsReference
├── planReference
├── taskUnit
├── context
├── sourceReading
├── scope
├── hardRules
├── tasks
├── constraints
├── acceptanceCriteria
├── testPlan
└── outputRequired
```

`<meta><taskId>` MUST khớp Task ID trong filename. Prompt frontend phải tham chiếu và yêu cầu đọc `frontend.md`.

Mọi implementation prompt phải yêu cầu Agent đọc `AGENTS.md`, inspect source liên quan, giữ scope, review diff, chạy test/build phù hợp và báo changed files, risks, blockers, assumptions cùng unverified items.

## 10. Prompt Creation Workflow

```text
Approved requirement
  -> plan.md task hoặc engineer-approved task
  -> xác nhận Definition of Ready
  -> tạo DRAFT prompt trong đúng phase
  -> engineer review prompt
  -> APPROVED
  -> AI Agent implementation theo AGENTS.md
  -> engineer review + test/build
  -> task report
  -> commit khi được chấp nhận
  -> chỉ sau đó mới xem xét task kế tiếp
```

Prompt không đạt Definition of Ready, còn TBD ảnh hưởng trực tiếp hoặc vượt 90 phút phải được làm rõ/chia nhỏ trước khi approved.

## 11. Example

```text
Requirement: requirements.md
Task: CAFE-DB-002 – Create foundation migrations
Prompt: prompts/phase-01-database/CAFE-DB-002__foundation-migrations.xml
Implementation: database/migrations/... theo thiết kế đã duyệt
Checks: migrate / rollback / fresh trong environment an toàn
Report: reports/task-reports/CAFE-DB-002__report.md
Commit recommendation: feat(db): add foundation migrations
```

Ví dụ chỉ mô tả convention; README này không tạo hoặc phê duyệt prompt triển khai `CAFE-DB-002`.

## 12. Historical Integrity Rules

- Không xóa executed prompt nếu không có lý do được review.
- Không âm thầm ghi đè approved/executed prompt bằng instruction khác đáng kể.
- Không đổi hoặc tái sử dụng Task ID cũ.
- Không rename prompt lịch sử tùy tiện làm mất liên kết với report/commit.
- Giữ traceability giữa requirement, task, prompt revision, source changes, report và commit.
- Khi task bị hủy, giữ Task ID reserved và ghi trạng thái trong tracking/report thích hợp.
- Không tạo prompt giả chỉ để lấp đầy phase folder.

## 13. AI Agent Rules

AI Agent làm việc với `prompts/` MUST:

- Đọc `AGENTS.md` và toàn bộ prompt được giao.
- Xác minh Task ID, phase, dependency và status APPROVED trước implementation.
- Xác minh Task ID metadata khớp filename.
- Không sửa executed prompt history để hợp thức hóa source đã thay đổi.
- Không tự tạo hàng loạt future prompts.
- Không lưu report hoặc source implementation trong `prompts/`.
- Không tự động thực thi prompt/task kế tiếp.
- Báo conflict, missing requirement, risk và blocker trung thực.
