@extends('layouts.internal')
@section('title', 'Tạo yêu cầu đặt bàn')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng">
            <a href="{{ route('admin.reservations.index') }}" style="color:var(--cafe-text-secondary);text-decoration:none;">Đặt bàn</a>
            <span>Tạo mới</span>
        </nav>
        <h1 class="cafe-page-title" id="page-title">Tạo yêu cầu đặt bàn</h1>
        <p class="cafe-page-subtitle">Thời gian nhập theo UTC. Lịch trùng sẽ bị từ chối.</p>
    </div>
</div>

<div class="cafe-card" style="max-width:720px;padding:1.5rem;">
    <div class="cafe-notice cafe-notice--info" role="note" style="margin-bottom:1.25rem;">
        Chỉ dùng thử nghiệm. Lịch trùng sẽ bị từ chối; không dùng để tiếp nhận đặt bàn thực tế trước khi hoàn tất kiểm chứng.
    </div>

    @if ($errors->any())
        <div class="cafe-notice cafe-notice--error" role="alert">Vui lòng kiểm tra thông tin bên dưới.</div>
    @endif
    @error('reservation')<p class="cafe-field-error" role="alert">{{ $message }}</p>@enderror
    @if ($tables->isEmpty())
        <div class="cafe-notice cafe-notice--error" role="alert">Chưa có bàn nhận yêu cầu. Kiểm tra bàn và khu vực trước khi tạo.</div>
    @endif

    <form method="POST" action="{{ route('admin.reservations.store') }}">
        @csrf
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <div>
                <label for="customer_id" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Hồ sơ khách hàng (tùy chọn)</label>
                <select id="customer_id" class="cafe-select" name="customer_id" @error('customer_id') aria-invalid="true" aria-describedby="customer_id-error" @enderror>
                    <option value="">Khách vãng lai</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>#{{ $customer->id }} · {{ $customer->name ?? 'Chưa có tên' }} · {{ $customer->phone ?? 'Chưa có số điện thoại' }}</option>
                    @endforeach
                </select>
                <p style="font-size:.8rem;color:var(--cafe-text-secondary);margin-top:.25rem;margin-bottom:0;">Nhập hoặc xác nhận tên và điện thoại của lượt đặt bên dưới. Hồ sơ khách hàng gốc được giữ nguyên.</p>
                @error('customer_id')<p id="customer_id-error" class="cafe-field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="table_id" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Bàn *</label>
                <select id="table_id" class="cafe-select" name="table_id" required @error('table_id') aria-invalid="true" aria-describedby="table_id-error" @enderror>
                    <option value="">Chọn bàn</option>
                    @foreach ($tables as $table)
                        <option value="{{ $table->id }}" @selected((string) old('table_id') === (string) $table->id)>{{ $table->table_code }} · {{ $table->area?->name ?? '—' }} · {{ $table->capacity }} khách · {{ $table->status === 'occupied' ? 'Đang có khách' : 'Bàn trống' }}</option>
                    @endforeach
                </select>
                <p style="font-size:.8rem;color:var(--cafe-text-secondary);margin-top:.25rem;margin-bottom:0;">Trạng thái hiện tại chưa xác nhận bàn trống tại thời gian bạn chọn.</p>
                @error('table_id')<p id="table_id-error" class="cafe-field-error">{{ $message }}</p>@enderror
            </div>
            <div class="v5-form-grid">
                <div>
                    <label for="customer_name" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Tên người đặt *</label>
                    <input id="customer_name" class="cafe-input" name="customer_name" type="text" maxlength="255" value="{{ old('customer_name') }}" required @error('customer_name') aria-invalid="true" aria-describedby="customer_name-error" @enderror>
                    @error('customer_name') <p id="customer_name-error" class="cafe-field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="customer_phone" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Số điện thoại *</label>
                    <input id="customer_phone" class="cafe-input" name="customer_phone" type="tel" maxlength="20" value="{{ old('customer_phone') }}" required @error('customer_phone') aria-invalid="true" aria-describedby="customer_phone-error" @enderror>
                    @error('customer_phone') <p id="customer_phone-error" class="cafe-field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label for="guest_count" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Số khách *</label>
                <input id="guest_count" class="cafe-input" name="guest_count" type="number" min="1" max="2147483647" step="1" value="{{ old('guest_count') }}" required @error('guest_count') aria-invalid="true" aria-describedby="guest_count-error" @enderror>
                @error('guest_count') <p id="guest_count-error" class="cafe-field-error">{{ $message }}</p> @enderror
            </div>
            <div class="v5-form-grid">
                <div>
                    <label for="reservation_start_at" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Bắt đầu (UTC) *</label>
                    <input id="reservation_start_at" class="cafe-input" name="reservation_start_at" type="datetime-local" value="{{ old('reservation_start_at') }}" required @error('reservation_start_at') aria-invalid="true" aria-describedby="reservation_start_at-error" @enderror>
                    @error('reservation_start_at') <p id="reservation_start_at-error" class="cafe-field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="reservation_end_at" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Kết thúc (UTC) *</label>
                    <input id="reservation_end_at" class="cafe-input" name="reservation_end_at" type="datetime-local" value="{{ old('reservation_end_at') }}" required @error('reservation_end_at') aria-invalid="true" aria-describedby="reservation_end_at-error" @enderror>
                    @error('reservation_end_at') <p id="reservation_end_at-error" class="cafe-field-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label for="note" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Ghi chú</label>
                <textarea id="note" class="cafe-textarea" name="note" rows="3" maxlength="65535" @error('note') aria-invalid="true" aria-describedby="note-error" @enderror placeholder="Yêu cầu riêng của khách…">{{ old('note') }}</textarea>
                @error('note')<p id="note-error" class="cafe-field-error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--cafe-border);">
            <button type="submit" class="cafe-btn-primary" @disabled($tables->isEmpty())>Tạo yêu cầu đặt bàn</button>
            <a href="{{ route('admin.reservations.index') }}" class="cafe-btn-secondary">Quay lại danh sách</a>
        </div>
    </form>
</div>
@endsection
