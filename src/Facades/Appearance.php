<?php

namespace Momotombo\NativephpAppearance\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Static access to the application-local appearance service.
 *
 * @method static void set(\Momotombo\NativephpAppearance\AppearanceMode|string $mode)
 * @method static string|null get()
 * @method static \Momotombo\NativephpAppearance\AppearanceMode|null preference()
 * @method static 'light'|'dark' effective()
 * @method static bool isDark()
 *
 * @see \Momotombo\NativephpAppearance\Appearance
 */
class Appearance extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Momotombo\NativephpAppearance\Appearance::class;
    }
}
