<?php

namespace Tests\Feature;

use Illuminate\Support\Env;
use Tests\TestCase;

class GoogleMapsConfigurationTest extends TestCase
{
    public function test_missing_location_has_no_fabricated_fallback_or_key(): void
    {
        $this->withLocationEnvironment([], function () {
            $maps = require config_path('maps.php');
            $this->assertSame(['name' => '', 'address' => '', 'place_id' => '', 'embed_url' => ''], array_intersect_key($maps, array_flip(['name', 'address', 'place_id', 'embed_url'])));
        });
    }

    public function test_environment_values_load_into_laravel_configuration(): void
    {
        // Synthetic strings for configuration testing only, never a real location.
        $values = ['name' => 'Configuration fixture', 'address' => 'Fixture & Unicode: cà phê',
            'place_id' => 'fixture-id', 'embed_url' => ''];
        $this->withLocationEnvironment($values, function () use ($values) {
            $this->app['config']->set('maps', require config_path('maps.php'));
            $this->assertSame($values, array_intersect_key(config('maps'), $values));
        });
    }

    private function withLocationEnvironment(array $values, callable $check): void
    {
        $variables = ['name' => 'GOOGLE_MAPS_SHOP_NAME', 'address' => 'GOOGLE_MAPS_ADDRESS',
            'place_id' => 'GOOGLE_MAPS_PLACE_ID', 'embed_url' => 'GOOGLE_MAPS_EMBED_URL'];
        $repository = Env::getRepository();
        $original = [];
        try {
            foreach ($variables as $field => $variable) {
                $original[$variable] = $repository->get($variable);
                $repository->set($variable, $values[$field] ?? '');
            }
            $check();
        } finally {
            foreach ($original as $variable => $value) {
                $value === null ? $repository->clear($variable) : $repository->set($variable, $value);
            }
        }
    }
}
