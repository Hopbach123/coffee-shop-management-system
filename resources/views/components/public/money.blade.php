@props(['amount'])
@php
    [$whole, $fraction] = array_pad(explode('.', (string) $amount, 2), 2, '00');
@endphp
{{ preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole) }},{{ str_pad($fraction, 2, '0') }} ₫
