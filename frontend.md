# Coffee Shop Frontend Design Standard

Tài liệu này là hợp đồng thiết kế frontend chính thức của Coffee Shop Management System. Engineer và AI Agent phải đọc `architecture.md` và toàn bộ `frontend.md` trước mọi frontend task.

## 1. Design Vision

Ngôn ngữ hình ảnh là một European coffeehouse cổ điển được chuyển thành web application hiện đại: vintage nhưng không cũ kỹ, ấm áp nhưng không tối, cao cấp nhưng thân thiện, editorial và thủ công. Trải nghiệm phải gợi cảm giác về giấy parchment, gỗ espresso, gốm thủ công, ánh sáng sớm và nhịp phục vụ chậm rãi.

Các tính từ định hướng: **warm, refined, premium, timeless, cozy, quiet, editorial, artisanal và coffee-centric**. Không sao chép logo, tài sản trang trí, nội dung hoặc kích thước bố cục của nguồn tham chiếu.

## 2. Design Principles

1. **Consistency:** cùng token, typography, border language và interaction behavior trên Public, Admin và Staff.
2. **Restraint:** mỗi chi tiết trang trí phải có mục đích; ưu tiên một điểm nhấn mạnh thay vì nhiều hiệu ứng cạnh tranh.
3. **Readability:** nội dung và thao tác luôn quan trọng hơn novelty.
4. **Whitespace:** dùng khoảng thở rộng để tạo nhịp editorial; không nén các nhóm nội dung không liên quan.
5. **Hierarchy:** heading, copy, metadata và action phải có vai trò thị giác rõ ràng.
6. **Brand warmth:** cream, sand, cocoa và caramel thay cho trắng/đen lạnh.
7. **Responsive-first thinking:** component phải hoạt động từ mobile đến desktop, không vá mobile sau cùng.
8. **Progressive enhancement:** HTML phải hiểu và dùng được trước; JavaScript chỉ tăng cường tương tác.

## 3. Color System

Mọi màu theme chính nằm trong `:root` của `resources/css/public/coffee-theme.css`. Không lặp arbitrary HEX trong component.

| Token | Giá trị | Công dụng |
| --- | --- | --- |
| `--coffee-bg` | `#f4eddf` | nền parchment chính |
| `--coffee-surface` | `#fbf7ef` | surface sáng, section/card |
| `--coffee-surface-alt` | `#e7d8bf` | sand surface, callout |
| `--coffee-surface-deep` | `#d5bea0` | beige đậm, badge và detail |
| `--coffee-border` | `#c9b69a` | border/separator ấm, low contrast |
| `--coffee-text` | `#342820` | nội dung chính |
| `--coffee-text-muted` | `#746458` | nội dung phụ |
| `--coffee-espresso` | `#2c1b14` | nền tối nhất, footer/header |
| `--coffee-cocoa` | `#4b3024` | surface tối và emphasis |
| `--coffee-bean` | `#6b4735` | hover/secondary brown |
| `--coffee-caramel` | `#ad7547` | primary action và accent |
| `--coffee-accent` | `#7a6547` | accent olive-brown tùy chọn |
| `--coffee-success` | `#687259` | success state, phải kèm text/icon |
| `--coffee-danger` | `#9b493d` | error/destructive state, phải kèm text/icon |
| `--coffee-on-dark` | `#f9f1e3` | text chính trên nền tối |
| `--coffee-on-dark-muted` | `#d8c7ae` | text phụ trên nền tối |
| `--coffee-focus` | `#c58a56` | focus ring dễ nhận biết |

Overlay và shadow dùng các token rgba riêng. Tạo token mới có semantic purpose nếu thật sự cần; không thêm màu chỉ để giải quyết một component duy nhất.

## 4. Typography

- **Display/heading:** `--font-display`, ưu tiên serif editorial (`Iowan Old Style`, Palatino, Georgia fallback).
- **Body/UI:** `--font-body`, ưu tiên Instrument Sans từ Vite fonts và system sans fallback.
- **Accent:** `--font-accent`, chỉ dùng cho câu ngắn mang tính trang trí; không dùng cho navigation, form label hoặc body dài.
- **Display:** `--text-display`, dùng duy nhất cho hero/brand statement.
- **H1/H2/H3:** dùng `--text-h1`, `--text-h2`, `--text-h3`; scale responsive bằng `clamp()`.
- **Body:** `--text-body` với line-height khoảng `1.7`.
- **Body large:** `--text-body-lg` cho lead paragraph.
- **Small/caption:** `--text-small`, `--text-caption`; caption cần tracking đủ rộng nếu viết hoa.
- **Label/button:** sans-serif, medium/semibold, tracking có kiểm soát; không dùng script.

Không thêm font ngẫu nhiên. Font decorative phải ít, dễ bỏ trên màn hình nhỏ và không ảnh hưởng khả năng đọc.

## 5. Spacing System

| Token | Giá trị khởi điểm | Dùng cho |
| --- | --- | --- |
| `--space-xs` | `0.375rem` | detail rất nhỏ |
| `--space-sm` | `0.75rem` | gap trong component |
| `--space-md` | `1.25rem` | padding/gap mặc định |
| `--space-lg` | `2rem` | nhóm nội dung |
| `--space-xl` | `3.5rem` | khoảng giữa block lớn |
| `--space-2xl` | responsive `5–8.5rem` | page section |

Ưu tiên token thay vì số tùy ý. Mobile có thể giảm section spacing nhưng vẫn phải giữ nhịp và phân nhóm rõ ràng.

## 6. Borders, Radius and Shadows

- Border dùng `--coffee-border`, thường dày `1px`, không dùng đen tuyệt đối.
- `--radius-sm`: form controls, buttons và detail nhỏ.
- `--radius-md`: cards thông thường.
- `--radius-lg`: panel lớn khi cần; không biến mọi component thành pill.
- `--shadow-soft`: panel/ảnh editorial cần chiều sâu nhẹ.
- `--shadow-line`: header hoặc separator elevation thấp.
- Không chồng nhiều shadow và không dùng floating-card effect mạnh.

## 7. Layout System

- Container chính dùng `.coffee-container`, tối đa `--container-wide: 82rem`.
- Nội dung đọc dài giới hạn bằng `--container-reading: 46rem`.
- Public section dùng `.coffee-section` và whitespace rộng.
- Desktop ưu tiên editorial split layout, deliberate asymmetry và ảnh lớn.
- Tablet giảm khoảng cách, cho grid từ ba xuống hai hoặc một cột theo nội dung.
- Mobile dùng một cột, thứ tự DOM có nghĩa, action touch-friendly và padding tối thiểu có chủ đích.
- Không đặt fixed width gây horizontal overflow.
- Dùng CSS Grid/Flexbox và `minmax(0, 1fr)` để nội dung co đúng.

Bootstrap hiện chưa được cài trong project. Foundation này dùng CSS thuần với Vite hiện có để không thêm dependency ngoài scope. Nếu Bootstrap được phê duyệt ở task tương lai, chỉ dùng làm structural support và phải override bằng các token này; không để giao diện mang default Bootstrap appearance.

## 8. Image Standards

- Hero ưu tiên ảnh landscape rộng; ảnh story/menu ưu tiên `4:5` hoặc tỷ lệ được component quy định.
- Luôn dùng `object-fit: cover`; không kéo méo ảnh.
- Đặt `width`/`aspect-ratio` trong CSS để giảm layout shift.
- Tông ảnh: ánh sáng tự nhiên, cream–cocoa–caramel, texture thật và saturation thấp.
- Frame ảnh dùng border ấm, radius tiết chế và shadow nhẹ.
- Meaningful images phải có `alt`; ảnh trang trí dùng alt rỗng hoặc CSS background phù hợp.
- Không dùng logo, watermark, brand của bên khác hoặc ảnh không rõ quyền sử dụng.
- Generated/original assets phải được lưu trong project, không phụ thuộc đường dẫn tạm.

## 9. Blade Architecture

- `resources/views/layouts/`: document shell, metadata và shared page structure.
- `resources/views/components/`: component thực sự tái sử dụng như navbar, button, section heading và footer.
- `resources/views/public/`: customer-facing pages.
- `resources/views/admin/`: internal administration pages.
- `resources/views/staff/`: operational/POS pages.
- `resources/views/auth/`: authentication pages khi được triển khai.

Không tách component chỉ để làm project trông modular. Component phải có reuse hoặc responsibility rõ ràng. Blade không truy vấn database và không chứa business logic.

## 10. Public UI Standard

Public UI có thể giàu hình ảnh hơn internal UI nhưng phải bình tĩnh, có nhịp editorial và lấy coffee làm trọng tâm. Dùng hero imagery mạnh, serif headings, composition ảnh/chữ, surface parchment và caramel accent. CTA rõ ràng nhưng không quá lớn hoặc quá nhiều. Tránh layout kiểu admin card grid được phủ ảnh cà phê ngẫu nhiên.

## 11. Admin UI Standard

Admin giữ typography, color tokens, border và focus language chung nhưng ưu tiên data density hợp lý, scanability và thao tác chính xác. Bảng, filter và form phải rõ hơn trang public; giảm script font, texture và decorative composition. Không hy sinh khả năng đọc dữ liệu để giữ phong cách vintage.

## 12. Staff / POS UI Standard

Staff/POS ưu tiên tốc độ, target lớn, trạng thái rõ, hierarchy mạnh và số thao tác tối thiểu. Status không chỉ biểu diễn bằng màu. Nút chính phải dễ chạm, order items dễ scan, destructive action có phân biệt rõ. Branding thể hiện qua palette, type, border và spacing; không qua decoration gây chậm thao tác.

## 13. Component Standards

- **Buttons:** tối thiểu khoảng `48px` cao, action label ngắn, primary/outline/ghost có hierarchy rõ; không lạm dụng pill.
- **Inputs/select/textarea:** label hiển thị, border ấm, focus ring rõ, error text gần field; placeholder không thay label.
- **Cards:** dùng border trước shadow; radius vừa phải; một card chỉ đại diện một nhóm nội dung.
- **Product/menu cards:** ưu tiên tên, mô tả ngắn, giá và metadata; ảnh có tỷ lệ thống nhất nếu sử dụng.
- **Tables:** header rõ, số canh phù hợp, row states không chỉ bằng màu, responsive bằng strategy cụ thể chứ không ép co chữ.
- **Modals:** dùng cho task ngắn và có focus management; không nhồi workflow dài.
- **Badges:** nhỏ, semantic, luôn có text; không dùng làm decoration tràn lan.
- **Alerts:** có title/message/action rõ; success/danger kèm icon hoặc label.
- **Navigation:** keyboard accessible, active state rõ, mobile toggle cập nhật `aria-expanded`.
- **Pagination:** target đủ lớn, current page có text/ARIA state, không chỉ đổi màu.
- **Sidebar:** internal UI only, hierarchy đơn giản và collapse có kiểm soát.
- **Metric cards:** dùng cho dữ liệu thực sự cần so sánh; ưu tiên số và context, không trang trí quá mức.

## 14. Responsive Standard

- **Desktop (~1440px):** dùng container rộng có kiểm soát, hero editorial, 3-column menu và split story composition.
- **Tablet (~768px):** navigation chuyển sang mobile mode, grids thu gọn, copy vẫn dễ đọc và target vẫn đủ lớn.
- **Mobile (~375px):** một cột, button full width khi hợp lý, menu đóng/mở rõ, decoration đơn giản hóa và không có horizontal overflow.
- Breakpoint theo convention phổ biến: `62rem`, `48rem`, `36rem`, chỉ thêm breakpoint khi component thực sự cần.
- Kiểm tra cả chiều rộng dài và nội dung text dài; không chỉ resize ảnh chụp đẹp nhất.

## 15. Accessibility

- Dùng semantic HTML (`header`, `nav`, `main`, `section`, `footer`, heading order).
- Có skip link và visible focus state.
- Tương tác dùng được bằng keyboard; Escape đóng navigation mobile.
- Form có label; lỗi được mô tả bằng text.
- Meaningful images có alt text mô tả mục đích.
- Target tương tác tối thiểu khoảng `44–48px`.
- Độ tương phản phải đủ; không truyền đạt trạng thái chỉ bằng màu.
- Tôn trọng `prefers-reduced-motion`.

## 16. Motion Standard

Motion phải ngắn, nhẹ và có mục đích: hover feedback, menu reveal hoặc state transition. Thời lượng thông thường khoảng `160–200ms`. Không dùng parallax nặng, animation lặp vô hạn, entrance choreography dài hoặc hiệu ứng làm chậm task. Khi reduced motion được yêu cầu, giảm gần như toàn bộ transition/animation.

## 17. Frontend Coding Rules

- Không dùng arbitrary colors; thêm semantic CSS variable khi cần.
- Không thêm font ngẫu nhiên.
- Không dùng inline CSS trừ khi có lý do kỹ thuật được ghi rõ.
- Tái sử dụng CSS variables.
- Kiểm tra component hiện có trước khi tạo component mới.
- Không duplicate component hoặc style block.
- Không dùng default Bootstrap appearance mà không customize.
- Không tạo React/Vue components.
- Không đưa vào frontend framework thứ hai.
- Không thay đổi visual language khi chưa được phê duyệt.
- Đọc `frontend.md` trước mọi frontend task.
- JavaScript chỉ progressive enhancement; business rules thuộc backend.
- Không đặt AJAX order/payment logic trong task chỉ làm presentation.

## 18. AI Agent Frontend Rules

Mọi AI Agent làm frontend bắt buộc:

1. Đọc `architecture.md`.
2. Đọc toàn bộ `frontend.md`.
3. Inspect layouts, pages và components hiện có.
4. Tái sử dụng design tokens.
5. Tái sử dụng component trước khi tạo mới.
6. Tuân theo responsive rules.
7. Giữ accessibility basics.
8. Không redesign phần không liên quan.
9. Ở đúng scope task.
10. Báo cáo thay đổi thị giác.
11. Báo cáo file đã thay đổi.
12. Build assets và kiểm tra desktop/mobile layout.

## 19. Forbidden Visual Patterns

Cấm các pattern sau nếu chưa có phê duyệt rõ ràng:

- Random hoặc excessive gradients.
- Neon colors.
- Glassmorphism.
- Generic SaaS/dashboard style cho public site.
- Excessive pill UI.
- Excessive box shadows.
- Inconsistent radius.
- Random icon styles.
- Excessive animation.
- Decorative text làm giảm khả năng đọc.
- Inconsistent spacing.
- Arbitrary CSS values bị lặp lại trong source.
- Cartoonish illustration hoặc fast-food visual language.

## 20. Definition of Done for Frontend Task

Frontend task chỉ DONE khi:

- Khớp `frontend.md` và architecture hiện hành.
- Responsive ở desktop, tablet và mobile.
- Không có broken layout hoặc horizontal overflow.
- Basic interaction dùng được bằng keyboard và có focus state.
- Design tokens được tái sử dụng.
- Không duplicate component/style không cần thiết.
- Frontend build thành công.
- Laravel page render không exception.
- Engineer có thể review danh sách file thay đổi.
- Có thể thực hiện screenshot hoặc visual verification tại viewport mục tiêu.