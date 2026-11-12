<?php

use Momotombo\NativephpAppearance\Appearance;
use Momotombo\NativephpAppearance\AppearanceMode;

it('validates modes without a native runtime', function () {
    $appearance = new Appearance;

    $appearance->set(AppearanceMode::Light);
    $appearance->set('system');

    expect($appearance->get())->toBeNull();
});

it('rejects unsupported modes before calling the bridge', function () {
    $called = false;
    $appearance = new Appearance(function () use (&$called): string {
        $called = true;

        return '{"mode":"dark"}';
    });

    expect(fn () => $appearance->set('sepia'))
        ->toThrow(InvalidArgumentException::class, 'Appearance must be system, light, or dark.');
    expect($called)->toBeFalse();
});

it('separates the persisted preference from the effective mode', function () {
    $appearance = new Appearance(function (string $method): string {
        return match ($method) {
            'Appearance.Set' => '{"mode":"dark"}',
            'Appearance.Get' => '{"mode":"dark"}',
        };
    });

    $appearance->set(AppearanceMode::Dark);

    expect($appearance->preference())->toBe(AppearanceMode::Dark)
        ->and($appearance->get())->toBe('dark');
});

it('rejects malformed bridge responses', function (string $response, string $message) {
    $appearance = new Appearance(fn (): string => $response);

    expect(fn () => $appearance->preference())
        ->toThrow(RuntimeException::class, $message);
})->with([
    ['not-json', 'Appearance.Get returned invalid JSON.'],
    ['{}', 'Appearance.Get returned an invalid mode payload.'],
    ['{"mode":"sepia"}', 'Appearance.Get returned an unsupported appearance mode.'],
]);

it('sends an empty JSON object when reading the preference', function () {
    $payloads = [];
    $appearance = new Appearance(function (string $method, string $payload) use (&$payloads): string {
        $payloads[$method] = $payload;

        return '{"mode":"system"}';
    });

    expect($appearance->preference())->toBe(AppearanceMode::System)
        ->and($payloads['Appearance.Get'])->toBe('{}');
});

it('surfaces native error messages', function (string $method) {
    $appearance = new Appearance(fn (): string => '{"status":"error","code":"unsupported","message":"Application appearance requires Android 12 (API 31) or newer."}');

    $call = $method === 'set'
        ? fn () => $appearance->set('dark')
        : fn () => $appearance->preference();

    expect($call)->toThrow(RuntimeException::class, 'Application appearance requires Android 12 (API 31) or newer.');
})->with(['set', 'preference']);

it('explains a missing native registration instead of failing silently', function (string $method) {
    $appearance = new Appearance(fn (): ?string => null);

    $call = $method === 'set'
        ? fn () => $appearance->set('dark')
        : fn () => $appearance->preference();

    expect($call)->toThrow(RuntimeException::class, 'Register momotombo/nativephp-appearance and rebuild the native app.');
})->with(['set', 'preference']);

it('updates the cached effective appearance after an explicit light or dark choice', function () {
    $cache = new ReflectionProperty(Native\Mobile\System::class, 'appearance');
    $cache->setValue(null, null);

    $appearance = new Appearance(fn (string $method, string $payload): string => $payload);

    $appearance->set(AppearanceMode::Dark);
    expect($cache->getValue())->toBe('dark');

    $appearance->set(AppearanceMode::System);
    expect($cache->getValue())->toBe('dark');

    $appearance->set('light');
    expect($cache->getValue())->toBe('light');

    $cache->setValue(null, null);
});
