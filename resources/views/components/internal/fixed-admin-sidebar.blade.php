@if(auth('web')->user()?->role === 'admin' && auth('web')->user()?->status === 'active')
<div class="fixed-admin-sidebar" data-fixed-admin-sidebar>
    @foreach(config('admin_navigation') as $group)
        <section class="fixed-nav-group" aria-labelledby="admin-nav-{{ $group['id'] }}">
            <h2 id="admin-nav-{{ $group['id'] }}" class="sneat-nav-section">{{ $group['label'] }}</h2>
            @foreach($group['items'] as $item)
                @php($active = request()->routeIs(...$item['active']))
                <a href="{{ route($item['route']) }}" class="sneat-nav-link {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
                    <x-internal.icon :name="$item['icon']" size="19" /><span>{{ $item['label'] }}</span>
                </a>
            @endforeach
            @if(isset($group['hint']))<p class="fixed-nav-hint">{{ $group['hint'] }}</p>@endif
        </section>
    @endforeach
</div>
@endif
