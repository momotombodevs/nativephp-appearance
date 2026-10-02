<?php

namespace Momotombo\NativephpAppearance;

enum AppearanceMode: string
{
    case System = 'system';
    case Light = 'light';
    case Dark = 'dark';

    public static function tryFromValue(self|string $mode): ?self
    {
        return $mode instanceof self ? $mode : self::tryFrom($mode);
    }
}
