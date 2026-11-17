<div class="maison-ai-widget" id="maisonAiWidget" data-chat-widget>
    <!-- Floating Trigger Button -->
    <button type="button" class="maison-ai-trigger" id="maisonAiTrigger" aria-expanded="false" aria-controls="maisonAiPanel" aria-label="Mở Trợ lý AI Maison">
        <span class="maison-ai-trigger__pulse"></span>
        <span class="maison-ai-trigger__icon">
            <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a3 3 0 0 0-3 3v1a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3Z"></path>
                <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                <line x1="12" y1="19" x2="12" y2="22"></line>
                <circle cx="12" cy="12" r="2"></circle>
            </svg>
        </span>
        <span class="maison-ai-trigger__label">
            <strong>Trợ lý AI</strong>
            <small>Vấn đáp</small>
        </span>
        <span class="maison-ai-trigger__badge">AI</span>
    </button>

    <!-- Glassmorphism Chat Window -->
    <section class="maison-ai-panel is-collapsed" id="maisonAiPanel" role="dialog" aria-labelledby="maisonAiTitle" aria-hidden="true">
        <!-- Panel Header -->
        <header class="maison-ai-header">
            <div class="maison-ai-header__brand">
                <div class="maison-ai-avatar">
                    <span>☕</span>
                    <span class="maison-ai-avatar__status"></span>
                </div>
                <div>
                    <h2 class="maison-ai-title" id="maisonAiTitle">Maison AI Bot</h2>
                    <p class="maison-ai-status-text">Trợ lý ảo Maison du Café</p>
                </div>
            </div>
            <div class="maison-ai-header__actions">
                <button type="button" class="maison-ai-btn-icon" id="maisonAiCloseBtn" aria-label="Đóng khung chat">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Development Notice Badge -->
        <div class="maison-ai-dev-notice" role="note">
            <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor">
                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
            </svg>
            <div>
                <strong>Trạng thái: Tính năng đang phát triển</strong>
                <p>Hỗ trợ giải đáp menu, đặt bàn & thông tin khuyến mãi tự động.</p>
            </div>
        </div>

        <!-- Chat Conversation Body -->
        <div class="maison-ai-messages" id="maisonAiMessages" tabindex="0">
            <!-- Bot Greeting -->
            <div class="maison-ai-msg maison-ai-msg--bot">
                <div class="maison-ai-msg__bubble">
                    <p>Xin chào quý khách! ☕ Em là <strong>Trợ lý AI của Maison du Café</strong>.</p>
                    <p>Em có thể hỗ trợ giải đáp về các món đặc trưng, quy trình đặt bàn hoặc ưu đãi mới nhất hôm nay ạ!</p>
                </div>
                <span class="maison-ai-msg__time">Vừa xong</span>
            </div>

            <!-- Quick Suggestions -->
            <div class="maison-ai-chips-wrap">
                <p class="maison-ai-chips-label">Gợi ý câu hỏi nhanh:</p>
                <div class="maison-ai-chips">
                    <button type="button" class="maison-ai-chip" data-question="Quán có những món cà phê nào đặc trưng?">
                        ☕ Món cà phê đặc trưng?
                    </button>
                    <button type="button" class="maison-ai-chip" data-question="Cách đặt bàn trước tại quán như thế nào?">
                        📅 Cách đặt bàn trước?
                    </button>
                    <button type="button" class="maison-ai-chip" data-question="Quán mở cửa từ mấy giờ đến mấy giờ?">
                        ⏰ Giờ mở cửa của quán?
                    </button>
                    <button type="button" class="maison-ai-chip" data-question="Hôm nay có chương trình khuyến mãi gì không?">
                        🎁 Khuyến mãi hôm nay?
                    </button>
                </div>
            </div>
        </div>

        <!-- Typing Indicator (Hidden by default) -->
        <div class="maison-ai-typing is-hidden" id="maisonAiTyping">
            <span class="maison-ai-dot"></span>
            <span class="maison-ai-dot"></span>
            <span class="maison-ai-dot"></span>
            <span class="maison-ai-typing__text">Maison AI đang suy nghĩ...</span>
        </div>

        <!-- Panel Footer / Input Form -->
        <form class="maison-ai-input-form" id="maisonAiForm">
            <input type="text" class="maison-ai-input" id="maisonAiInput" placeholder="Nhập câu hỏi cho Maison AI..." autocomplete="off" required>
            <button type="submit" class="maison-ai-send-btn" aria-label="Gửi câu hỏi">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </form>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const trigger = document.getElementById('maisonAiTrigger');
    const panel = document.getElementById('maisonAiPanel');
    const closeBtn = document.getElementById('maisonAiCloseBtn');
    const form = document.getElementById('maisonAiForm');
    const input = document.getElementById('maisonAiInput');
    const messages = document.getElementById('maisonAiMessages');
    const typing = document.getElementById('maisonAiTyping');
    const chips = document.querySelectorAll('.maison-ai-chip');

    if (!trigger || !panel) return;

    // Toggle Chat Panel
    function toggleChat(open) {
        const isCollapsed = panel.classList.contains('is-collapsed');
        const willOpen = typeof open === 'boolean' ? open : isCollapsed;

        panel.classList.toggle('is-collapsed', !willOpen);
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        panel.setAttribute('aria-hidden', willOpen ? 'false' : 'true');

        if (willOpen && input) {
            setTimeout(() => input.focus(), 200);
        }
    }

    trigger.addEventListener('click', () => toggleChat());
    if (closeBtn) closeBtn.addEventListener('click', () => toggleChat(false));

    // Knowledge Base FAQ simulator
    const knowledgeBase = [
        {
            keywords: ['cà phê', 'đặc trưng', 'món', 'uống', 'menu', 'bán chạy', 'thực đơn'],
            reply: 'Maison du Café tự hào với các món Signature thủ công: <strong>Cà phê sữa đá</strong> đậm vị truyền thống (35.000đ), <strong>Bạc xỉu</strong> béo mịn (39.000đ), và <strong>Cold Brew</strong> ủ lạnh thanh mát (45.000đ). Quý khách có thể xem toàn bộ tại mục <a href="/menu" style="color:#d4a373;font-weight:600;text-decoration:underline;">Thực đơn</a>!'
        },
        {
            keywords: ['đặt bàn', 'bàn', 'chỗ', 'hẹn trước', 'đặt chỗ'],
            reply: 'Quý khách có thể đặt bàn trực tuyến dễ dàng tại mục <a href="/reservation" style="color:#d4a373;font-weight:600;text-decoration:underline;">Đặt bàn trước</a>. Quán hỗ trợ chọn số khách, khung giờ và ghi chú vị trí (trong nhà, ngoài trời). Nhân viên sẽ xác nhận nhanh chóng ạ!'
        },
        {
            keywords: ['giờ', 'mở cửa', 'đóng cửa', 'thời gian', 'mấy giờ'],
            reply: 'Maison du Café mở cửa phục vụ từ <strong>07:00 sáng đến 22:30 tối</strong> tất cả các ngày trong tuần (kể cả Thứ Bảy, Chủ Nhật và ngày lễ) tại không gian ấm cúng!'
        },
        {
            keywords: ['khuyến mãi', 'ưu đãi', 'giảm giá', 'voucher', 'coupon'],
            reply: 'Hiện tại quán đang có ưu đãi <strong>Giảm 10% - 15%</strong> cho khách hàng thân thiết và đơn đặt bàn trước theo khung giờ vàng! Bạn có thể xem thêm tại mục <a href="/#specials" style="color:#d4a373;font-weight:600;text-decoration:underline;">Ưu đãi</a> trên trang chủ.'
        },
        {
            keywords: ['đăng ký', 'đăng nhập', 'tài khoản', 'member'],
            reply: 'Quý khách có thể tạo tài khoản hoặc đăng nhập trực tiếp tại <a href="/login" style="color:#d4a373;font-weight:600;text-decoration:underline;">Trang Đăng nhập / Đăng ký</a> để lưu thông tin và nhận ưu đãi riêng nhé!'
        }
    ];

    function appendMessage(text, isUser = false) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `maison-ai-msg ${isUser ? 'maison-ai-msg--user' : 'maison-ai-msg--bot'}`;

        const bubble = document.createElement('div');
        bubble.className = 'maison-ai-msg__bubble';
        if (isUser) {
            bubble.textContent = text;
        } else {
            bubble.innerHTML = text;
        }

        const time = document.createElement('span');
        time.className = 'maison-ai-msg__time';
        const now = new Date();
        time.textContent = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;

        msgDiv.appendChild(bubble);
        msgDiv.appendChild(time);
        messages.appendChild(msgDiv);
        messages.scrollTop = messages.scrollHeight;
    }

    function processAiReply(query) {
        const normalized = query.toLowerCase();
        let matched = knowledgeBase.find(item => item.keywords.some(k => normalized.includes(k)));

        typing.classList.remove('is-hidden');
        messages.scrollTop = messages.scrollHeight;

        setTimeout(() => {
            typing.classList.add('is-hidden');
            if (matched) {
                appendMessage(matched.reply, false);
            } else {
                appendMessage(
                    `Dạ câu hỏi của bạn về <em>"${query}"</em> đã được ghi nhận. Hệ thống Trợ lý AI đang trong quá trình huấn luyện & hoàn thiện. Quý khách vui lòng liên hệ nhân viên hoặc hotline quán để được hỗ trợ tức thì ạ! ☕`,
                    false
                );
            }
        }, 700);
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const val = input.value.trim();
            if (!val) return;

            appendMessage(val, true);
            input.value = '';
            processAiReply(val);
        });
    }

    // Quick chip click
    chips.forEach(chip => {
        chip.addEventListener('click', function () {
            const q = this.getAttribute('data-question');
            appendMessage(q, true);
            processAiReply(q);
        });
    });
});
</script>