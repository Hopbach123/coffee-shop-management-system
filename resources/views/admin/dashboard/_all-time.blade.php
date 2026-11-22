<div class="dashboard-orders dashboard-orders--kpis" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;margin-bottom:1.5rem;">
    {{-- Tổng đơn hàng --}}
    <section class="cafe-card dashboard-orders__kpi" style="padding:1.5rem;" aria-labelledby="total-orders-label" aria-describedby="total-orders-definition">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;">
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(173,118,76,.12);display:flex;align-items:center;justify-content:center;color:var(--cafe-accent);">
                <svg class="dashboard-orders__icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><path d="M7 4h10v17l-3-2-2 2-2-2-3 2V4Z"/><path d="M9 4V2h6v2M10 9h4M10 13h4"/></svg>
            </div>
            <h2 id="total-orders-label" style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);text-transform:uppercase;letter-spacing:.04em;margin:0;">Tổng đơn hàng</h2>
        </div>
        <p class="dashboard-orders__total">{{ number_format($orderStatistics['total'], 0, ',', '.') }}</p>
        <p id="total-orders-definition" style="font-size:.8rem;color:var(--cafe-text-secondary);margin:0;">Bao gồm đơn đang xử lý, hoàn tất và đã hủy.</p>
    </section>

    {{-- Tổng thanh toán --}}
    <section class="cafe-card dashboard-orders__kpi" style="padding:1.5rem;" aria-labelledby="paid-value-label" aria-describedby="paid-value-definition">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;">
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(49,113,83,.12);display:flex;align-items:center;justify-content:center;color:var(--cafe-success);">
                <svg class="dashboard-orders__icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/></svg>
            </div>
            <h2 id="paid-value-label" style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);text-transform:uppercase;letter-spacing:.04em;margin:0;">Tổng thanh toán thành công</h2>
        </div>
        @if ($revenueStatistics['available'])
            <p class="dashboard-orders__total">@include('admin.dashboard._vnd', ['amount' => $revenueStatistics['amount']])</p>
        @else
            <p class="category-notice category-notice--error cafe-notice cafe-notice--error" role="status" style="margin:0;">Chưa xác định — cần kiểm tra dữ liệu thanh toán.</p>
        @endif
        <p id="paid-value-definition" style="font-size:.8rem;color:var(--cafe-text-secondary);margin-top:.35rem;margin-bottom:0;">Tổng giá trị giao dịch đang ở trạng thái đã thanh toán (paid), trên toàn bộ thời gian. Không bao gồm giao dịch chờ xử lý, thất bại hoặc đã hoàn tiền. Đây không phải doanh thu thuần.</p>
    </section>

    {{-- Tổng số bàn --}}
    <section class="cafe-card dashboard-orders__kpi" style="padding:1.5rem;" aria-labelledby="total-tables-label" aria-describedby="total-tables-definition">
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem;">
            <div style="width:44px;height:44px;border-radius:10px;background:rgba(44,27,20,.08);display:flex;align-items:center;justify-content:center;color:var(--cafe-primary);">
                <svg class="dashboard-orders__icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22"><path d="M3 8h18v5H3zM6 13v7M18 13v7M8 8V4M16 8V4"/></svg>
            </div>
            <h2 id="total-tables-label" style="font-size:.8rem;font-weight:600;color:var(--cafe-text-secondary);text-transform:uppercase;letter-spacing:.04em;margin:0;">Tổng số bàn</h2>
        </div>
        <p class="dashboard-orders__total">{{ number_format($tableStatistics['total'], 0, ',', '.') }}</p>
        <p id="total-tables-definition" style="font-size:.8rem;color:var(--cafe-text-secondary);margin:0;">Bàn chưa xóa mềm, bao gồm bàn bảo trì và bàn thuộc khu vực ngừng hoạt động.</p>
    </section>
</div>

{{-- Detail grids --}}
<div class="dashboard-orders dashboard-orders--details" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;">
    {{-- Phân bố trạng thái đơn hàng --}}
    <section class="cafe-card" style="padding:1.25rem;" aria-labelledby="order-status-title">
        <h2 id="order-status-title" style="font-size:1rem;font-weight:700;color:var(--cafe-text-primary);margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid var(--cafe-border);">Phân bố trạng thái</h2>
        <dl class="dashboard-orders__statuses" style="display:flex;flex-direction:column;gap:.6rem;margin:0;">
            @foreach ($orderStatistics['by_status'] as $status => $count)
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <dt style="font-size:.875rem;color:var(--cafe-text-secondary);">{{ $orderStatusLabels[$status] ?? 'Trạng thái chưa xác định' }}</dt>
                    <dd style="font-weight:700;font-size:.9rem;color:var(--cafe-text-primary);margin:0;">{{ number_format($count, 0, ',', '.') }}</dd>
                </div>
            @endforeach
        </dl>
        @if ($orderStatistics['total'] === 0)
            <p style="color:var(--cafe-text-secondary);font-size:.9rem;margin-top:.75rem;margin-bottom:0;">Chưa có đơn hàng được ghi nhận.</p>
        @endif
    </section>

    {{-- Trạng thái bàn --}}
    <section class="cafe-card" style="padding:1.25rem;" aria-labelledby="table-status-title">
        <h2 id="table-status-title" style="font-size:1rem;font-weight:700;color:var(--cafe-text-primary);margin-bottom:.35rem;">Trạng thái bàn hiện tại</h2>
        <p style="font-size:.8rem;color:var(--cafe-text-secondary);margin-bottom:1rem;padding-bottom:.75rem;border-bottom:1px solid var(--cafe-border);">Trạng thái được tổng hợp từ khu vực, bàn và đơn tại chỗ đang xử lý.</p>
        <dl class="dashboard-orders__statuses" style="display:flex;flex-direction:column;gap:.6rem;margin:0;">
            @foreach ($tableStatistics['by_status'] as $status => $count)
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <dt class="dashboard-table-status dashboard-table-status--{{ $status }}" style="font-size:.875rem;color:var(--cafe-text-secondary);">{{ $tableStatusLabels[$status] }}</dt>
                    <dd style="font-weight:700;font-size:.9rem;color:var(--cafe-text-primary);margin:0;">{{ number_format($count, 0, ',', '.') }}</dd>
                </div>
            @endforeach
            @if ($tableStatistics['unclassified'] > 0)
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <dt style="font-size:.875rem;color:var(--cafe-danger);">Chưa phân loại — cần kiểm tra dữ liệu</dt>
                    <dd style="font-weight:700;font-size:.9rem;color:var(--cafe-danger);margin:0;">{{ number_format($tableStatistics['unclassified'], 0, ',', '.') }}</dd>
                </div>
            @endif
        </dl>
        @if ($tableStatistics['total'] === 0)
            <p style="color:var(--cafe-text-secondary);font-size:.9rem;margin-top:.75rem;margin-bottom:0;">Chưa có bàn được ghi nhận.</p>
        @endif
    </section>
</div>

