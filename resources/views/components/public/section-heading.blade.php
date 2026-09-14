@props([
    'eyebrow' => null,
    'title',
    'align' => 'left',
])

<div {{ $attributes->class(['section-heading', 'section-heading--center' => $align === 'center']) }}>
    @if ($eyebrow)
        <p class="section-heading__eyebrow">{{ $eyebrow }}</p>
    @endif
    <h2>{{ $title }}</h2>
    @if ($slot->isNotEmpty())
        <div class="section-heading__body">{{ $slot }}</div>
    @endif
</div>