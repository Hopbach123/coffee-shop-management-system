<div class="maison-ai-widget" id="maisonAiWidget" data-storefront-advisor data-reply-url="{{ route('storefront-advisor.reply') }}">
    <button type="button" class="maison-ai-trigger" id="maisonAiTrigger" aria-expanded="false" aria-controls="maisonAiPanel" aria-label="Mở Trợ lý Maison">
        <span class="maison-ai-trigger__icon" aria-hidden="true">☕</span>
        <span class="maison-ai-trigger__label"><strong>Trợ lý Maison</strong><small>Tư vấn menu</small></span>
    </button>
    <section class="maison-ai-panel is-collapsed" id="maisonAiPanel" role="dialog" aria-modal="false" aria-labelledby="maisonAiTitle" aria-hidden="true">
        <header class="maison-ai-header"><div class="maison-ai-header__brand"><div class="maison-ai-avatar" aria-hidden="true">☕</div><div><h2 class="maison-ai-title" id="maisonAiTitle">Trợ lý Maison</h2><p class="maison-ai-status-text">Tư vấn menu và ưu đãi hiện hành</p></div></div><button type="button" class="maison-ai-btn-icon" id="maisonAiCloseBtn" aria-label="Đóng khung chat">×</button></header>
        <div class="maison-ai-dev-notice" role="note"><div><strong>Tư vấn tham khảo</strong><p>Giá, mã và điều kiện luôn được kiểm tra lại khi đặt đơn.</p></div></div>
        <div class="maison-ai-messages" id="maisonAiMessages" role="log" aria-live="polite" aria-label="Cuộc trò chuyện">
            <div class="maison-ai-msg maison-ai-msg--bot"><div class="maison-ai-msg__bubble">Xin chào! Mình có thể gợi ý đồ uống đang bán và ưu đãi hiện hành.</div></div>
            <div class="maison-ai-chips-wrap"><p class="maison-ai-chips-label">Gợi ý câu hỏi nhanh:</p><div class="maison-ai-chips"><button type="button" class="maison-ai-chip" data-question="Gợi ý món cà phê đang bán">Món cà phê đang bán</button><button type="button" class="maison-ai-chip" data-question="Có ưu đãi nào đang áp dụng?">Có ưu đãi nào?</button><button type="button" class="maison-ai-chip" data-question="Gợi ý một món từ thực đơn">Gợi ý món hôm nay</button></div></div>
        </div>
        <div class="maison-ai-typing is-hidden" id="maisonAiTyping" aria-live="polite"><span class="maison-ai-dot"></span><span class="maison-ai-dot"></span><span class="maison-ai-dot"></span><span class="maison-ai-typing__text">Đang tìm trong thực đơn…</span></div>
        <form class="maison-ai-input-form" id="maisonAiForm"><label class="sr-only" for="maisonAiInput">Câu hỏi cho trợ lý Maison</label><input type="text" class="maison-ai-input" id="maisonAiInput" maxlength="500" placeholder="Ví dụ: Có món cà phê nào?" autocomplete="off" required><button type="submit" class="maison-ai-send-btn" aria-label="Gửi câu hỏi">➜</button></form>
    </section>
</div>
