<?php

namespace Momotombo\NativephpAppearance;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the appearance service for Laravel auto-discovery.
 */
class AppearanceServiceProvider extends ServiceProvider
{
    /**
     * Bind one application-local Appearance service instance.
     */
    public function register(): void
    {
        $this->app->singleton(Appearance::class, function () {
            return new Appearance;
        });
    }
}
