# Report Management Standard

## 1. Purpose

`reports/` lưu evidence và kết quả của task đã được thực thi. Report ghi lại source/context đã đọc, thay đổi, test/build, Acceptance Criteria, risk, blocker và engineer review. Report không phải prompt, không tạo requirement mới và không thay thế source diff hoặc test evidence.

Không lưu task report trong project root hoặc các source folder như `app/`, `resources/` và `database/`.

## 2. Directory Structure

```text
reports/
├── README.md
├── task-reports/
└── weekly/
```

- `task-reports/`: report theo Task ID sau mỗi lần thực thi.
- `weekly/`: tổng hợp tiến độ theo tuần.
- `.gitkeep`: chỉ giữ empty directory trong Git, không phải report.

Evidence bổ sung như screenshot, log đã làm sạch hoặc checklist có thể được lưu khi cần dưới `reports/task-reports/evidence/{TASK-ID}/`. Chỉ tạo folder evidence khi có evidence thật; không lưu secret, dữ liệu nhạy cảm, dependency artifact hoặc file quá lớn không cần thiết.

## 3. Task Reports

Mỗi lần thực thi task phải tạo/cập nhật report tương ứng sau khi implementation và kiểm tra hoàn tất. Report phải phản ánh đúng prompt revision đã chạy và không được tuyên bố SUCCESS khi Acceptance Criteria bắt buộc thất bại hoặc test chưa chạy.

Report phải phân biệt:

- Thay đổi do task hiện tại tạo ra.
- Pre-existing worktree changes.
- Out-of-scope findings không được sửa.
- Unverified items và lý do.

## 4. Weekly Reports

Weekly report tổng hợp:

- Tasks đã hoàn thành.
- Tasks đang thực hiện.
- Thay đổi đáng kể.
- Tests/builds đã chạy.
- Blockers và risks.
- Kế hoạch tuần kế tiếp dựa trên `plan.md`.

Weekly report không thay thế task report chi tiết. Nếu chương trình học/thực tập có số tuần chính thức, dùng số đó nhất quán.

## 5. Task Report Naming Convention

Report đầu tiên:

```text
{TASK-ID}__report.md
```

Nếu cùng task được thực thi lại và cần giữ lịch sử, dùng revision thống nhất:

```text
{TASK-ID}__report__r02.md
{TASK-ID}__report__r03.md
```

Task ID viết hoa và MUST khớp task/prompt. Revision report phải liên kết đúng prompt revision hoặc ghi rõ lý do rerun. Không dùng `report1.md`, `output.md`, `final-report.md`, `latest.md` hoặc tên không có Task ID.

## 6. Weekly Report Naming Convention

```text
week-{NN}__YYYY-MM-DD_to_YYYY-MM-DD.md
```

Ví dụ:

```text
week-01__2026-08-10_to_2026-08-16.md
```

`NN` có hai chữ số; ngày dùng ISO `YYYY-MM-DD`; khoảng thời gian phải liên tục và khớp nội dung report.

## 7. Required Task Report Structure

Task report tối thiểu gồm:

```text
# Task Report

## Task Information
- Task ID
- Task Name
- Status: SUCCESS / PARTIAL / BLOCKED
- Date
- Prompt Path / Revision

## Summary

## Source / Context Reviewed

## Files Created

## Files Modified

## Files Deleted

## Implementation Result

## Tests / Checks Executed

## Test Results

## Acceptance Criteria Result
- PASS / FAIL / UNVERIFIED per criterion

## Risks

## Blockers

## Assumptions

## Out-of-Scope Findings

## Unverified Items

## Engineer Review

## Next Recommended Task
```

Mục không có nội dung ghi `None`. Không được bỏ failure/risk để report trông thành công. “Next Recommended Task” chỉ là đề xuất; không tự động triển khai.

## 8. Relationship with Task and Prompt

Traceability bắt buộc:

```text
requirements.md
  -> plan.md task hoặc engineer-approved Task ID
  -> prompts/<phase>/{TASK-ID}__{name}.xml
  -> source changes
  -> tests/build/checklists
  -> reports/task-reports/{TASK-ID}__report.md
  -> engineer review
  -> commit
```

Task report phải ghi Task ID và prompt path/revision. Commit message không cần nằm trong filename nhưng nên tham chiếu đúng task/domain. Không tạo report nếu chưa có kết quả thật chỉ để thể hiện task đã hoàn thành.

## 9. Historical Integrity

- Report là project evidence; không xóa hoặc ghi đè lịch sử im lặng.
- Rerun có kết quả material khác phải tạo `__r02`, `__r03`, ... thay vì sửa report đã chấp nhận.
- Không recycle Task ID hoặc đổi Task ID trong report cũ.
- Không rename tùy tiện làm mất liên kết tới prompt/commit.
- Nếu report sai, tạo correction/revision có trace thay vì che giấu bản cũ.
- Không tạo fake successful report, fake test evidence hoặc fake screenshot.
- Secret và dữ liệu nhạy cảm phải được loại khỏi report/evidence.

## 10. Engineer Review Responsibility

Engineer/team phải:

- Đối chiếu report với prompt, Git diff và source thật.
- Xác nhận test/build evidence và từng Acceptance Criterion.
- Review risk, blocker, assumption, out-of-scope và unverified items.
- Xác nhận status cuối cùng.
- Quyết định commit, merge hoặc yêu cầu revision.
- Chỉ chọn task kế tiếp sau khi task hiện tại được chấp nhận.

AI report hỗ trợ review nhưng không thay thế trách nhiệm kỹ thuật hoặc phê duyệt cuối cùng của engineer.
