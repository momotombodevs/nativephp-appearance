<?php

namespace Momotombo\NativephpAppearance\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void set(\Momotombo\NativephpAppearance\AppearanceMode|string $mode)
 * @method static string|null get()
 * @method static \Momotombo\NativephpAppearance\AppearanceMode|null preference()
 * @method static string effective()
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
