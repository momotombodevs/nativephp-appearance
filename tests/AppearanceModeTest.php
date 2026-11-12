<?php

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Momotombo\NativephpAppearance\AppearanceMode;

/**
 * Bind a real Laravel translator, loading the package translations the same
 * way AppearanceServiceProvider does. `$appLangPath` plays the app's lang/
 * directory, where published overrides live.
 */
function bindTranslator(string $locale, ?string $appLangPath = null): void
{
    $loader = new FileLoader(new Filesystem, $appLangPath ?? __DIR__.'/fixtures/empty-lang');
    $loader->addNamespace(AppearanceMode::TRANSLATION_NAMESPACE, dirname(__DIR__).'/lang');

    $translator = new Translator($loader, $locale);
    $translator->setFallback('en');

    Container::getInstance()->instance('translator', $translator);
}

afterEach(function () {
    Container::setInstance(null);
});

it('returns English labels without a Laravel translator', function () {
    expect(AppearanceMode::options())->toBe([
        'system' => 'System',
        'light' => 'Light',
        'dark' => 'Dark',
    ])->and(AppearanceMode::Dark->description())->toBe('Always dark, whatever your device uses.');
});

it('translates labels and descriptions to the app locale', function () {
    bindTranslator('es');

    expect(AppearanceMode::options())->toBe([
        'system' => 'Sistema',
        'light' => 'Claro',
        'dark' => 'Oscuro',
    ])->and(AppearanceMode::System->description())->toBe('Sigue el tema de tu dispositivo.');
});

it('uses the English labels for locales the package does not ship', function () {
    bindTranslator('fr');

    expect(AppearanceMode::Light->label())->toBe('Light')
        ->and(AppearanceMode::Light->description())->toBe('Always light, whatever your device uses.');
});

it('prefers wording published to the app', function () {
    bindTranslator('es', __DIR__.'/fixtures/lang');

    expect(AppearanceMode::System->label())->toBe('Automático')
        ->and(AppearanceMode::Dark->label())->toBe('Oscuro');
});

it('ships the same keys for every mode in every language', function () {
    $languages = glob(dirname(__DIR__).'/lang/*/appearance.php');
    $values = array_map(fn (AppearanceMode $mode) => $mode->value, AppearanceMode::cases());

    expect($languages)->not->toBeEmpty();

    foreach ($languages as $file) {
        $lines = require $file;

        expect(array_keys($lines))->toBe(['modes', 'descriptions']);
        expect(array_keys($lines['modes']))->toBe($values);
        expect(array_keys($lines['descriptions']))->toBe($values);
    }
});
