<?php

namespace wUFr\Localizer;

/**
 * Why a translation could not be produced.
 */
enum MissingReason: string
{
	/** No locale directory has the requested file for any of the languages tried */
	case FileNotFound = 'file_not_found';

	/** The file exists, but neither the language nor any fallback defines the key */
	case KeyNotFound = 'key_not_found';

	/** The file or language name is not allowed (e.g. contains "..", slashes or other path characters) */
	case InvalidName = 'invalid_name';

	/** The value is an array of variants but neither `_gender` nor `_counter` was passed */
	case SelectorMissing = 'selector_missing';

	/** The requested gender or plural variant does not exist in the value */
	case VariantNotFound = 'variant_not_found';

	/** `_counter` is not a number */
	case InvalidCounter = 'invalid_counter';

	/** The resolved value is not a string (e.g. a nested array that needs another selector) */
	case InvalidValue = 'invalid_value';
}
