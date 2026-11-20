@props(['name' => 'grid', 'size' => 20])
<svg {{ $attributes->merge(['width' => $size, 'height' => $size, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>
    @switch($name)
        @case('coffee') <path d="M6 8h12v7a6 6 0 0 1-12 0V8ZM18 9h2a2 2 0 0 1 0 4h-2M4 21h17M9 2v3M14 2v3"/> @break
        @case('grid') <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/> @break
        @case('bag') <path d="M4 8h16l-1 13H5L4 8ZM9 10V6a3 3 0 0 1 6 0v4"/> @break
        @case('layers') <path d="m12 3 9 5-9 5-9-5 9-5ZM3 12l9 5 9-5M3 16l9 5 9-5"/> @break
        @case('tag') <path d="M12 3H4v8l10 10 7-7L11 4Z"/><circle cx="7.5" cy="7.5" r="1"/> @break
        @case('gift') <rect x="3" y="8" width="18" height="13" rx="2"/><path d="M12 8v13M3 12h18M12 8C7 8 6 3 9 3c2 0 3 5 3 5ZM12 8c5 0 6-5 3-5-2 0-3 5-3 5Z"/> @break
        @case('receipt') <path d="M5 3h14v18l-3-2-4 2-4-2-3 2V3ZM9 8h6M9 12h6"/> @break
        @case('table') <path d="M3 9h18M5 9V5h14v4M6 9v11M18 9v11M6 15h12"/> @break
        @case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/> @break
        @case('chef') <path d="M6 15a4 4 0 0 1 0-8 6 6 0 0 1 12 0 4 4 0 0 1 0 8v6H6v-6ZM6 17h12"/> @break
        @case('box') <path d="m12 2 9 5-9 5-9-5 9-5ZM3 7v10l9 5 9-5V7M12 12v10"/> @break
        @case('users') <circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M17 5a3 3 0 0 1 0 6M17 14a5 5 0 0 1 4 5v1"/> @break
        @case('chart') <path d="M3 20h18M5 16V9M10 16V4M15 16v-6M20 16V7"/> @break
        @case('search') <circle cx="11" cy="11" r="7"/><path d="m16 16 5 5"/> @break
        @case('bell') <path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/> @break
        @case('menu') <path d="M4 6h16M4 12h16M4 18h16"/> @break
        @case('close') <path d="M5 5l14 14M19 5 5 19"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('arrow') <path d="M5 12h14m-6-6 6 6-6 6"/> @break
        @case('logout') <path d="M9 21H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5M16 17l5-5-5-5M21 12H9"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l4 2"/> @break
        @case('wallet') <rect x="3" y="5" width="18" height="15" rx="2"/><path d="M16 12h5M3 9h18"/> @break
        @case('edit') <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"/> @break
        @case('trash') <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/> @break
        @case('eye') <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/> @break
        @case('check') <polyline points="20 6 9 17 4 12"/> @break
        @case('upload') <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/> @break
        @case('filter') <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/> @break
        @default <circle cx="12" cy="12" r="9"/><path d="M12 8v4m0 4h.01"/>
    @endswitch
</svg>
