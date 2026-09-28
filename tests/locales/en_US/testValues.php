<?php

$l = [
	"helloWorld" => "hello world",
	"BasedOnNumber" => [
		1  => "box",
		2  => "boxes",
		50 => "a lot of boxes"
	],
	"thxText" => "Thank you {username} for buying {product}",
	"thxTextCounter" => [
		1 => "Thank you {username} for buying a piece of {product}",
		2 => "Thank you {username} for buying two of {product}",
		50 => "Thank you {username} for buying {count} pieces of {product}",
	],
	"unsorted" => [
		1 => "one",
		5 => "many",
		2 => "few",
	],
	"noZero" => [
		1 => "item",
		2 => "items",
	],
	"withZero" => [
		0 => "no items",
		1 => "one item",
		2 => "{count} items",
	],
	"categories" => [
		0 => "no files",
		"one" => "{count} file",
		"other" => "{count} files",
	],
	"onlyOther" => [
		"other" => "{count} things",
	],
	"link" => "Read the {link} first",
	"markup" => "<strong>{name}</strong>",
	"nested" => "{a} and {b}",
	"number" => "Total: {amount, number}",
	"fixed" => "Price: {amount, number, 2}",
	"integer" => "Rounded: {amount, number, integer}",
	"percent" => "Share: {share, number, percent}",
	"date" => "Created {when, date}",
	"dateLong" => "Created {when, date, long}",
	"time" => "At {when, time}",
	"datetime" => "On {when, datetime}",
	"notAPlaceholder" => "Keep {unknown} and {count, number} as they are",
	"overridden" => "core value",
	"coreOnly" => "only in core",
	"numberValue" => 42,
];
