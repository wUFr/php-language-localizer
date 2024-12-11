# PHP Language Translator / Localizer

Outputs translated text based on current language settings.  
Text strings are stored in PHP files and these can be stored in subfolders for better maintaining.

**Features:**
- Translate keys into text values based on set language.
- Support for string replacement with parameters.
- Handle different translations based on a counter value.
-Easy to structure localization texts in a clean folder hierarchy.

## Table of Contents
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Files Structure Example](#files-structure-example)
- [Usage](#usage)
- [Contributing](#contributing)
- [License](#license)


## Installation

To install the library, use Composer:

```sh
composer require wufr/php-language-localizer
```

**Note:** This library requires PHP version 8.0 or higher.

## Quick Start

Here is a quick example to get you started:

```php
require_once "./vendor/autoload.php";

use wUFr\Translator;

$translator = new Translator(dir: "./locales/", lang: "en_US");
echo $translator->locale("someFolder/testValues", "translateThis");
// outputs "translated value"
```

## Files Structure Example

**Example folder structure:**  
You can specify custom location for the "locales" folder, if you need to.

```
/locales/
    en_US/
        someFolder/
            testValues.php
            ...

    cs_CZ/
        ...
    ...
/vendor/
    ... (composer packages)
...
composer.json
index.php
```

**Example localization file:**  
Path to this file and name of the file itself is used in the locale() method. This way you can structure your localization texts in a nice clean structure with folders for different parts of the website, like "eshop", "user-area", "admin-panel" etc.

`en_us/someFolder/testValues.php:`
```php
$l = [
    "translateThis" => "translated value",
    "BasedOnNumber" => [
        1  => "box",
        2  => "boxes",
        50 => "a lot of boxes"
    ],
    "thxText" => "Thank you {username} for buying {product}",
    "thxTextCounter" => [
        1 =>   "Thank you {username} for buying a piece of {product}",
        2 =>   "Thank you {username} for buying two of {product}",
        50 =>  "Thank you {username} for buying {count} pieces of {product}",
    ],
];

```




## Usage

### Simple String

```php
echo $translator->locale("someFolder/testValues", "translateThis");
// outputs "translated value"
```

### String Based on "Amount" of Something

```php
echo $translator->locale("someFolder/testValues", "BasedOnNumber", ["_counter" => 1]);
// outputs "box"

echo $translator->locale("someFolder/testValues", "BasedOnNumber", ["_counter" => 50]);
// outputs "a lot of boxes"
```

### String with Replaceable Values

```php
echo $translator->locale("someFolder/testValues", "thxText", [
    "username" => "John Doe",
    "product"  => "AMD Epyc Server"
]);
// outputs "Thank you John Doe for buying AMD Epyc Server"
```

### Combined with Counter

```php
echo $translator->locale("someFolder/testValues", "thxTextCounter", [
    "_counter" => 50,
    "count"    => 50,
    "username" => "John Doe",
    "product"  => "AMD Epyc Server"
]);
// outputs: "Thank you John Doe for buying 50 pieces of AMD Epyc Server"
```

**Important:** the `_counter` parameter is used only to decide which string is returned, not as a variable inside string. You need to add (in this case) `count` in the parameters.

## Contributing

To contribute, please create a new branch from the `release-candidate` branch and submit a pull request.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.