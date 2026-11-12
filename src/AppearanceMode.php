<?php

namespace Momotombo\NativephpAppearance;

use Illuminate\Container\Container;

/**
 * Application-local appearance preference understood by the native bridge.
 *
 * `system` delegates to the platform appearance, while `light` and `dark`
 * override it for this application only.
 */
enum AppearanceMode: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';

    /** Translation namespace registered by AppearanceServiceProvider. */
    public const TRANSLATION_NAMESPACE = 'nativephp-appearance';

    /**
     * Normalize an enum instance or its wire-format string value.
     */
    public static function tryFromValue(self|string $mode): ?self
    {
        return $mode instanceof self ? $mode : self::tryFrom($mode);
    }

    /**
     * Every mode as `value => label`, in display order, for a settings screen.
     *
     * @return array<'system'|'light'|'dark', string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $mode) {
            $options[$mode->value] = $mode->label();
        }

        return $options;
    }

    /**
     * Short name for this mode in the app's locale, such as "Dark" or "Oscuro".
     */
    public function label(): string
    {
        return $this->translate('modes', match ($this) {
            self::System => 'System',
            self::Light => 'Light',
            self::Dark => 'Dark',
        });
    }

    /**
     * One-line explanation of this mode in the app's locale.
     */
    public function description(): string
    {
        return $this->translate('descriptions', match ($this) {
            self::System => 'Follows your device setting.',
            self::Light => 'Always light, whatever your device uses.',
            self::Dark => 'Always dark, whatever your device uses.',
        });
    }

    /**
     * Look up a translation, or return the English text when Laravel's
     * translator is unavailable or the key is missing.
     */
    private function translate(string $group, string $fallback): string
    {
        if (! class_exists(Container::class) || ! Container::getInstance()->bound('translator')) {
            return $fallback;
        }

        $key = self::TRANSLATION_NAMESPACE."::appearance.{$group}.{$this->value}";
        $translation = Container::getInstance()->make('translator')->get($key);

        return is_string($translation) && $translation !== $key ? $translation : $fallback;
    }
}
