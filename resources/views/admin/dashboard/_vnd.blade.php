@php
    [$whole, $fraction] = explode('.', $amount);
    $groupedWhole = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $whole);
@endphp
{{ $groupedWhole }},{{ $fraction }} VNĐ
