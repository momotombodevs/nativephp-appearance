<?php

namespace Momotombo\NativephpAppearance;

use InvalidArgumentException;
use Native\Mobile\Facades\System;
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
     * @param  (callable(string, string): string)|null  $bridge
     */
    public function __construct(private readonly ?\Closure $bridge = null) {}

    /**
     * Persist and apply an application appearance mode on the native side.
     *
     * Outside a NativePHP runtime, validation still runs but no bridge call is
     * attempted. This keeps command-line and unit-test usage deterministic.
     *
     * @throws InvalidArgumentException When the mode is not supported.
     */
    public function set(AppearanceMode|string $mode): void
    {
        $mode = $this->mode($mode);

        if (! $this->bridgeIsAvailable()) {
            return;
        }

        $response = $this->call('Appearance.Set', ['mode' => $mode->value], allowEmpty: true);
        $this->validateCommandResponse($response, 'Appearance.Set');
    }

    /**
     * Return the persisted user preference selected by the user.
     *
     * This may differ from the effective mode while the platform is changing
     * appearance or before the native application has finished updating.
     */
    public function preference(): ?AppearanceMode
    {
        if (! $this->bridgeIsAvailable()) {
            return null;
        }

        $response = $this->call('Appearance.Get', []);

        return $this->decodeMode($response, 'Appearance.Get');
    }

    /**
     * Return the persisted preference as the original string API.
     */
    public function get(): ?string
    {
        return $this->preference()?->value;
    }

    /**
     * Return the effective native appearance, independent of the preference.
     *
     * NativePHP's System facade is the source of truth for what is currently
     * rendered. The result is always `light` or `dark`.
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
     * Normalize and validate the public mode input before crossing the bridge.
     */
    private function mode(AppearanceMode|string $mode): AppearanceMode
    {
        $resolved = AppearanceMode::tryFromValue($mode);

        if ($resolved === null) {
            throw new InvalidArgumentException('Appearance must be system, light, or dark.');
        }

        return $resolved;
    }

    private function bridgeIsAvailable(): bool
    {
        if ($this->bridge !== null) {
            return true;
        }

        if (! function_exists('nativephp_call')) {
            return false;
        }

        if (function_exists('app') && (app()->runningUnitTests() || app()->environment('testing'))) {
            return class_exists(FakeBridge::class)
                && FakeBridge::current() !== null;
        }

        return true;
    }

    /**
     * @param  array<string, string>  $parameters
     */
    private function call(string $method, array $parameters, bool $allowEmpty = false): string
    {
        $payload = json_encode($parameters, JSON_THROW_ON_ERROR);

        if ($this->bridge !== null) {
            $response = ($this->bridge)($method, $payload);
        } else {
            $response = nativephp_call($method, $payload);
        }

        if ($response === null && $allowEmpty) {
            return '';
        }

        if (! is_string($response) || ($response === '' && ! $allowEmpty)) {
            throw new RuntimeException("{$method} returned an empty bridge response.");
        }

        return $response;
    }

    private function validateCommandResponse(string $response, string $method): void
    {
        if ($response === '') {
            return;
        }

        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException("{$method} returned invalid JSON.", previous: $exception);
        }

        if (is_array($decoded) && ($decoded['status'] ?? null) === 'error') {
            throw new RuntimeException($decoded['message'] ?? "{$method} failed.");
        }
    }

    private function decodeMode(string $response, string $method): AppearanceMode
    {
        try {
            $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException("{$method} returned invalid JSON.", previous: $exception);
        }

        if (! is_array($decoded) || ! array_key_exists('mode', $decoded) || ! is_string($decoded['mode'])) {
            throw new RuntimeException("{$method} returned an invalid mode payload.");
        }

        $mode = AppearanceMode::tryFrom($decoded['mode']);

        if ($mode === null) {
            throw new RuntimeException("{$method} returned an unsupported appearance mode.");
        }

        return $mode;
    }
}
