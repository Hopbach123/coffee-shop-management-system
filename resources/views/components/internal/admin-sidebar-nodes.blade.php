@foreach ($nodes as $node)
    @if ($node['type'] === 'group')
        <div class="dynamic-admin-sidebar__group {{ $node['active'] ? 'is-active-group' : '' }}">
            <button type="button" class="sneat-nav-link dynamic-admin-sidebar__toggle" aria-expanded="true" aria-controls="sidebar-group-{{ $node['id'] }}" data-sidebar-group data-default-open="{{ $node['active'] ? '1' : '0' }}">
                @if($node['icon'])<x-internal.icon :name="$node['icon']" size="19" />@endif
                <span>{{ $node['name'] }}</span><span aria-hidden="true">⌄</span>
            </button>
            <div class="dynamic-admin-sidebar__children" id="sidebar-group-{{ $node['id'] }}">
                @include('components.internal.admin-sidebar-nodes', ['nodes' => $node['children']])
            </div>
        </div>
    @else
        <a href="{{ $node['url'] }}" class="sneat-nav-link {{ $node['active'] ? 'is-active' : '' }}" @if($node['active']) aria-current="page" @endif>
            @if($node['icon'])<x-internal.icon :name="$node['icon']" size="19" />@endif<span>{{ $node['name'] }}</span>
        </a>
    @endif
@endforeach
