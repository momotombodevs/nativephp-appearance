<?php

namespace Momotombo\NativephpAppearance;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the appearance service and its translations for Laravel auto-discovery.
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

    /**
     * Load the mode labels and let apps publish them to customize the wording.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', AppearanceMode::TRANSLATION_NAMESPACE);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/'.AppearanceMode::TRANSLATION_NAMESPACE),
            ], 'nativephp-appearance-lang');
        }
    }
}
