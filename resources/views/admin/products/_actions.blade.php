<div class="v5-actions">
    <a href="{{ route('admin.products.edit', $product) }}" class="cafe-btn-secondary" aria-label="Sửa sản phẩm {{ $product->name }}"><x-internal.icon name="edit" size="16" /> Sửa</a>
    <details class="v5-row-actions"><summary>Thao tác</summary><div class="v5-row-actions__content">
        <form method="POST" action="{{ route('admin.products.toggle-status', $product) }}">@csrf @method('PATCH')<button type="submit" class="cafe-btn-secondary">{{ $product->status === 'available' ? 'Đánh dấu tạm hết' : 'Đánh dấu có sẵn' }}</button></form>
        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">@csrf @method('DELETE')<button type="submit" class="cafe-btn-icon cafe-btn-icon--danger" aria-label="Xóa sản phẩm {{ $product->name }}"><x-internal.icon name="trash" size="16" /></button></form>
    </div></details>
</div>
