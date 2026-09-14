@props([
    'href' => null,
    'variant' => 'primary',
    'type' => 'button',
])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class(['coffee-button', "coffee-button--{$variant}"]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['coffee-button', "coffee-button--{$variant}"]) }}>{{ $slot }}</button>
@endif