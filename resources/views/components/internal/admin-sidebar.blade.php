<div class="dynamic-admin-sidebar" data-admin-sidebar>
    @include('components.internal.admin-sidebar-nodes', ['nodes' => $tree])
    @if ($recovery)
        @if ($unavailable)
            <p class="sneat-nav-section" role="status">Điều hướng tạm thời chưa sẵn sàng.</p>
        @elseif ($tree === [])
            <p class="sneat-nav-section">Chưa có menu hiển thị.</p>
        @endif
        <a href="{{ route('admin.menus.index') }}" class="sneat-nav-link {{ $recoveryActive ? 'is-active' : '' }}" @if($recoveryActive) aria-current="page" @endif>
            <x-internal.icon name="menu" size="19" /><span>Quản lý menu</span>
        </a>
    @endif
</div>
