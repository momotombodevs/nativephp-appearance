<?php

namespace Momotombo\NativephpAppearance;

use InvalidArgumentException;
use Native\Mobile\Facades\System;
use Native\Mobile\System as NativeSystem;
use Native\Mobile\Testing\FakeBridge;
use RuntimeException;

/**
 * Application-local appearance preference and native bridge.
 *
 * The preference is owned by the native platform. PHP validates requests and
 * translates bridge responses; it does not persist a second copy of the
 * selected mode.
 */
class Appearance
{
    /**
     * @param  (\Closure(string, string): (string|null))|null  $bridge  Optional bridge override, used by tests.
     */
    public function __construct(private readonly ?\Closure $bridge = null) {}

    /**
     * Persist and apply an application appearance mode on the native side.
     *
     * Outside a NativePHP runtime (web preview, Artisan, unit tests without a
     * FakeBridge), validation still runs but no bridge call is attempted.
     *
     * @throws InvalidArgumentException When the mode is not supported.
     * @throws RuntimeException When the native side rejects the request or the plugin is not compiled into the app.
     */
    public function set(AppearanceMode|string $mode): void
    {
        $mode = $this->mode($mode);

        if (! $this->bridgeIsAvailable()) {
            return;
        }

        $response = $this->call('Appearance.Set', ['mode' => $mode->value]);

        if ($response === null) {
            return;
        }

        $this->decode($response, 'Appearance.Set');
        $this->rememberEffective($mode);
    }

    /**
     * Return the persisted preference selected by the user.
     *
     * Returns `null` outside a NativePHP runtime. On a device the native side
     * defaults to `system` until the user picks something else.
     *
     * @throws RuntimeException When the native side fails or returns an unexpected payload.
     */
    public function preference(): ?AppearanceMode
    {
        if (! $this->bridgeIsAvailable()) {
            return null;
        }

        $response = $this->call('Appearance.Get', []);

        if ($response === null) {
            return null;
        }

        $decoded = $this->decode($response, 'Appearance.Get');

        if (! isset($decoded['mode']) || ! is_string($decoded['mode'])) {
            throw new RuntimeException('Appearance.Get returned an invalid mode payload.');
        }

        return AppearanceMode::tryFrom($decoded['mode'])
            ?? throw new RuntimeException('Appearance.Get returned an unsupported appearance mode.');
    }

    /**
     * Return the persisted preference as a string: `system`, `light`, `dark`, or `null`.
     */
    public function get(): ?string
    {
        return $this->preference()?->value;
    }

    /**
     * Return the appearance NativePHP is currently rendering: `light` or `dark`.
     *
     * NativePHP's System facade is the source of truth. Off the device it
     * falls back to `light`.
     *
     * @return 'light'|'dark'
     */
    public function effective(): string
    {
        $appearance = System::appearance();

        if (! in_array($appearance, ['light', 'dark'], true)) {
            throw new RuntimeException('NativePHP returned an invalid effective appearance.');
        }

        return $appearance;
    }

    /**
     * Whether NativePHP is currently rendering the dark appearance.
     */
    public function isDark(): bool
    {
        return $this->effective() === 'dark';
    }

    /**
     * Normalize and validate the public mode input before crossing the bridge.
     */
    private function mode(AppearanceMode|string $mode): AppearanceMode
    {
        return AppearanceMode::tryFromValue($mode)
            ?? throw new InvalidArgumentException('Appearance must be system, light, or dark.');
    }

    /**
     * Whether a bridge call can reach a device, the Jump app, or a test FakeBridge.
     *
     * `nativephp_call()` alone is not a reliable signal: NativePHP Mobile
     * defines a PHP fallback for it on every machine, which would otherwise
     * try to reach a Jump device from `php artisan serve` or Tinker.
     */
    private function bridgeIsAvailable(): bool
    {
        if ($this->bridge !== null) {
            return true;
        }

        if (! function_exists('nativephp_call')) {
            return false;
        }

        if ($this->fakeBridgeIsBound()) {
            return true;
        }

        if (function_exists('app') && app()->runningUnitTests()) {
            return false;
        }

        return (bool) getenv('NATIVEPHP_RUNNING')
            || (function_exists('config') && (bool) config('nativephp-internal.running'))
            || getenv('JUMP_BRIDGE_PORT') !== false;
    }

    private function fakeBridgeIsBound(): bool
    {
        return class_exists(FakeBridge::class) && FakeBridge::current() !== null;
    }

    /**
     * Call the bridge. Returns `null` only when a test FakeBridge has no scripted response.
     *
     * @param  array<string, string>  $parameters
     */
    private function call(string $method, array $parameters): ?string
    {
        $payload = json_encode((object) $parameters, JSON_THROW_ON_ERROR);

        $response = $this->bridge !== null
            ? ($this->bridge)($method, $payload)
            : nativephp_call($method, $payload);

        if ($response === null && $this->bridge === null && $this->fakeBridgeIsBound()) {
            return null;
        }

        if ($response === null) {
            throw new RuntimeException(
                "{$method} is not available. Register momotombo/nativephp-appearance and rebuild the native app."
            );
        }

        if (! is_string($response) || $response === '') {
            throw new RuntimeException("{$method} returned an empty bridge response.");
        }

        return $response;
    }

    /**
     * Decode a bridge response and surface native errors with their own message.
     *
     * @return array<string, mixed>
     */
    private function decode(string $response, string $method): array
    {
        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException("{$method} returned invalid JSON.", previous: $exception);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException("{$method} returned an invalid payload.");
        }

        if (($decoded['status'] ?? null) === 'error') {
            $message = is_string($decoded['message'] ?? null) ? $decoded['message'] : "{$method} failed.";

            throw new RuntimeException($message);
        }

        return $decoded;
    }

    /**
     * Keep `System::appearance()` in sync right after an explicit light/dark
     * choice, instead of waiting for the native AppearanceChanged event.
     * `system` is resolved by the platform, so that case waits for the event.
     */
    private function rememberEffective(AppearanceMode $mode): void
    {
        if ($mode !== AppearanceMode::System && method_exists(NativeSystem::class, 'rememberAppearance')) {
            NativeSystem::rememberAppearance($mode->value);
        }
    }
}
