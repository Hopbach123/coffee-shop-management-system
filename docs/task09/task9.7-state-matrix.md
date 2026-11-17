# Task9.7 — State Matrix trước triển khai

Ngày 20/10/2026. VERIFIED là hành vi source hiện có. Engineer đã duyệt toàn bộ bảng hiển thị bảo thủ, cửa sổ Reservation và GET/bfcache refresh qua phản hồi trong chat. Các phần Payment/release chưa có backend tiếp tục BLOCKED.

## Nguồn có thẩm quyền
- cafe_tables.status: available/occupied/maintenance. CafeTableManagementService có expected_status và các chuyển available→maintenance, maintenance→available, occupied→available. Chặn nếu có Order pending hoặc Reservation pending/confirmed/arrived còn end_at>now. Mở lại/giải phóng yêu cầu Area active/chưa xóa.
- OrderService::create: Area active, bàn available, không pending dine_in; sau khi transaction tạo thành công đổi occupied. VERIFIED.
- OrderService::cancel: chỉ pending và tất cả items pending; sau hủy, nếu bàn occupied/chưa xóa và không pending dine_in khác thì có thể đổi available. VERIFIED hành vi hiện tại; source này chưa kiểm tra Reservation/cleanup trong nhánh release. Task9.7 không sửa OrderService.
- OrderService::addItem cập nhật món/tổng, không tạo status bàn mới. VERIFIED.
- Payment: chỉ schema/model và đọc bản ghi, chưa có backend xác nhận/lifecycle/idempotency. pending/paid/failed/refunded là giá trị lưu, không phải quyền tự giải phóng bàn. BLOCKED cho trạng thái financial-derived/release.
- Reservation: pending/confirmed/arrived/completed/cancelled/no_show; ghi theo services hiện tại, không tự nhận/hủy khi refresh. Cửa sổ display hiện tại cần engineer duyệt riêng; điều kiện end>now của quản lý bàn bao gồm cả tương lai.
- Floor/detail/Area hiện đếm/hiển thị persisted status. Task9.5 đã duyệt candidate pending+dine_in và conflict nếu nhiều; đã có server recheck ở luồng gọi món.

## Matrix APPROVED (phản hồi engineer trong chat ngày 20/10/2026)
| Area / persisted / dữ liệu liên quan | Display chỉ đọc đề xuất | Writes |
| --- | --- | --- |
| Area inactive/xóa/missing hoặc persisted lạ | Chưa xác định | Không |
| Nhiều pending dine_in | Cần kiểm tra | Không |
| available nhưng có pending dine_in | Cần kiểm tra | Không |
| occupied nhưng không pending dine_in | Cần kiểm tra | Không |
| maintenance có Order hoặc Reservation hiện tại | Cần kiểm tra | Không |
| Order và Reservation hiện tại cùng tồn tại | Cần kiểm tra | Không |
| available, không Order, có Reservation hiện tại | Đã đặt (display only) | Không |
| available, không Order/lịch hiện tại | Trống | Không |
| occupied, đúng một Order, không lịch hiện tại | Có khách | Không |
| maintenance, không Order/lịch hiện tại | Bảo trì | Không |
| Reservation tương lai | Chỉ tín hiệu đếm riêng, không đổi display | Không |
| Payment pending/paid/failed/refunded | Không tự thêm waiting_payment, paid hay release | Không |

Reservation hiện tại APPROVED: status pending/confirmed/arrived và start_at<=observed_at<end_at, dùng timezone ứng dụng hiện có. Cùng một observed_at cho toàn bộ query của mỗi lần đọc. Tương lai: start_at>observed_at. Quy tắc chỉ phục vụ hiển thị, không thay policy tạo/duyệt/hủy lịch.

## Mini plan sau khi policy được chốt
1. Read service chung tạo aggregate subqueries/scoped snapshot và resolver display; không query Order/Payment riêng mỗi bàn, không load lịch sử hoặc thông tin liên hệ không cần thiết.
2. Floor card, detail và Area metrics dùng cùng buckets và điều kiện. Area/floor count theo cùng scope/bộ lọc; mỗi bàn thuộc đúng một display bucket. Persisted status vẫn hiển thị riêng để đối chiếu, không lưu display enum.
3. Manual GET refresh giữ bộ lọc; observed_at rõ ràng. Khôi phục từ bfcache refresh một lần, không polling/POST/retry. Refresh không được gửi lại form hoặc mất draft của trang gọi món.
4. Tests isolated: matrix combinations, boundaries start/end, stale reads, no writes, auth, count reconciliation and bounded query count. Build/Node checks phù hợp; MySQL nếu đọc chỉ read-only, không thêm probe ghi vào database gốc.
5. Report phân biệt phần hoàn thành với Payment/Order-completion/release còn thiếu. Không triển khai task9.8 tự động.

## Phần chưa có authority
Payment success/completion/partial settlement/release, cleanup policy, ngoại lệ Reservation cùng Order đã arrived, và tự động cập nhật persisted status đều chưa được duyệt hoặc thiếu backend. Không sửa các writer, không tạo event/WebSocket mới. Các nhãn cần kiểm tra là fallback bảo thủ, không kết luận/xóa/hủy dữ liệu nghiệp vụ.

## Tình trạng triển khai
Đã triển khai phần đọc được duyệt; evidence và phần còn thiếu xem task9.7-report.md. Worktree task9.1–9.6 và thay đổi task9.9 được giữ nguyên.
