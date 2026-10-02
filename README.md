# NativePHP Appearance

`momotombo/nativephp-appearance` adds an application-local appearance preference to NativePHP Mobile: `system`, `light`, or `dark`.

NativePHP Mobile already reports the effective appearance through `System::appearance()` and emits `AppearanceChanged` when the system changes. This package owns only the explicit preference and its native application. It does not change the device-wide theme or define color tokens.

## Requirements

- PHP 8.4+
- NativePHP Mobile 4.3+
- Android 12+ (API 31)
- iOS 15+

## Installation

```bash
composer require momotombo/nativephp-appearance
php artisan vendor:publish --tag=nativephp-plugins-provider
php artisan native:plugin:register momotombo/nativephp-appearance
php artisan native:plugin:list
php artisan native:plugin:validate
```

Rebuild the native application after installing or changing this plugin. Use `php artisan native:run android` or `php artisan native:run ios` in the application project.

## PHP API

```php
use Momotombo\NativephpAppearance\AppearanceMode;
use Momotombo\NativephpAppearance\Facades\Appearance;

Appearance::set(AppearanceMode::Dark);
Appearance::set('system');

$preference = Appearance::preference(); // AppearanceMode|null
$legacyValue = Appearance::get();       // 'system'|'light'|'dark'|null
$effective = Appearance::effective();   // 'light'|'dark'
```

`preference()` and `get()` return the value selected by the user. `effective()` returns the mode currently rendered by NativePHP, which can differ from the preference while the system is changing or before a native update has completed.

Invalid input throws `InvalidArgumentException`. Invalid or malformed native responses throw `RuntimeException`. Outside a NativePHP runtime, `set()` validates the input and performs no bridge call; reads return `null` for the preference and NativePHP's normal `light` fallback for the effective mode.

## Reacting to system changes

Use NativePHP's event when application state depends on the effective appearance:

```php
use Native\Mobile\Attributes\On;
use Native\Mobile\Events\System\AppearanceChanged;

#[On(AppearanceChanged::class)]
public function appearanceChanged(string $mode): void
{
    // Re-resolve appearance-dependent state here.
}
```

For Native UI screens, define colors in `config/native-ui.php` and use `bg-theme-*`, `text-theme-*`, and `border-theme-*` tokens. This package does not replace NativePHP Mobile UI theming.

## Platform behavior

Android persists the selected mode in application preferences and applies it with `UiModeManager.setApplicationNightMode()`. `system` uses Android's automatic application night mode, which follows Android's automatic night-mode policy. The plugin requires API 31 because that is the minimum for application-local night mode.

iOS persists the preference in `UserDefaults` and applies it to all active windows. `system` uses `UIUserInterfaceStyle.unspecified`, allowing the windows to inherit the system appearance. The startup initializer reapplies the preference when a scene becomes active.

The native bridge contract is intentionally stable:

| Function | Input | Response |
| --- | --- | --- |
| `Appearance.Set` | `{ "mode": "system"|"light"|"dark" }` | `{ "mode": "..." }` |
| `Appearance.Get` | `{}` | `{ "mode": "..." }` |

## Despertá example

Keep the selected preference in the app's settings UI and use `Appearance::effective()` only when PHP needs to choose appearance-dependent behavior. Let NativePHP's `AppearanceChanged` event update any currently mounted component when the device theme changes.

## Validation

```bash
php artisan native:plugin:validate packages/momotombo/nativephp-appearance --no-interaction
```

PHP and manifest tests do not certify native rendering. Validate startup, preference changes, system changes, activity/scene recreation, background/foreground transitions, keyboards, dialogs, sheets, and Native UI chrome on Android 12–15 and iOS 15+.

## License

MIT
