<?php

// Lives outside every locale directory. Loading it means a path escaped the locale root.
$GLOBALS['localizer_pwned'] = true;

$l = [
	"x" => "escaped the locale directory",
];
