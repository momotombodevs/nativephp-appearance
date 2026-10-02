<?php

beforeEach(function () {
    $this->pluginPath = dirname(__DIR__);
    $this->manifestPath = $this->pluginPath.'/nativephp.json';
});

describe('Plugin manifest', function () {
    it('has valid metadata and startup initializers', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true, 512, JSON_THROW_ON_ERROR);

        expect($manifest)->toHaveKeys(['name', 'namespace', 'bridge_functions', 'platforms']);
        expect($manifest['name'])->toBe('momotombo/nativephp-appearance');
        expect($manifest['namespace'])->toBe('Appearance');
        expect($manifest['version'])->toBe('1.1.0');
        expect($manifest['homepage'])->toBe('https://github.com/momotombodevs/nativephp-appearance');
        expect($manifest['repository'])->toBe('https://github.com/momotombodevs/nativephp-appearance');
        expect($manifest['hooks'])->toBe([]);
        expect($manifest['android']['init_function'])->toBe('dev.momotombo.plugins.nativephp_appearance.initializeAppearance');
        expect($manifest['ios']['init_function'])->toBe('initializeAppearance');
    });

    it('declares matching bridge functions', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $kotlin = file_get_contents($this->pluginPath.'/resources/android/AppearanceFunctions.kt');
        $swift = file_get_contents($this->pluginPath.'/resources/ios/AppearanceFunctions.swift');

        expect($manifest['bridge_functions'])->toHaveCount(2);
        expect($kotlin)->toContain('package dev.momotombo.plugins.nativephp_appearance');
        expect($kotlin)->toContain('object AppearanceFunctions');
        expect($kotlin)->toContain('fun initializeAppearance');
        expect($swift)->toContain('enum AppearanceFunctions');
        expect($swift)->toContain('func initializeAppearance');

        foreach ($manifest['bridge_functions'] as $function) {
            expect($kotlin)->toContain('class '.basename(str_replace('.', '/', $function['android'])));
            expect($swift)->toContain('class '.basename(str_replace('.', '/', $function['ios'])));
        }
    });
});

describe('Package files', function () {
    it('has no obsolete generated API files', function () {
        expect(file_exists($this->pluginPath.'/resources/js/appearance.js'))->toBeFalse();
        expect(file_exists($this->pluginPath.'/src/Events/AppearanceCompleted.php'))->toBeFalse();
        expect(file_exists($this->pluginPath.'/src/Commands/CopyAssetsCommand.php'))->toBeFalse();
        expect(file_exists($this->pluginPath.'/resources/boost/guidelines/core.blade.php'))->toBeFalse();
    });

    it('uses the supported NativePHP Mobile baseline', function () {
        $composer = json_decode(file_get_contents($this->pluginPath.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        expect($composer['require']['php'])->toBe('^8.4');
        expect($composer['require']['nativephp/mobile'])->toBe('^4.3');
    });
});
