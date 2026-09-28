# Upgrading

## From 1.x to 2.0

2.0 makes the safe behaviour the default. Most applications only need to review the first three points.

### Parameters are HTML-escaped

`{placeholder}` values are now passed through `htmlspecialchars()`. If a parameter intentionally contains markup, mark it as trusted:

```php
// 1.x
$translator->locale('legal/terms', 'accept', ['link' => '<a href="/terms">terms</a>']);

// 2.0
use wUFr\Localizer\Raw;

$translator->locale('legal/terms', 'accept', ['link' => new Raw('<a href="/terms">terms</a>')]);
// or, where building objects is awkward (templates):
$translator->locale('legal/terms', 'accept', ['link' => $html, '_raw' => ['link']]);
```

For output that never reaches HTML (plain-text e-mails, CLI), call `$translator->setEscaping(false)` or pass `'_raw' => true`.

The translation text itself is not escaped, exactly as before.

### Missing translations no longer return red HTML

1.x returned markup such as `<b style="color:red">lang key NOT found: file-key</b>`. 2.0 returns the escaped `file.key` identifier (for example `common/general.welcome`). To keep a visible marker, log, or throw, set a handler:

```php
use wUFr\Localizer\MissingTranslation;

$translator->setMissingHandler(fn(MissingTranslation $m): string =>
    '<mark>' . htmlspecialchars($m->identifier()) . '</mark>'
);
```

A gender variant that needs a counter but did not get one (1.x threw a `TypeError`) and a `_counter` that is not a number are reported the same way.

### PHP 8.1 or newer

The `Gender` enum always needed PHP 8.1; 2.0 declares it.

### `$values` is no longer public

The `public array $values` cache of loaded files was removed. Use `has()` to check whether a key exists, and `setDirectory()` / `setLanguage()` to change what is loaded.

### File and language names are validated

File names may only contain letters, digits, `_`, `-`, `.` and `/` as a separator, and no segment may start with a dot. Language names may only contain letters, digits, `_` and `-`. Anything else (`..`, backslashes, absolute paths, stream wrappers) is reported as a missing translation with the reason `InvalidName`.

### Threshold plurals

- The variant with the **highest** threshold not above the counter wins, regardless of the order in which the keys are written. 1.x used the last matching key in written order.
- A counter below the lowest threshold now uses the lowest variant instead of an empty string. Add a `0` key if zero needs its own text.
- A non-numeric `_counter` is reported as missing instead of being compared as a string.

### Placeholders

- Placeholders are replaced in one pass: a value that contains `{other}` is no longer expanded.
- `{count}` is filled from `_counter` when no `count` parameter is passed.
- `{_counter}`, `{_gender}` and `{_raw}` are never replaced.
- `{name, number}`, `{name, date}`, `{name, time}` and `{name, datetime}` are now formatting instructions. A placeholder that looked like this in 1.x text, with a parameter literally named `name, number`, is no longer supported.
- Arrays and objects without `__toString()` trigger an `E_USER_WARNING` and render as an empty string (1.x printed `Array`).

### Directories

- The default directory changed from `/locales/` (the file system root) to `locales/` (relative to the working directory).
- A trailing slash is optional.
- `setDirectory()` also accepts a list of directories; `getDirectory()` returns the first one.

### Gender pronoun helpers are deprecated

`Gender::getPronoun()`, `getPossessivePronoun()` and `getObjectPronoun()` return English only. They still work but will be removed in 3.0; keep pronouns in locale files as gender variants.

### Extending `Translator`

`Translator` now implements `wUFr\Localizer\TranslatorInterface` and gained the public methods `has()`, `setFallbackLanguages()`, `getFallbackLanguages()`, `setEscaping()`, `isEscaping()`, `setMissingHandler()`, `setPluralRules()`, `getPluralRules()` and `getDirectories()`. A subclass that declares a method with one of these names must use a compatible signature. All other members are private.
