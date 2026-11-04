<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class GoogleMapsDisplayTest extends TestCase
{
    public function test_approved_campus_links_are_visible_without_embed(): void
    {
        config(['maps.name' => 'Khuôn viên HUIT', 'maps.address' => '140 Lê Trọng Tấn', 'maps.embed_url' => '']);
        $html = Blade::render('<x-public.footer />');
        $this->assertStringContainsString('chưa phải điểm ghim riêng của quán', $html);
        $this->assertStringContainsString('https://maps.app.goo.gl/9dnXkvFtu8GJz4mL9', $html);
        $this->assertStringContainsString('destination=10.8061539%2C106.6286656', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function test_missing_location_omits_map_and_navigation(): void
    {
        config(['maps.name' => '', 'maps.address' => '', 'maps.embed_url' => 'https://www.google.com/maps/embed?pb=fixture']);
        $html = Blade::render('<x-public.shop-location />');
        $this->assertStringContainsString('Liên hệ cửa hàng', $html);
        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('href=', $html);
    }

    public function test_embed_is_lazy_and_address_is_escaped(): void
    {
        config(['maps.name' => 'Campus', 'maps.address' => '<script>alert(1)</script>',
            'maps.embed_url' => 'https://www.google.com/maps/embed?pb=fixture']);
        $html = Blade::render('<x-public.shop-location />');
        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
        $this->assertStringContainsString('title="Bản đồ điểm đến khuôn viên HUIT"', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_untrusted_embed_urls_are_rejected_without_losing_links(): void
    {
        config(['maps.name' => 'Campus', 'maps.address' => 'Owner supplied address']);
        foreach (['https://evil.test/maps/embed?pb=x', 'javascript:alert(1)',
            'https://www.google.com.evil.test/maps/embed?pb=x', 'https://user@www.google.com/maps/embed?pb=x',
            'https://www.google.com/maps/embed?pb[]=x', 'https://www.google.com/maps/embed?pb=x&key=secret',
            'http://www.google.com/maps/embed?pb=x', 'https://www.google.com/maps/embed/v1/place?key=x'] as $url) {
            config(['maps.embed_url' => $url]);
            $html = Blade::render('<x-public.shop-location />');
            $this->assertStringNotContainsString('<iframe', $html);
            $this->assertStringNotContainsString('secret', $html);
            $this->assertStringContainsString('Xem trên Google Maps', $html);
        }
    }
}
