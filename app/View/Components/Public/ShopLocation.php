<?php

namespace App\View\Components\Public;

use Illuminate\View\Component;
use Illuminate\View\View;

class ShopLocation extends Component
{
    public string $name;

    public string $address;

    public ?string $embedUrl = null;

    public string $directionsUrl;

    public function __construct()
    {
        $this->name = trim((string) config('maps.name'));
        $this->address = trim((string) config('maps.address'));
        $this->directionsUrl = 'https://www.google.com/maps/dir/?'.http_build_query([
            'api' => 1, 'destination' => config('maps.destination'),
        ], '', '&', PHP_QUERY_RFC3986);
        $url = trim((string) config('maps.embed_url'));
        $parts = parse_url($url);
        if (is_array($parts) && ($parts['scheme'] ?? '') === 'https'
            && ($parts['host'] ?? '') === 'www.google.com'
            && ($parts['path'] ?? '') === '/maps/embed'
            && ! isset($parts['user']) && ! isset($parts['pass'])
            && ! isset($parts['port']) && ! isset($parts['fragment'])) {
            parse_str($parts['query'] ?? '', $query);
            if (is_string($query['pb'] ?? null) && $query['pb'] !== '' && count($query) === 1) {
                $this->embedUrl = $url;
            }
        }
    }

    public function render(): View
    {
        return view('components.public.shop-location');
    }
}
