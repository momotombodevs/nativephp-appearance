<p align="center">
    <a href="https://momotombo.dev">
        <img src="https://raw.githubusercontent.com/momotombodevs/nativephp-appearance/main/art/momotombo-logo.svg" alt="Momotombo Devs" width="120">
    </a>
</p>

<h1 align="center">NativePHP Appearance</h1>

<p align="center">
    Let users pick light, dark, or system appearance for your <a href="https://nativephp.com/mobile">NativePHP Mobile</a> app, on Android and iOS.
</p>

<p align="center">
    <a href="https://packagist.org/packages/momotombo/nativephp-appearance"><img src="https://img.shields.io/packagist/v/momotombo/nativephp-appearance.svg" alt="Latest version"></a>
    <a href="https://packagist.org/packages/momotombo/nativephp-appearance"><img src="https://img.shields.io/packagist/dt/momotombo/nativephp-appearance.svg" alt="Total downloads"></a>
    <a href="LICENSE"><img src="https://img.shields.io/packagist/l/momotombo/nativephp-appearance.svg" alt="License"></a>
</p>

---

`momotombo/nativephp-appearance` adds an in-app appearance setting: `system`, `light`, or `dark`. The choice is stored on the device, applied natively, and restored on the next launch. It only affects your app. The device-wide theme is never touched.

NativePHP Mobile already tells you which appearance is on screen (`System::appearance()`) and fires `AppearanceChanged` when it changes. This plugin adds the missing piece: letting the user override it.

## Apps using this plugin

| App | Description |
| --- | --- |
| [**Despertá**](https://desperta.momotombo.dev/) · [Google Play](https://play.google.com/store/apps/details?id=dev.momotombo.desperta) | A daily alarm you can only turn off by solving a short three-question challenge. Progress stays on the device and no account is needed. |

Using the plugin in your app? Open a pull request to add it here.

## Features

- Three modes: follow the system, always light, or always dark.
- Persisted natively and reapplied at startup, before PHP boots, so the app opens in the right appearance.
- App-local only. Uses `UiModeManager.setApplicationNightMode()` on Android and `overrideUserInterfaceStyle` on iOS.
- Covers web views, Native UI screens, system dialogs, sheets, and the keyboard.
- Ready-made labels for your settings screen, in English and Spanish.
- Validates input in PHP and on the native side, and turns native errors into exceptions.
- Safe to call off the device: in web preview, Artisan, and tests it validates input and does nothing else.

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | 8.4+ |
| NativePHP Mobile | 4.3+ |
| Android | 12+ (API 31) |
| iOS | 15+ |

Android 12 is the minimum because app-local night mode (`setApplicationNightMode()`) was added in API 31.

## Installation

Install the package:

```bash
composer require momotombo/nativephp-appearance
```

If your app doesn't have `app/Providers/NativeServiceProvider.php` yet, publish it:

```bash
php artisan vendor:publish --tag=nativephp-plugins-provider
```

Register the plugin. NativePHP only compiles plugins you register explicitly:

```bash
php artisan native:plugin:register momotombo/nativephp-appearance
```

Check that it's registered and valid:

```bash
php artisan native:plugin:list
php artisan native:plugin:validate
```

Then rebuild the native app. The plugin ships Kotlin and Swift code, so a PHP reload isn't enough:

```bash
php artisan native:run android
php artisan native:run ios
```

> [!NOTE]
> The Jump app doesn't include third-party native code. Use `native:run` to test this plugin on a device or simulator.

## Usage

```php
use Momotombo\NativephpAppearance\AppearanceMode;
use Momotombo\NativephpAppearance\Facades\Appearance;

// Save and apply the user's choice.
Appearance::set(AppearanceMode::Dark);
Appearance::set('system'); // Strings work too: 'system', 'light', or 'dark'.

// Read the saved choice.
Appearance::preference(); // AppearanceMode::Dark, or null off the device
Appearance::get();        // 'dark', or null off the device

// Read what's on screen right now.
Appearance::effective(); // 'light' or 'dark'
Appearance::isDark();    // true or false
```

### Preference vs. effective appearance

| Method | Returns | Meaning |
| --- | --- | --- |
| `preference()` | `AppearanceMode\|null` | What the user picked. `system` until they pick something else. |
| `get()` | `'system'\|'light'\|'dark'\|null` | Same as `preference()`, as a string. |
| `effective()` | `'light'\|'dark'` | What is rendered. Comes from NativePHP's `System::appearance()`. |
| `isDark()` | `bool` | `effective() === 'dark'`. |

When the preference is `system`, the effective appearance follows the device and can change at any time, for example at sunset. Use the preference for your settings screen, and the effective appearance for logic that depends on what the user sees.

After `set('light')` or `set('dark')`, `effective()` returns the new value right away. After `set('system')`, it updates when NativePHP fires `AppearanceChanged`.

### A settings screen

A Livewire component with three options:

```php
use Illuminate\Validation\Rule;
use Livewire\Component;
use Momotombo\NativephpAppearance\AppearanceMode;
use Momotombo\NativephpAppearance\Facades\Appearance;

class AppearanceSettings extends Component
{
    public string $mode = 'system';

    public function mount(): void
    {
        $this->mode = Appearance::get() ?? 'system';
    }

    public function updatedMode(string $value): void
    {
        $this->validate(['mode' => ['required', Rule::enum(AppearanceMode::class)]]);

        Appearance::set($value);
    }

    public function render()
    {
        return view('livewire.appearance-settings', [
            'modes' => AppearanceMode::cases(),
        ]);
    }
}
```

```blade
<fieldset>
    <legend>Appearance</legend>

    @foreach ($modes as $option)
        <label>
            <input type="radio" wire:model.live="mode" value="{{ $option->value }}">
            {{ $option->label() }}
            <small>{{ $option->description() }}</small>
        </label>
    @endforeach
</fieldset>
```

### Labels and translations

Each mode has a ready-made label and a one-line description, translated to the app's locale:

```php
AppearanceMode::Dark->label();         // 'Dark', or 'Oscuro' in Spanish
AppearanceMode::Dark->description();   // 'Always dark, whatever your device uses.'

AppearanceMode::options();
// ['system' => 'System', 'light' => 'Light', 'dark' => 'Dark']
```

`options()` returns every mode as `value => label`, ready for a `<select>` or a list of radio buttons.

| Mode | English | Spanish |
| --- | --- | --- |
| `system` | System · *Follows your device setting.* | Sistema · *Sigue el tema de tu dispositivo.* |
| `light` | Light · *Always light, whatever your device uses.* | Claro · *Siempre claro, sin importar tu dispositivo.* |
| `dark` | Dark · *Always dark, whatever your device uses.* | Oscuro · *Siempre oscuro, sin importar tu dispositivo.* |

The language comes from `config('app.locale')`. For a language the plugin doesn't ship, Laravel uses your `fallback_locale`, and the plugin falls back to English if that is missing too.

To change the wording or add a language, publish the translations:

```bash
php artisan vendor:publish --tag=nativephp-appearance-lang
```

This copies them to `lang/vendor/nativephp-appearance/`. Edit a file there, or add a folder for a new locale, such as `fr/appearance.php`. Pull requests that add languages are welcome.

### Reacting to appearance changes

NativePHP fires `Native\Mobile\Events\System\AppearanceChanged` when the rendered appearance changes, whether the user changed the device theme or picked a new mode in your app. The event carries the new `mode`: `'light'` or `'dark'`.

In a Native UI component (`NativeComponent`):

```php
use Native\Mobile\Attributes\On;
use Native\Mobile\Events\System\AppearanceChanged;

#[On(AppearanceChanged::class)]
public function appearanceChanged(string $mode): void
{
    // Recompute anything that depends on light or dark.
}
```

In a Livewire component:

```php
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\System\AppearanceChanged;

#[OnNative(AppearanceChanged::class)]
public function appearanceChanged(string $mode): void
{
    // Recompute anything that depends on light or dark.
}
```

Anywhere else, listen to the event:

```php
use Illuminate\Support\Facades\Event;
use Native\Mobile\Events\System\AppearanceChanged;

Event::listen(AppearanceChanged::class, function (AppearanceChanged $event) {
    logger()->info("Appearance is now {$event->mode}");
});
```

### Styling

This plugin decides *which* appearance is active. It doesn't define colors.

- **Native UI:** define colors in `config/native-ui.php` and use the `bg-theme-*`, `text-theme-*`, and `border-theme-*` classes. They follow the active appearance automatically.
- **Web views:** use CSS `prefers-color-scheme` or Tailwind's `dark:` variant. The web view reports the app's appearance (on Android through NativePHP's `DayNight` theme), so these follow the user's choice too.

## Errors

| Situation | Behavior |
| --- | --- |
| Invalid mode, such as `Appearance::set('sepia')` | Throws `InvalidArgumentException` before calling native code. |
| Plugin not registered or app not rebuilt | Throws `RuntimeException`: *"Appearance.Set is not available. Register momotombo/nativephp-appearance and rebuild the native app."* |
| Native code rejects the request | Throws `RuntimeException` with the native message. |
| Invalid response from native code | Throws `RuntimeException`. |
| Off the device: web preview, Artisan, queue workers, tests | `set()` only validates. `preference()` and `get()` return `null`. `effective()` returns `light`. |

## Testing your app

Use NativePHP's `FakeBridge` to script responses and assert calls. Without a `FakeBridge`, the plugin makes no bridge calls in tests.

```php
use Momotombo\NativephpAppearance\AppearanceMode;
use Momotombo\NativephpAppearance\Facades\Appearance;
use Native\Mobile\Testing\FakeBridge;

it('saves the dark preference', function () {
    $bridge = FakeBridge::enable()
        ->respondTo('Appearance.Set', fn (array $params) => ['mode' => $params['mode']])
        ->respondTo('Appearance.Get', ['mode' => 'dark']);

    Appearance::set(AppearanceMode::Dark);

    $bridge->assertCalled('Appearance.Set', fn (array $params) => $params['mode'] === 'dark');
    expect(Appearance::preference())->toBe(AppearanceMode::Dark);
});
```

When a method has no scripted response, `set()` does nothing and `preference()` returns `null`.

## Platform behavior

### Android

- The preference is stored in the app's private `SharedPreferences` (`nativephp_appearance`).
- It's applied with `UiModeManager.setApplicationNightMode()`. `system` maps to `MODE_NIGHT_AUTO`, which follows the device setting.
- Android persists the app's night mode too. The plugin reapplies the stored preference at startup so both stay in sync after a backup restore.
- Changing the mode triggers a configuration change, as when the device theme changes.

### iOS

- The preference is stored in `UserDefaults` under `nativephp_appearance.mode`.
- It's applied by setting `overrideUserInterfaceStyle` on every window. `system` uses `.unspecified`, so windows inherit the device appearance.
- At launch, the preference is applied as each window becomes visible, before its first frame. It's reapplied whenever a scene becomes active.

## Bridge reference

If you call the bridge directly, for example from JavaScript, these are the contracts:

| Function | Parameters | Response |
| --- | --- | --- |
| `Appearance.Set` | `{"mode": "system"}`, `"light"`, or `"dark"` | `{"mode": "dark"}` |
| `Appearance.Get` | `{}` | `{"mode": "system"}` |

Errors use NativePHP's standard shape:

```json
{ "status": "error", "code": "invalid_mode", "message": "Appearance must be system, light, or dark." }
```

| Code | Platform | Meaning |
| --- | --- | --- |
| `invalid_mode` | Android, iOS | `mode` is missing or not `system`, `light`, or `dark`. |
| `unsupported` | Android | The device runs Android 11 or older. |

## Development

```bash
composer install
composer test
```

To validate the manifest from a NativePHP app that loads this plugin through a path repository:

```bash
php artisan native:plugin:validate packages/momotombo/nativephp-appearance
```

See [CHANGELOG.md](CHANGELOG.md) for release notes.

## License

The MIT License (MIT). See [LICENSE](LICENSE).
