# PHP Language Localizer

A powerful, flexible PHP library for translating and localizing applications with support for gender-based translations, pluralization, and parameter replacement.

_Just a weekend project so far, also to experiment with Github Copilot to rewrite and extend this library._

[![Latest Version on Packagist](https://img.shields.io/packagist/v/wufr/php-language-localizer.svg)](https://packagist.org/packages/wufr/php-language-localizer)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-8892BF)](https://php.net)
[![License](https://img.shields.io/github/license/wUFr/php-language-localizer)](LICENSE.md)

## Overview

PHP Language Localizer provides a simple yet comprehensive solution for implementing multi-language support in your PHP applications. The library allows you to structure translations in a clean, maintainable folder hierarchy while providing advanced features like gender-based translations, pluralization, and parameter replacement.

## Features

- **Simple Key-Based Translation**: Convert translation keys to localized strings
- **Flexible Folder Structure**: Organize translations in a logical folder hierarchy
- **Parameter Replacement**: Insert dynamic values into translated strings
- **Pluralization Support**: Handle different translations based on quantity
- **Gender-Based Translation**: Support for male, female, neutral, and entity (object/animal) forms
- **Combined Rules**: Mix gender and quantity rules for complex language scenarios
- **Type Safety**: Built with PHP 8.x features including enums and match expressions
- **Modern Implementation**: Utilizes readonly properties and proper type hints

## Installation

Install the library using Composer:

```bash
composer require wufr/php-language-localizer
```

**Requirements:**
- PHP 8.0 or higher

## Basic Usage

### Initialization

```php
// Import the Translator class
use wUFr\Translator;

// Create a new translator instance
$translator = new Translator(
    dir: "./locales/",  // Directory containing translation files
    lang: "en_US"       // Language code
);

// You can also set/change these values after initialization
$translator->setDirectory("./custom/locales/");
$translator->setLanguage("fr_FR");
```

### Simple String Translation

```php
// Translate a simple string
echo $translator->locale("common/general", "welcome");
// Output: "Welcome to our application"
```

## Organization

### Folder Structure

```
/locales/               # Base directory for all translations
    /en_US/             # English (US) translations
        /common/        # Common translations
            general.php # General terms
            errors.php  # Error messages
        /admin/         # Admin panel translations
            dashboard.php
            users.php
    /fr_FR/             # French translations
        /common/
            general.php
            errors.php
        ...
```

### Translation File Format

Each translation file should return an array using the `$l` variable:

```php
<?php
// locales/en_US/common/general.php

$l = [
    "welcome" => "Welcome to our application",
    "greeting" => "Hello, {name}!",
    "logout" => "Log out",
    // More translations...
];
```

## Advanced Features

### Parameter Replacement

Insert dynamic values into translations:

```php
echo $translator->locale("users/profile", "greeting", [
    "username" => "John",
    "lastLogin" => "yesterday"
]);
// Output: "Hello John, you last logged in yesterday"
```

### Pluralization with Counters

Handle different forms based on quantity:

```php
// Translation file:
// "itemCount" => [
//     1 => "You have one item",
//     2 => "You have two items",
//     5 => "You have several items",
//     10 => "You have many items"
// ]

// Code:
echo $translator->locale("shop/cart", "itemCount", ["_counter" => 1]);
// Output: "You have one item"

echo $translator->locale("shop/cart", "itemCount", ["_counter" => 3]);
// Output: "You have several items" (uses the 2 key as it's the highest that's <= 3)

echo $translator->locale("shop/cart", "itemCount", ["_counter" => 12]);
// Output: "You have many items"
```

When using the `_counter` parameter, the library selects the appropriate translation by finding the highest key that is less than or equal to the counter value.

### Gender-Based Translations

Handle gender-specific language forms:

```php
// Translation file:
// "welcome" => [
//     "male" => "Welcome Mr. {name}",
//     "female" => "Welcome Mrs. {name}",
//     "neutral" => "Welcome {name}",
//     "entity" => "Product: {name}"
// ]

// Code:
echo $translator->locale("users/welcome", "welcome", [
    "_gender" => "male",
    "name" => "John"
]);
// Output: "Welcome Mr. John"

echo $translator->locale("users/welcome", "welcome", [
    "_gender" => "female",
    "name" => "Jane"
]);
// Output: "Welcome Mrs. Jane"
```

### Combined Gender and Counter Translations

For complex language rules that need both gender and quantity:

```php
// Translation file:
// "items" => [
//     "male" => [
//         1 => "He has one item",
//         2 => "He has two items",
//         5 => "He has many items"
//     ],
//     "female" => [
//         1 => "She has one item",
//         2 => "She has two items",
//         5 => "She has many items"
//     ],
//     "neutral" => [
//         1 => "They have one item",
//         2 => "They have two items",
//         5 => "They have many items"
//     ],
//     "entity" => [
//         1 => "It contains one component",
//         2 => "It contains two components",
//         5 => "It contains many components"
//     ]
// ]

// Code:
echo $translator->locale("users/inventory", "items", [
    "_gender" => "female",
    "_counter" => 1
]);
// Output: "She has one item"

echo $translator->locale("users/inventory", "items", [
    "_gender" => "entity", 
    "_counter" => 3
]);
// Output: "It contains two components"
```

### Using with Parameters and Counters

Combine counters with parameter replacement:

```php
// Translation file:
// "purchase" => [
//     1 => "Thank you {name} for buying a piece of {product}",
//     2 => "Thank you {name} for buying two pieces of {product}",
//     5 => "Thank you {name} for buying {count} pieces of {product}"
// ]

// Code:
echo $translator->locale("shop/checkout", "purchase", [
    "_counter" => 50,
    "count" => 50,      // This is used as a parameter in the string
    "name" => "John",
    "product" => "Premium Widget"
]);
// Output: "Thank you John for buying 50 pieces of Premium Widget"
```

**Note:** The `_counter` parameter is only used to determine which string to return, not as a variable inside the string. Add another parameter (like `count` in this example) to use the number within the text.

## Using with PHP 8.1+ Enums

For type-safe gender-based translations, use the provided `Gender` enum:

```php
use wUFr\Gender;

// Using enum for gender parameter
echo $translator->locale("users/profile", "biography", [
    "_gender" => Gender::Male->value
]);
// Output: "He is a developer"

// For objects, animals, and non-gendered entities
echo $translator->locale("products/description", "details", [
    "_gender" => Gender::Entity->value
]);
// Output: "It is a high-quality product"
```

### Helpful Enum Methods

The `Gender` enum provides utility methods for working with pronouns:

```php
$gender = Gender::Female;

echo $gender->getPronoun();           // "she"
echo $gender->getPossessivePronoun(); // "her"
echo $gender->getObjectPronoun();     // "her"
echo $gender->getDescription();       // "Female"

// Available gender options:
// - Gender::Male    - For male subjects (he/his/him)
// - Gender::Female  - For female subjects (she/her/her) 
// - Gender::Neutral - For gender-neutral people (they/their/them)
// - Gender::Entity  - For objects, animals, babies, etc. (it/its/it)
```

## Error Handling

The library provides clear error messages when translations are missing:

```php
// If the file doesn't exist
echo $translator->locale("nonexistent", "key");
// Output: '<b style="color:red">lang file NOT found: nonexistent</b>'

// If the key doesn't exist in the file
echo $translator->locale("common/general", "nonexistentKey");
// Output: '<b style="color:red">lang key NOT found: common/general-nonexistentKey</b>'

// If using array translation without counter or gender
echo $translator->locale("common/general", "arrayKey");
// Output: '<b style="color:red">lang counter or gender NOT set</b>'

// If the specified gender doesn't exist in the translation
echo $translator->locale("users/profile", "welcome", ["_gender" => "nonexistent"]);
// Output: '<b style="color:red">lang gender NOT found: nonexistent</b>'
```

## API Reference

### Translator Class

```php
// Constructor
public function __construct(string $dir = "/locales/", string $lang = "en_US")

// Directory and language setters/getters
public function setDirectory(string $dir): self
public function setLanguage(string $lang): self
public function getDirectory(): string
public function getLanguage(): string

// Main translation method
public function locale(string $file, string $key, array $params = []): string
```

### Gender Enum

```php
enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Neutral = 'neutral';
    case Entity = 'entity';
    
    public function getDescription(): string
    public function getPronoun(): string
    public function getPossessivePronoun(): string
    public function getObjectPronoun(): string
}
```

## Contributing

To contribute, please create a new branch from the `release-candidate` branch and submit a pull request.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.
