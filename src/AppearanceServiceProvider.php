<?php

namespace Momotombo\NativephpAppearance;

use Illuminate\Support\ServiceProvider;

class AppearanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Appearance::class, function () {
            return new Appearance;
        });
    }
}
