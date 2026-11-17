<?php

namespace App\Providers;

use App\Models\OrderItem;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('customer-orders', fn (Request $request): array => [
            Limit::perMinute(5)->by('session:'.hash('sha256', $request->session()->getId())),
            Limit::perMinute(20)->by('ip:'.hash('sha256', (string) $request->ip())),
        ]);
        Relation::enforceMorphMap([
            'order_item' => OrderItem::class,
        ]);
    }
}
