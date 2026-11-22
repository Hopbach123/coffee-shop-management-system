@extends('layouts.public')

@section('title', 'Đặt món — Maison du Café')

@section('content')
<section class="coffee-section commerce-page">
    <div class="coffee-container">
        <header class="commerce-hero">
            <p class="section-heading__eyebrow">ALMOST THERE</p>
            <h1>Đặt trước, nhận tại quán</h1>
            <p>Nhân viên sẽ tiếp nhận và chuẩn bị món tươi mới ngay khi bạn đến hoặc theo giờ hẹn.</p>
        </header>

        @if($errors->any())
            <div class="commerce-alert commerce-alert--error" role="alert">
                <strong>Vui lòng kiểm tra lại thông tin:</strong>
                <ul style="margin: 6px 0 0; padding-left: 18px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="commerce-layout">
            <form id="pickup-form" action="{{ route('cart.place') }}" method="POST" class="commerce-form">
                @csrf

                <div class="checkout-section">
                    <h2 style="font-family: var(--font-display); font-size: 1.35rem; color: var(--coffee-espresso); margin-top: 0; margin-bottom: 14px;">
                        1. Hình thức nhận món
                    </h2>

                    <div class="channel-selector-grid">
                        <label class="channel-card-option {{ old('purchase_type', 'online') === 'online' ? 'is-selected' : '' }}" id="label-online">
                            <input type="radio" name="purchase_type" value="online" id="type-online" {{ old('purchase_type', 'online') === 'online' ? 'checked' : '' }} onchange="togglePurchaseType()">
                            <div class="channel-card-content">
                                <span class="channel-card-title">🌐 Online lấy sớm</span>
                                <span class="channel-card-desc">Quán chuẩn bị ngay sau khi duyệt đơn, bạn ghé lấy sớm.</span>
                            </div>
                        </label>

                        <label class="channel-card-option {{ old('purchase_type') === 'scheduled' ? 'is-selected' : '' }}" id="label-scheduled">
                            <input type="radio" name="purchase_type" value="scheduled" id="type-scheduled" {{ old('purchase_type') === 'scheduled' ? 'checked' : '' }} onchange="togglePurchaseType()">
                            <div class="channel-card-content">
                                <span class="channel-card-title">⏰ Đặt hẹn theo giờ</span>
                                <span class="channel-card-desc">Hẹn giờ cụ thể đến quán lấy món (từ 07:00 đến 22:00).</span>
                            </div>
                        </label>
                    </div>

                    <div id="scheduled-time-wrapper" class="scheduled-picker-box" style="{{ old('purchase_type', 'online') === 'scheduled' ? '' : 'display: none;' }}">
                        <label for="pickup_at">
                            Thời gian hẹn đến nhận đồ (07:00 – 22:00) <span style="color: var(--coffee-danger);">*</span>
                        </label>
                        <input name="pickup_at" id="pickup_at" type="datetime-local" 
                               min="{{ now('Asia/Ho_Chi_Minh')->addMinutes(15)->format('Y-m-d\TH:i') }}" 
                               max="{{ now('Asia/Ho_Chi_Minh')->addDays(7)->format('Y-m-d\TH:i') }}" 
                               value="{{ old('pickup_at') }}"
                               class="scheduled-picker-input">
                        <span class="scheduled-notice">
                            ✧ Quán phục vụ nhận đồ từ 07:00 đến 22:00. Vui lòng đặt trước ít nhất 15 phút.
                        </span>
                    </div>
                </div>

                <div class="checkout-section" style="border-top: 1px dashed var(--coffee-border); padding-top: 20px;">
                    <h2 style="font-family: var(--font-display); font-size: 1.35rem; color: var(--coffee-espresso); margin-top: 0; margin-bottom: 14px;">
                        2. Thông tin liên hệ
                    </h2>

                    <div class="commerce-fields">
                        <div>
                            <label for="customer_name">Họ và tên khách hàng <span style="color: var(--coffee-danger);">*</span></label>
                            <input name="customer_name" id="customer_name" type="text" required maxlength="120" value="{{ old('customer_name') }}" autocomplete="name" placeholder="VD: Nguyễn Văn A">
                        </div>

                        <div>
                            <label for="customer_phone">Số điện thoại liên hệ <span style="color: var(--coffee-danger);">*</span></label>
                            <input name="customer_phone" id="customer_phone" type="tel" required maxlength="20" value="{{ old('customer_phone') }}" autocomplete="tel" placeholder="VD: 0901234567">
                        </div>

                        <div>
                            <label for="note">Ghi chú cho đơn hàng (tùy chọn)</label>
                            <textarea name="note" id="note" maxlength="500" rows="3" placeholder="Ví dụ: ít ngọt, lấy sớm hơn 5 phút, chuẩn bị túi mang đi...">{{ old('note') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="checkout-section" style="border-top: 1px dashed var(--coffee-border); padding-top: 20px;">
                    <h2 style="font-family: var(--font-display); font-size: 1.35rem; color: var(--coffee-espresso); margin-top: 0; margin-bottom: 14px;">
                        3. Hình thức thanh toán
                    </h2>

                    <div style="display: grid; gap: 10px; margin-bottom: 12px;">
                        <label style="border: 1px solid var(--coffee-border); border-radius: var(--radius-sm); padding: 12px 16px; cursor: pointer; display: flex; align-items: center; gap: 10px; background: var(--coffee-bg);">
                            <input type="radio" name="payment_option" value="on_receipt" {{ old('payment_option', 'on_receipt') === 'on_receipt' ? 'checked' : '' }} onchange="togglePaymentOption()" style="accent-color: var(--coffee-caramel); width: 16px; height: 16px;">
                            <span style="font-size: 0.95rem; font-weight: 500;">💵 Thanh toán tại quầy khi nhận đồ</span>
                        </label>

                        <label style="border: 1px solid var(--coffee-border); border-radius: var(--radius-sm); padding: 12px 16px; cursor: pointer; display: flex; align-items: center; gap: 10px; background: var(--coffee-bg);">
                            <input type="radio" name="payment_option" value="bank_transfer_prepaid" {{ old('payment_option') === 'bank_transfer_prepaid' ? 'checked' : '' }} onchange="togglePaymentOption()" style="accent-color: var(--coffee-caramel); width: 16px; height: 16px;">
                            <span style="font-size: 0.95rem; font-weight: 500;">🏦 Chuyển khoản trước</span>
                        </label>
                    </div>

                    <div id="prepaid-transfer-notice" style="display: none; margin-bottom: 12px; padding: 12px 14px; border: 1px solid #eed9be; border-radius: var(--radius-sm); background: #fdf6ec; color: var(--coffee-text); font-size: 0.88rem; line-height: 1.5;">
                        <strong style="display: block; color: var(--coffee-espresso); margin-bottom: 4px;">Xác minh thanh toán bởi nhân viên</strong>
                        Sau khi bạn chuyển khoản theo hướng dẫn của quán, nhân viên sẽ đối chiếu giao dịch thực nhận. Việc gửi yêu cầu hoặc tích xác nhận này <strong>không</strong> tự chuyển đơn sang đã thanh toán.
                        <label style="display: flex; align-items: flex-start; gap: 8px; margin-top: 10px; cursor: pointer;">
                            <input type="checkbox" name="prepaid_transfer_declaration" value="1" {{ old('prepaid_transfer_declaration') ? 'checked' : '' }} style="margin-top: 3px; accent-color: var(--coffee-caramel);">
                            <span>Tôi hiểu quán chỉ ghi nhận thanh toán sau khi nhân viên xác minh giao dịch.</span>
                        </label>
                        @error('prepaid_transfer_declaration')
                            <small style="display: block; color: var(--coffee-danger); margin-top: 7px;">{{ $message }}</small>
                        @enderror
                    </div>

                    <p style="color: var(--coffee-text-muted); font-size: 0.85rem; margin: 0; line-height: 1.4;">
                        ℹ️ Sau khi gửi, bạn sẽ nhận được mã tra cứu tiến độ đơn hàng trên website. Nhân viên chỉ xác nhận đã thanh toán khi đã đối chiếu tiền thực nhận.
                    </p>
                </div>
            </form>

            <aside class="commerce-summary">
                <h2>Đơn của bạn</h2>

                <div style="display: grid; gap: 12px; margin-bottom: 16px; border-bottom: 1px dashed var(--coffee-border); padding-bottom: 16px;">
                    @foreach($cart['items'] as $line)
                        <div style="display: flex; justify-content: space-between; font-size: 0.95rem; gap: 10px;">
                            <div>
                                <strong style="color: var(--coffee-espresso);">{{ $line['product_name'] }}</strong>
                                <small style="display: block; color: var(--coffee-text-muted); margin-top: 2px;">
                                    {{ $line['variant_name'] }} × {{ $line['quantity'] }}
                                    @if(!empty($line['toppings']))
                                        · +{{ collect($line['toppings'])->pluck('name')->join(', ') }}
                                    @endif
                                    @if(!empty($line['note']))
                                        ({{ $line['note'] }})
                                    @endif
                                </small>
                            </div>
                            <strong style="color: var(--coffee-caramel); white-space: nowrap;">
                                {{ number_format((float)$line['subtotal'], 0, ',', '.') }}đ
                            </strong>
                        </div>
                    @endforeach
                </div>

                <div class="commerce-total" style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 18px;">
                    <span style="font-weight: 700; font-size: 1.05rem;">Tạm tính</span>
                    <strong style="font-size: 1.45rem; color: var(--coffee-caramel); font-family: var(--font-display);">
                        {{ number_format($cart['total_cents'] / 100, 0, ',', '.') }}đ
                    </strong>
                </div>

                <button class="coffee-button coffee-button--primary commerce-wide" type="submit" form="pickup-form" style="cursor: pointer;">
                    Gửi yêu cầu nhận món →
                </button>

                <div style="text-align: center; margin-top: 14px;">
                    <a href="{{ route('pickup.cart.index') }}" class="commerce-secondary" style="color: var(--coffee-text-muted); font-size: 0.88rem;">
                        ← Quay lại giỏ hàng
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>

<script>
function togglePurchaseType() {
    const isScheduled = document.getElementById('type-scheduled').checked;
    const timeWrapper = document.getElementById('scheduled-time-wrapper');
    const pickupInput = document.getElementById('pickup_at');
    const labelOnline = document.getElementById('label-online');
    const labelScheduled = document.getElementById('label-scheduled');

    if (isScheduled) {
        timeWrapper.style.display = 'grid';
        pickupInput.required = true;
        labelScheduled.classList.add('is-selected');
        labelOnline.classList.remove('is-selected');
    } else {
        timeWrapper.style.display = 'none';
        pickupInput.required = false;
        pickupInput.value = '';
        labelOnline.classList.add('is-selected');
        labelScheduled.classList.remove('is-selected');
    }
}
function togglePaymentOption() {
    const isPrepaid = document.querySelector('input[name="payment_option"]:checked')?.value === 'bank_transfer_prepaid';
    const notice = document.getElementById('prepaid-transfer-notice');
    const declaration = document.querySelector('input[name="prepaid_transfer_declaration"]');

    notice.style.display = isPrepaid ? 'block' : 'none';
    declaration.required = isPrepaid;
    if (!isPrepaid) declaration.checked = false;
}
document.addEventListener('DOMContentLoaded', () => {
    togglePurchaseType();
    togglePaymentOption();
});
</script>
@endsection
