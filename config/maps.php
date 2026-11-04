<?php

return [
    // Share > Embed a map, plus Maps URLs. No Google Cloud API key required.
    // Populate only after the owner verifies the exact shop location.
    'name' => env('GOOGLE_MAPS_SHOP_NAME', 'Khuôn viên HUIT'),
    'address' => env('GOOGLE_MAPS_ADDRESS', '140 Lê Trọng Tấn, Tây Thạnh, Hồ Chí Minh, Việt Nam'),
    'place_id' => env('GOOGLE_MAPS_PLACE_ID', ''),
    // Store only the src URL copied from Google's iframe, never arbitrary HTML.
    'embed_url' => env('GOOGLE_MAPS_EMBED_URL', ''),
    // Owner-approved campus destination; not the café's own map pin.
    'view_url' => 'https://maps.app.goo.gl/9dnXkvFtu8GJz4mL9',
    'destination' => '10.8061539,106.6286656',
];
