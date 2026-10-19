@extends('layouts.internal')
@section('title', 'Tạo yêu cầu đặt bàn')
@section('content')
<section class="category-page category-page--form" aria-labelledby="page-title">
    <p class="section-heading__eyebrow">Maison du Café · Quản trị</p>
    <h1 id="page-title">Tạo yêu cầu đặt bàn</h1>
    <p class="category-notice" role="note">Chỉ dùng thử nghiệm. Lịch trùng sẽ bị từ chối; không dùng để tiếp nhận đặt bàn thực tế trước khi hoàn tất kiểm chứng. Thời gian nhập theo UTC.</p>
    <form method="POST" action="{{ route('admin.reservations.store') }}" class="category-panel category-form">
        @csrf
        @if ($errors->any())<div class="category-notice category-notice--error" role="alert">Vui lòng kiểm tra thông tin bên dưới.</div>@endif
        @error('reservation')<p class="category-field__error" role="alert">{{ $message }}</p>@enderror
        @if ($tables->isEmpty())<p class="category-notice category-notice--error" role="alert">Chưa có bàn nhận yêu cầu. Kiểm tra bàn và khu vực trước khi tạo.</p>@endif
        <div class="category-fields">
            <div class="category-field">
                <label for="customer_id">Hồ sơ khách hàng (tùy chọn)</label>
                <select id="customer_id" name="customer_id" @error('customer_id') aria-invalid="true" aria-describedby="customer_id-error" @enderror>
                    <option value="">Khách vãng lai</option>
                    @foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>#{{ $customer->id }} · {{ $customer->name ?? 'Chưa có tên' }} · {{ $customer->phone ?? 'Chưa có số điện thoại' }}</option>@endforeach
                </select>
                <p>Nhập hoặc xác nhận tên và điện thoại của lượt đặt bên dưới. Hồ sơ khách hàng gốc được giữ nguyên.</p>
                @error('customer_id')<p id="customer_id-error" class="category-field__error">{{ $message }}</p>@enderror
            </div>
            <div class="category-field">
                <label for="table_id">Bàn *</label>
                <select id="table_id" name="table_id" required @error('table_id') aria-invalid="true" aria-describedby="table_id-error" @enderror>
                    <option value="">Chọn bàn</option>
                    @foreach ($tables as $table)<option value="{{ $table->id }}" @selected((string) old('table_id') === (string) $table->id)>{{ $table->table_code }} · {{ $table->area?->name ?? '—' }} · {{ $table->capacity }} khách · {{ $table->status === 'occupied' ? 'Đang có khách' : 'Bàn trống' }}</option>@endforeach
                </select>
                <p>Trạng thái hiện tại chưa xác nhận bàn trống tại thời gian bạn chọn.</p>
                @error('table_id')<p id="table_id-error" class="category-field__error">{{ $message }}</p>@enderror
            </div>
                <div class="category-field">
                    <label for="customer_name">Tên người đặt *</label>
                    <input id="customer_name" name="customer_name" type="text" maxlength="255" value="{{ old('customer_name') }}" required @error('customer_name') aria-invalid="true" aria-describedby="customer_name-error" @enderror>
                    @error('customer_name') <p id="customer_name-error" class="category-field__error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="customer_phone">Số điện thoại *</label>
                    <input id="customer_phone" name="customer_phone" type="tel" maxlength="20" value="{{ old('customer_phone') }}" required @error('customer_phone') aria-invalid="true" aria-describedby="customer_phone-error" @enderror>
                    @error('customer_phone') <p id="customer_phone-error" class="category-field__error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="guest_count">Số khách *</label>
                    <input id="guest_count" name="guest_count" type="number" min="1" max="2147483647" step="1" value="{{ old('guest_count') }}" required @error('guest_count') aria-invalid="true" aria-describedby="guest_count-error" @enderror>
                    @error('guest_count') <p id="guest_count-error" class="category-field__error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="reservation_start_at">Bắt đầu (UTC) *</label>
                    <input id="reservation_start_at" name="reservation_start_at" type="datetime-local"  value="{{ old('reservation_start_at') }}" required @error('reservation_start_at') aria-invalid="true" aria-describedby="reservation_start_at-error" @enderror>
                    @error('reservation_start_at') <p id="reservation_start_at-error" class="category-field__error">{{ $message }}</p> @enderror
                </div>
                <div class="category-field">
                    <label for="reservation_end_at">Kết thúc (UTC) *</label>
                    <input id="reservation_end_at" name="reservation_end_at" type="datetime-local"  value="{{ old('reservation_end_at') }}" required @error('reservation_end_at') aria-invalid="true" aria-describedby="reservation_end_at-error" @enderror>
                    @error('reservation_end_at') <p id="reservation_end_at-error" class="category-field__error">{{ $message }}</p> @enderror
                </div>
            <div class="category-field">
                <label for="note">Ghi chú</label>
                <textarea id="note" name="note" rows="4" maxlength="65535" @error('note') aria-invalid="true" aria-describedby="note-error" @enderror>{{ old('note') }}</textarea>
                @error('note')<p id="note-error" class="category-field__error">{{ $message }}</p>@enderror
            </div>
        </div>
        <div class="category-form-actions">
            <x-public.button type="submit" :disabled="$tables->isEmpty()">Tạo yêu cầu đặt bàn</x-public.button>
            <x-public.button href="{{ route('admin.reservations.index') }}" variant="outline">Quay lại danh sách</x-public.button>
        </div>
    </form>
</section>
@endsection

