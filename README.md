# PHP Language Localizer

A small, dependency-free PHP library for translating and localizing applications: plain PHP locale files, parameter replacement with HTML escaping, CLDR plural rules, gender variants, fallback languages and layered locale directories.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wufr/php-language-localizer.svg)](https://packagist.org/packages/wufr/php-language-localizer)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF)](https://php.net)
[![License](https://img.shields.io/github/license/wUFr/php-language-localizer)](LICENSE.md)

## Features

- **Plain PHP locale files** organized in any folder hierarchy, loaded once per request
- **Parameter replacement** with **HTML escaping by default** and explicit opt-outs for trusted markup
- **Pluralization** with Unicode CLDR categories (`one`, `few`, `many`, …) for 60+ languages, or simple numeric thresholds
- **Gender variants**, also combined with plurals
- **Number and date formatting** per locale (with `ext-intl`)
- **Fallback languages** and **layered locale directories** (override single keys of a base set)
- **Safe by design**: file and language names are validated, so user input can never load files outside the locale directories
- **Configurable handling of missing translations**: return the key, log it or throw

## Installation

```bash
composer require wufr/php-language-localizer
```

**Requirements:** PHP 8.1 or higher. `ext-intl` is optional and only used for locale-aware number and date placeholders.

Upgrading from 1.x? See [UPGRADE.md](UPGRADE.md).

## Basic Usage

```php
use wUFr\Translator;

$translator = new Translator(
    dir: __DIR__ . '/locales/',  // Directory containing the language directories
    lang: 'en_US'                // Language directory to read
);

echo $translator->locale('common/general', 'welcome');
// Welcome to our application
```

Setters are chainable:

```php
$translator
    ->setDirectory(__DIR__ . '/locales/')
    ->setLanguage('cs_CZ')
    ->setFallbackLanguages('en_US');
```

## Locale files

```
locales/
    en_US/
        common/
            general.php
            errors.php
        admin/
            dashboard.php
    cs_CZ/
        common/
            general.php
        ...
```

A locale file returns an array (or assigns it to `$l`, the format used by 1.x):

```php
<?php
// locales/en_US/common/general.php

return [
    'welcome' => 'Welcome to our application',
    'greeting' => 'Hello, {name}!',
    'logout' => 'Log out',
];
```

Locale files are PHP code and are fully trusted: only load files you ship yourself. Each file runs in an isolated scope and is loaded once per `Translator` instance.

**Names.** A file name is one or more segments separated by `/`, each made of letters, digits, `_`, `-` and `.` (not starting with a dot). A language is a directory name made of letters, digits, `_` and `-`. Any other name, for example one containing `..`, `\` or an absolute path, never touches the file system and is reported as a missing translation. This makes it safe to pass a language chosen by the visitor, although checking it against the languages you support is still the better choice.

## Parameters

```php
// 'greeting' => 'Hello, {name}!'
echo $translator->locale('common/general', 'greeting', ['name' => 'John']);
// Hello, John!
```

All placeholders are replaced in a single pass, so a value that contains `{something}` is never expanded again. A placeholder without a matching parameter is left as it is.

Values can be strings, numbers, `null`, `bool`, enums and `Stringable` objects. Other types (arrays, plain objects) trigger an `E_USER_WARNING` and render as an empty string.

### Escaping

Parameter values are **HTML-escaped by default**, because translations usually end up in HTML and values often come from users. The translation text itself is trusted and may contain markup:

```php
// 'welcome' => '<strong>Welcome</strong>, {name}!'
echo $translator->locale('users/profile', 'welcome', ['name' => '<script>alert(1)</script>']);
// <strong>Welcome</strong>, &lt;script&gt;alert(1)&lt;/script&gt;!
```

To insert trusted markup without escaping, pick the narrowest option:

```php
use wUFr\Localizer\Raw;

// One value, from PHP code
$translator->locale('legal/terms', 'accept', [
    'link' => new Raw('<a href="/terms">' . htmlspecialchars($title) . '</a>'),
]);

// Named values, e.g. from a template where objects are awkward to build
$translator->locale('legal/terms', 'accept', ['link' => $html, '_raw' => ['link']]);

// All values of one call
$translator->locale('mail/plain', 'body', ['name' => $name, '_raw' => true]);

// Every call, for output that never reaches HTML (plain-text e-mails, CLI)
$translator->setEscaping(false);
```

Never mark user input as raw.

### Numbers and dates

With `ext-intl` installed, typed placeholders format values for the current language:

```php
// 'total' => 'Total: {amount, number}'   → cs_CZ: "Total: 1 234,5",  en_US: "Total: 1,234.5"
// 'price' => 'Price: {amount, number, 2}' → exactly two fraction digits
// 'share' => 'Share: {ratio, number, percent}'
// 'rounded' => '{amount, number, integer}'
// 'created' => 'Created {when, date}'      → short | medium (default) | long | full
// 'at' => 'At {when, time}'                → short (default) | medium | long | full
// 'on' => 'On {when, datetime}'
```

Date values may be a `DateTimeInterface`, a Unix timestamp or a date string. Without `ext-intl` numbers are printed as plain digits and dates as `Y-m-d`, `H:i` or `Y-m-d H:i`.

## Pluralization

Pass the number as `_counter`. It is also available as the `{count}` placeholder unless you pass `count` yourself.

### CLDR plural categories (recommended)

Use the [CLDR plural categories](https://www.unicode.org/cldr/charts/latest/supplemental/language_plural_rules.html) of the language: `zero`, `one`, `two`, `few`, `many` and `other`. The library knows the rules of 60+ languages, including the Slavic ones where 22 and 25 need different forms, and falls back to `other` when a category is not defined.

```php
// locales/pl_PL/shop/cart.php
return [
    'items' => [
        0 => 'Your cart is empty',   // integer keys match that exact number
        'one' => '{count} produkt',  // 1
        'few' => '{count} produkty', // 2–4, 22–24, 32–34, …
        'many' => '{count} produktów', // 5–21, 25–31, …
        'other' => '{count} produktu', // fractions: 1.5
    ],
];

echo $translator->locale('shop/cart', 'items', ['_counter' => 22]); // 22 produkty
echo $translator->locale('shop/cart', 'items', ['_counter' => 25]); // 25 produktów
```

Fractions follow CLDR too: `1.5` is `many` in Czech and `other` in English. Visible decimals count, so pass `'1.0'` as a string to get "1.0 items" in English, while the integer `1` gives "1 item".

Languages without a built-in rule use the English one (`one` for exactly 1). Add or replace a rule with:

```php
$translator->getPluralRules()->define('xx', fn(int|float|string $n): string => $n == 1 ? 'one' : 'other');
```

### Numeric thresholds

Without category keys, integer keys are thresholds: the variant with the highest key that is less than or equal to the counter wins. The order of the keys does not matter, and a counter below every key uses the lowest one.

```php
// 'itemCount' => [
//     0 => 'You have no items',
//     1 => 'You have one item',
//     2 => 'You have {count} items',
// ]

echo $translator->locale('shop/cart', 'itemCount', ['_counter' => 12]);
// You have 12 items
```

Thresholds are enough for languages like English or Czech with integer counts. Use categories for languages with more complex rules.

## Gender

```php
use wUFr\Gender;

// 'welcome' => [
//     'male' => 'Welcome Mr. {name}',
//     'female' => 'Welcome Mrs. {name}',
//     'neutral' => 'Welcome {name}',
//     'entity' => 'Product: {name}',
// ]

echo $translator->locale('users/welcome', 'welcome', ['_gender' => Gender::Female, 'name' => 'Jane']);
// Welcome Mrs. Jane
```

`_gender` accepts a `Gender` case (or any backed enum) or a string key; the keys are not limited to the four `Gender` values.

Gender and plural variants can be combined. The gender is selected first:

```php
// 'items' => [
//     'male' => ['one' => 'He has {count} item', 'other' => 'He has {count} items'],
//     'female' => ['one' => 'She has {count} item', 'other' => 'She has {count} items'],
// ]

echo $translator->locale('users/inventory', 'items', ['_gender' => 'female', '_counter' => 3]);
// She has 3 items
```

## Fallback languages

When the current language lacks a file or a key, the fallback languages are tried in order:

```php
$translator->setLanguage('de_AT')->setFallbackLanguages('de_DE', 'en_US');
```

Plural rules and number formats follow the language the text was found in.

## Layered locale directories

Pass several directories, highest priority first. Each locale file is merged across every directory that has it, so the first directory can override single keys and leave the rest of the file to the others:

```php
$translator = new Translator([
    __DIR__ . '/app/locales/',        // project overrides
    __DIR__ . '/vendor/acme/ui/locales/', // base translations
], 'en_US');

$translator->getDirectory();   // the first directory
$translator->getDirectories(); // all of them
```

## Missing translations

A missing file, key or variant never throws and never breaks the page. By default the translator returns the `file.key` identifier (escaped), for example `common/general.welcome`.

Set a handler to log, show something else or throw:

```php
use wUFr\Localizer\MissingTranslation;

// Log and return the identifier
$translator->setMissingHandler(function (MissingTranslation $missing) use ($logger): string {
    $logger->warning($missing->message());
    return htmlspecialchars($missing->identifier());
});

// Fail loudly in development and tests
$translator->setMissingHandler(fn(MissingTranslation $missing) => throw new \RuntimeException($missing->message()));
```

`MissingTranslation` has the `reason` (a `MissingReason` case: `FileNotFound`, `KeyNotFound`, `InvalidName`, `SelectorMissing`, `VariantNotFound`, `InvalidCounter` or `InvalidValue`), `file`, `key`, `language` and an optional `detail`. The handler's return value is output as it is, so escape anything you build from these values.

Check for a key without triggering the handler:

```php
if ($translator->has('admin/menu', 'reports_desc')) {
    echo $translator->locale('admin/menu', 'reports_desc');
}
```

## API Reference

### `wUFr\Translator`

```php
public function __construct(string|array $dir = 'locales/', string $lang = 'en_US')

public function locale(string $file, string $key, array $params = []): string
public function has(string $file, string $key): bool

public function setDirectory(string|array $dir): self
public function setLanguage(string $lang): self
public function setFallbackLanguages(string ...$languages): self
public function setEscaping(bool $escape): self
public function setMissingHandler(?callable $handler): self
public function setPluralRules(PluralRules $pluralRules): self

public function getDirectory(): string
public function getDirectories(): array
public function getLanguage(): string
public function getFallbackLanguages(): array
public function isEscaping(): bool
public function getPluralRules(): PluralRules
```

Reserved parameters: `_counter` (plural number), `_gender` (gender key or enum) and `_raw` (`true` or a list of parameter names to insert without escaping).

`Translator` implements `wUFr\Localizer\TranslatorInterface` (`locale()` and `has()`), which is the type to depend on in your own code.

### `wUFr\Gender`

```php
enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Neutral = 'neutral';
    case Entity = 'entity';

    public function getDescription(): string
}
```

`getPronoun()`, `getPossessivePronoun()` and `getObjectPronoun()` still exist but are deprecated: they return English words only. Put pronouns into your locale files as gender variants instead.

## Security

See [SECURITY.md](SECURITY.md) for what the library guarantees and how to report a vulnerability.

## Contributing

Create a new branch from the `release-candidate` branch and submit a pull request. Run the checks before pushing:

```bash
composer test     # PHPUnit
composer analyse  # PHPStan
```

Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/), which drive the automatic releases.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
