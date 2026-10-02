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
