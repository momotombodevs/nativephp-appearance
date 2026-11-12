# Changelog

All notable changes to `momotombo/nativephp-appearance` are documented here.

## 1.2.0 - 2026-11-12

### Added

- `AppearanceMode::label()`, `AppearanceMode::description()`, and `AppearanceMode::options()` for settings screens, translated to English and Spanish. Publish them with `php artisan vendor:publish --tag=nativephp-appearance-lang` to change the wording or add languages.
- `Appearance::isDark()` shortcut for the effective appearance.
- `Appearance::effective()` reflects an explicit `light` or `dark` choice immediately, without waiting for NativePHP's `AppearanceChanged` event.

### Fixed

- Off the device (`php artisan serve`, Tinker, queue workers), `set()` and `preference()` no longer try to reach a Jump device and throw `No device connected`. They are no-ops, as documented.
- `set()` no longer succeeds silently when the plugin is not compiled into the native app. Both `set()` and `preference()` now throw a `RuntimeException` that says to register the plugin and rebuild.
- Native error messages from `Appearance.Get` are surfaced instead of a generic "invalid mode payload".
- `Appearance.Get` is called with a JSON object (`{}`) instead of an empty array (`[]`).
- iOS: the stored preference is applied when a window becomes visible, before its first frame, so the app no longer flashes the system appearance at launch. Windows created later (alerts, new scenes) also follow the preference.
- iOS and Android: corrupt stored values fall back to `system` instead of being returned to PHP.
- Android: `Appearance.Set` returns an `unsupported` error below API 31 instead of reporting success without applying anything.

## 1.1.0

- Initial public release with `set()`, `get()`, `preference()`, and `effective()`.
