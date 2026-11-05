@extends('layouts.internal')
@section('title', 'Tạo đơn hàng')
@section('content')
    <section class="category-page" aria-labelledby="page-title">
        <div class="category-heading"><div><p class="section-heading__eyebrow">Maison du Café · Nhân viên</p><h1 id="page-title">Tạo đơn hàng</h1><p>Tạo đơn tại chỗ hoặc đơn mang đi. Món sẽ được thêm ở bước tiếp theo.</p></div></div>
        <div class="category-panel"><form method="POST" action="{{ route('staff.orders.store') }}" class="category-form">@csrf
            <div class="category-fields">
                <div class="category-field"><label for="order_type">Loại đơn</label><select id="order_type" name="order_type" required @error('order_type') aria-invalid="true" @enderror><option value="dine_in" @selected(old('order_type', 'dine_in') === 'dine_in')>Tại chỗ</option><option value="takeaway" @selected(old('order_type') === 'takeaway')>Mang đi</option></select>@error('order_type')<p class="category-field__error">{{ $message }}</p>@enderror</div>
                <div class="category-field"><label for="table_id">Bàn</label><select id="table_id" name="table_id" @error('table_id') aria-invalid="true" @enderror><option value="">Chọn bàn cho đơn tại chỗ</option>@foreach ($tables as $table)<option value="{{ $table->id }}" @selected((string) old('table_id') === (string) $table->id)>{{ $table->table_code }}{{ $table->table_name ? ' · '.$table->table_name : '' }} ({{ $table->area?->name }})</option>@endforeach</select><p class="category-field__hint">Đơn mang đi không chọn bàn.</p>@error('table_id')<p class="category-field__error">{{ $message }}</p>@enderror</div>
                <div class="category-field"><label for="customer_id">Khách hàng</label><select id="customer_id" name="customer_id" @error('customer_id') aria-invalid="true" @enderror><option value="">Khách vãng lai</option>@foreach ($customers as $customer)<option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name ?? 'Không tên' }}{{ $customer->phone ? ' · '.$customer->phone : '' }}</option>@endforeach</select>@error('customer_id')<p class="category-field__error">{{ $message }}</p>@enderror</div>
                <div class="category-field"><label for="note">Ghi chú</label><textarea id="note" name="note" rows="4" maxlength="65535" @error('note') aria-invalid="true" @enderror>{{ old('note') }}</textarea>@error('note')<p class="category-field__error">{{ $message }}</p>@enderror</div>
            </div>
            @error('order')<p class="category-notice category-notice--error" role="alert">{{ $message }}</p>@enderror
            <div class="category-form-actions"><x-public.button type="submit">Tạo đơn</x-public.button><x-public.button href="{{ route('staff.orders.index') }}" variant="outline">Quay lại</x-public.button></div>
        </form></div>
    </section>
@endsection
