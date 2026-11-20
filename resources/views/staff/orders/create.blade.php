@extends('layouts.internal')
@section('title', 'Tạo đơn hàng')
@section('content')
<div class="cafe-page-header">
    <div>
        <nav class="cafe-breadcrumbs" aria-label="Điều hướng">
            <a href="{{ route('staff.orders.index') }}" style="color:var(--cafe-text-secondary);text-decoration:none;">Đơn hàng</a>
            <span>Tạo mới</span>
        </nav>
        <h1 class="cafe-page-title" id="page-title">Tạo đơn hàng</h1>
        <p class="cafe-page-subtitle">Tạo đơn tại chỗ hoặc đơn mang đi. Món sẽ được thêm ở bước tiếp theo.</p>
    </div>
</div>

<div class="cafe-card" style="max-width:680px;padding:1.5rem;">
    <form method="POST" action="{{ route('staff.orders.store') }}">
        @csrf
        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <div>
                <label for="order_type" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Loại đơn *</label>
                <select id="order_type" class="cafe-select" name="order_type" required @error('order_type') aria-invalid="true" @enderror>
                    <option value="dine_in" @selected(old('order_type', 'dine_in') === 'dine_in')>Tại chỗ</option>
                    <option value="takeaway" @selected(old('order_type') === 'takeaway')>Mang đi</option>
                </select>
                @error('order_type')<p class="cafe-field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="table_id" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Bàn</label>
                <select id="table_id" class="cafe-select" name="table_id" @error('table_id') aria-invalid="true" @enderror>
                    <option value="">Chọn bàn cho đơn tại chỗ</option>
                    @foreach ($tables as $table)
                        <option value="{{ $table->id }}" @selected((string) old('table_id') === (string) $table->id)>{{ $table->table_code }}{{ $table->table_name ? ' · '.$table->table_name : '' }} ({{ $table->area?->name }})</option>
                    @endforeach
                </select>
                <p style="font-size:.8rem;color:var(--cafe-text-secondary);margin-top:.25rem;margin-bottom:0;">Đơn mang đi không chọn bàn.</p>
                @error('table_id')<p class="cafe-field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="customer_id" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Khách hàng</label>
                <select id="customer_id" class="cafe-select" name="customer_id" @error('customer_id') aria-invalid="true" @enderror>
                    <option value="">Khách vãng lai</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name ?? 'Không tên' }}{{ $customer->phone ? ' · '.$customer->phone : '' }}</option>
                    @endforeach
                </select>
                @error('customer_id')<p class="cafe-field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="note" style="display:block;font-size:.85rem;font-weight:600;color:var(--cafe-text-secondary);margin-bottom:.35rem;">Ghi chú</label>
                <textarea id="note" class="cafe-textarea" name="note" rows="3" maxlength="65535" @error('note') aria-invalid="true" @enderror placeholder="Ghi chú đơn hàng…">{{ old('note') }}</textarea>
                @error('note')<p class="cafe-field-error">{{ $message }}</p>@enderror
            </div>
        </div>
        @error('order')<div class="cafe-notice cafe-notice--error" role="alert" style="margin-top:1rem;">{{ $message }}</div>@enderror
        <div style="display:flex;gap:.75rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--cafe-border);">
            <button type="submit" class="cafe-btn-primary">Tạo đơn</button>
            <a href="{{ route('staff.orders.index') }}" class="cafe-btn-secondary">Quay lại</a>
        </div>
    </form>
</div>
@endsection
