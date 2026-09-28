<?php

// A locale file must not see the translator's internals.
$GLOBALS['localizer_scope_probe'] = [
	'this' => isset($this),
	'params' => isset($params),
	'key' => isset($key),
];

$l = [
	"probe" => "probed",
];
