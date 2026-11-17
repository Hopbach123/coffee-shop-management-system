@extends('layouts.internal')
@section('title', 'Nhập kho nguyên liệu')
@section('content')
<section class="category-page" aria-labelledby="page-title">
    <div class="sneat-page-title">
        <div><h1 id="page-title">Nhập kho · {{ $ingredient->name }}</h1>
            <p>Tồn hiện tại: {{ $ingredient->current_stock }} {{ $ingredient->unit }}. Nhập theo đơn vị này, không tự quy đổi.</p>
        </div>
        <x-public.button href="{{ route('admin.ingredients.history', $ingredient) }}" variant="outline">Lịch sử kho</x-public.button>
    </div>
    <form method="POST" action="{{ route('admin.ingredients.import.store', $ingredient) }}" class="category-panel category-form">
        @csrf
        <input type="hidden" name="operation_token" value="{{ is_string(old('operation_token')) ? old('operation_token') : $operationToken }}">
        @error('operation_token')<p class="category-field__error" role="alert">{{ $message }}</p><a href="{{ route('admin.ingredients.import.create', $ingredient) }}">Mở phiếu mới</a>@enderror
        <div class="category-field">
            <label for="quantity">Số lượng nhập ({{ $ingredient->unit }}) *</label>
            <input id="quantity" name="quantity" type="number" min="0.001" max="999999999.999" step="0.001" required value="{{ is_scalar(old('quantity')) ? old('quantity') : '' }}" @error('quantity') aria-invalid="true" aria-describedby="quantity-error" @enderror>
            @error('quantity')<p class="category-field__error" id="quantity-error">{{ $message }}</p>@enderror
        </div>
        <div class="category-field">
            <label for="note">Ghi chú / lý do nhập</label>
            <textarea id="note" name="note" rows="3" maxlength="1000" @error('note') aria-invalid="true" aria-describedby="note-error" @enderror>{{ is_scalar(old('note')) ? old('note') : '' }}</textarea>
            @error('note')<p class="category-field__error" id="note-error">{{ $message }}</p>@enderror
        </div>
        <p>Kiểm tra số lượng trước khi lưu. Phiếu đã ghi nhận chỉ được xem trong lịch sử; việc điều chỉnh phải có chứng từ riêng.</p>
        <div class="category-form-actions">
            <x-public.button type="submit">Ghi nhận nhập kho</x-public.button>
            <x-public.button href="{{ route('admin.ingredients.index') }}" variant="outline">Hủy</x-public.button>
        </div>
    </form>
</section>
@endsection
