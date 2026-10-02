<?php

namespace Momotombo\NativephpAppearance;

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

    /**
     * Normalize an enum instance or its wire-format string value.
     */
    public static function tryFromValue(self|string $mode): ?self
    {
        return $mode instanceof self ? $mode : self::tryFrom($mode);
    }
}
