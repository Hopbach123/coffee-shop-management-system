<div class="floor-plan-actions" data-status-refresh-scope>
    <a class="coffee-button coffee-button--outline" href="{{ request()->fullUrl() }}" data-status-refresh>Làm mới trạng thái</a>
    <p>Thời điểm đọc: <time datetime="{{ $observedAt->toIso8601String() }}">{{ $observedAt->format('d/m/Y H:i:s T') }}</time>. Trạng thái hiển thị chỉ để quan sát, không cam kết bàn có thể nhận khách.</p>
    <span role="status" aria-live="polite" data-status-refresh-message></span>
</div>
