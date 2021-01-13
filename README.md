# PHP Language translator / localizer


Outputs translated text based on current language settings.  
Text strings are stored in PHP files and these can be stored in subfolders for better maintaining.


**Files structure example:**

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

**en_us/someFolder/testValues.php:**
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



**index.php:**
```php
<?php

require_once "./vendor/autoload.php";

/*
	DEFAULTS:
	dir: /locales/ (always use "/" at the end)

	lang: "en_US" - only specifies sub-folder,
	you can use whatever you want, even "english",
	if the sub folder is located in /locales/english/.
*/

$l = new wUFr\Translator(dir: "./locales/", lang: "en_US");

// SIMPLE STRING
echo $l->locale(
	"someFolder/testValues",
	"translate_this"
) // outputs "translated value"


// STRING BASED ON "AMOUNT" OF SOMETHING
echo $l->locale(
	"someFolder/testValues",
	"BasedOnNumber",
	["_counter" => 0]
) // outputs "box"

echo $l->locale(
	"someFolder/testValues",
	"BasedOnNumber",
	["_counter" => 50]
) // outputs "a lot of boxes"

echo $l->locale(
	"someFolder/testValues",
	"BasedOnNumber",
	["_counter" => 51]
) // outputs "a lot of boxes" as well


// STRING WITH REPLACABLE VALUES (IDEAL FOR USE IN TEMPLATING ENGINES, SO YOU DONT HAVE TO SPLIT TEXT INTO MULTIPLE KEY-STRINGS IN CONFIG)
echo $l->locale(
	"someFolder/testValues",
	"thxText",
	[
		"username" => "John Doe",
		"product"  => "AMD Epyc Server"
	]
) // outputs "Thank you John Doe for buying AMD Epyc Server"

// CAN BE COMBINED WITH COUNTER AS WELL
// WARNING: "_counter" cant be used in replacable values, you always have to specify it again
// its only used to decide which string should be returned, not for replacint
echo $l->locale(
	"someFolder/testValues",
	"thxTextCounter",
	[
		"_counter" => 50,
		"count"    => 50,
		"username" => "John Doe",
		"product"  => "AMD Epyc Server"
	]
) // outputs: "Thank you John Doe for buying 50 pieces of AMD Epyc Server"
```