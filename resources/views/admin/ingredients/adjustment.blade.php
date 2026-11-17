@extends('layouts.internal')
@section('title', 'Điều chỉnh và kiểm kê kho')
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <div class="sneat-page-title">
        <div><h1 id="page-title">Điều chỉnh / kiểm kê · {{ $ingredient->name }}</h1>
            <p>Tồn hệ thống: {{ $ingredient->current_stock }} {{ $ingredient->unit }}. Không quy đổi đơn vị.</p>
        </div>
        <x-public.button href="{{ route('admin.ingredients.history', $ingredient) }}" variant="outline">Lịch sử kho</x-public.button>
    </div>
    <form method="POST" action="{{ route('admin.ingredients.adjustment.store', $ingredient) }}" class="category-panel category-form" onsubmit="return confirm('Xác nhận ghi phiếu? Tồn kho sẽ thay đổi và lịch sử không được sửa/xóa.');">
        @csrf
        <input type="hidden" name="operation_token" value="{{ is_string(old('operation_token')) ? old('operation_token') : $operationToken }}">
        @error('operation_token')<p role="alert" class="category-field__error">{{ $message }}</p>@enderror
        <p><a href="{{ route('admin.ingredients.adjustment.create', $ingredient) }}">Mở phiếu mới / cập nhật tồn để kiểm kê lại</a></p>
        <div class="category-field">
            <label for="mode">Nghiệp vụ *</label>
            <select id="mode" name="mode" required aria-describedby="mode-help @error('mode') mode-error @enderror" @error('mode') aria-invalid="true" @enderror>
                <option value="">Chọn nghiệp vụ</option>
                @foreach (['increase' => 'Điều chỉnh tăng', 'decrease' => 'Điều chỉnh giảm', 'waste' => 'Hao hụt / hư hỏng', 'stocktake' => 'Kiểm kê — nhập tồn thực tế'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('mode') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p id="mode-help">Tăng/giảm/hao hụt: nhập lượng biến động lớn hơn 0. Kiểm kê: nhập tổng tồn thực tế (được bằng 0); hệ thống tính chênh lệch. Tồn thay đổi trong lúc kiểm kê thì phải mở lại phiếu.</p>
            @error('mode')<p id="mode-error" class="category-field__error">{{ $message }}</p>@enderror
        </div>
        <div class="category-field">
            <label for="quantity">Số lượng theo nghiệp vụ ({{ $ingredient->unit }}) *</label>
            <input id="quantity" name="quantity" type="number" min="0" max="999999999.999" step="0.001" required value="{{ is_scalar(old('quantity')) ? old('quantity') : '' }}" @error('quantity') aria-invalid="true" aria-describedby="quantity-error" @enderror>
            @error('quantity')<p id="quantity-error" class="category-field__error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="category-field">
            <label for="note">Lý do / kết quả kiểm kê *</label>
            <textarea id="note" name="note" rows="4" maxlength="1000" required @error('note') aria-invalid="true" aria-describedby="note-error" @enderror>{{ is_scalar(old('note')) ? old('note') : '' }}</textarea>
            @error('note')<p id="note-error" class="category-field__error">{{ $message }}</p>@enderror
        </div>
        <p>Admin ghi phiếu là người xác nhận. Phiếu đã ghi chỉ xem lại trong lịch sử; nếu sai, lập phiếu điều chỉnh mới có lý do.</p>
        <div class="category-form-actions">
            <x-public.button type="submit">Xác nhận ghi phiếu</x-public.button>
            <x-public.button href="{{ route('admin.ingredients.index') }}" variant="outline">Hủy</x-public.button>
        </div>
    </form>
</section>
@endsection
