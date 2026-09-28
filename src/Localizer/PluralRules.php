<?php

namespace wUFr\Localizer;

/**
 * CLDR cardinal plural rules: maps a number to one of the categories
 * "zero", "one", "two", "few", "many" or "other" for a language.
 *
 * Built-in rules follow the Unicode CLDR plural rules for the languages
 * listed in RULES. Languages that are not listed fall back to the English
 * rule ("one" for exactly 1, "other" for everything else). Add or replace a
 * rule with define().
 *
 * Numbers are evaluated with their visible fraction digits, so "1.0" (string)
 * is not the same as 1 (int): in English "1 item" but "1.0 items". A float is
 * converted with PHP's shortest representation, so 1.0 (float) behaves like 1.
 *
 * @see https://www.unicode.org/cldr/charts/latest/supplemental/language_plural_rules.html
 */
final class PluralRules
{
	public const CATEGORIES = ['zero', 'one', 'two', 'few', 'many', 'other'];

	/** Language (lower case, as used before "_" or "-" in a locale) => rule name */
	private const RULES = [
		// Only "other"
		'ja' => 'other', 'zh' => 'other', 'ko' => 'other', 'vi' => 'other', 'th' => 'other',
		'id' => 'other', 'ms' => 'other', 'lo' => 'other', 'my' => 'other', 'km' => 'other',
		// one: i = 1 and v = 0
		'en' => 'i1v0', 'de' => 'i1v0', 'nl' => 'i1v0', 'sv' => 'i1v0', 'fi' => 'i1v0',
		'et' => 'i1v0', 'gl' => 'i1v0', 'ur' => 'i1v0', 'sw' => 'i1v0', 'fy' => 'i1v0',
		// one: n = 1
		'nb' => 'n1', 'no' => 'n1', 'nn' => 'n1', 'hu' => 'n1', 'tr' => 'n1', 'el' => 'n1',
		'bg' => 'n1', 'ka' => 'n1', 'az' => 'n1', 'sq' => 'n1', 'eu' => 'n1', 'mn' => 'n1',
		'ne' => 'n1', 'ta' => 'n1', 'te' => 'n1', 'ml' => 'n1', 'kk' => 'n1', 'ky' => 'n1',
		'uz' => 'n1',
		// one: i = 0 or n = 1
		'hi' => 'i0n1', 'bn' => 'i0n1', 'gu' => 'i0n1', 'kn' => 'i0n1', 'mr' => 'i0n1',
		'fa' => 'i0n1', 'am' => 'i0n1', 'zu' => 'i0n1',
		'da' => 'da',
		'is' => 'is',
		'mk' => 'mk',
		'es' => 'es',
		'it' => 'it', 'ca' => 'it',
		'pt' => 'pt', 'pt_pt' => 'pt_pt',
		'fr' => 'fr',
		'cs' => 'cs', 'sk' => 'cs',
		'pl' => 'pl',
		'ru' => 'ru', 'uk' => 'ru',
		'be' => 'be',
		'hr' => 'hr', 'sr' => 'hr', 'bs' => 'hr', 'sh' => 'hr',
		'sl' => 'sl',
		'lt' => 'lt',
		'lv' => 'lv',
		'ro' => 'ro', 'mo' => 'ro',
		'he' => 'he', 'iw' => 'he',
		'ar' => 'ar',
		'ga' => 'ga',
		'cy' => 'cy',
	];

	/** @var array<string,callable(int|float|string):string> */
	private array $custom = [];

	/**
	 * Add or replace the rule for a language ("xx") or a locale ("xx_YY").
	 *
	 * @param callable(int|float|string):string $rule Returns one of self::CATEGORIES
	 */
	public function define(string $language, callable $rule): self
	{
		$this->custom[self::normalize($language)] = $rule;
		return $this;
	}

	/**
	 * The plural category of a number in a language.
	 *
	 * @param string $locale A language ("cs") or locale ("cs_CZ", "pt-BR")
	 * @param int|float|string $number A number or numeric string
	 */
	public function category(string $locale, int|float|string $number): string
	{
		$full = self::normalize($locale);
		$language = explode('_', $full)[0];

		$custom = $this->custom[$full] ?? $this->custom[$language] ?? null;
		if ($custom !== null) {
			$category = $custom($number);
			return in_array($category, self::CATEGORIES, true) ? $category : 'other';
		}

		$rule = self::RULES[$full] ?? self::RULES[$language] ?? 'i1v0';

		return self::evaluate($rule, self::operands($number));
	}

	private static function normalize(string $locale): string
	{
		return strtolower(str_replace('-', '_', $locale));
	}

	/**
	 * CLDR operands: n (absolute value), i (integer digits), v (number of
	 * visible fraction digits), f (visible fraction digits), t (f without
	 * trailing zeros).
	 *
	 * @return array{n:float,i:int,v:int,f:int,t:int}
	 */
	private static function operands(int|float|string $number): array
	{
		if (is_int($number)) {
			$string = (string) $number;
		} elseif (is_float($number)) {
			$string = is_finite($number) ? self::floatToString($number) : '0';
		} else {
			$string = is_numeric(trim($number)) ? trim($number) : '0';
			if (stripos($string, 'e') !== false) {
				$string = self::floatToString((float) $string);
			}
		}

		$string = ltrim($string, '+-');
		[$integer, $fraction] = array_pad(explode('.', $string, 2), 2, '');
		$integer = ltrim($integer, '0');
		$trimmed = rtrim($fraction, '0');

		return [
			'n' => abs((float) $string),
			'i' => (int) ($integer === '' ? '0' : $integer),
			'v' => strlen($fraction),
			'f' => (int) ($fraction === '' ? '0' : $fraction),
			't' => (int) ($trimmed === '' ? '0' : $trimmed),
		];
	}

	private static function floatToString(float $number): string
	{
		$string = (string) $number;
		if (stripos($string, 'e') === false) {
			return $string;
		}

		return rtrim(rtrim(sprintf('%.15F', $number), '0'), '.');
	}

	/**
	 * @param array{n:float,i:int,v:int,f:int,t:int} $o
	 */
	private static function evaluate(string $rule, array $o): string
	{
		['n' => $n, 'i' => $i, 'v' => $v, 'f' => $f, 't' => $t] = $o;

		// "many" for exact millions in es/fr/it/pt ("1 000 000 de libros")
		$million = $i !== 0 && $i % 1000000 === 0 && $v === 0;

		return match ($rule) {
			'other' => 'other',
			'i1v0' => $i === 1 && $v === 0 ? 'one' : 'other',
			'n1' => $n == 1 ? 'one' : 'other',
			'i0n1' => $i === 0 || $n == 1 ? 'one' : 'other',
			'da' => $n == 1 || ($t !== 0 && ($i === 0 || $i === 1)) ? 'one' : 'other',
			'is' => ($t === 0 && $i % 10 === 1 && $i % 100 !== 11) || ($t % 10 === 1 && $t % 100 !== 11) ? 'one' : 'other',
			'mk' => ($v === 0 && $i % 10 === 1 && $i % 100 !== 11) || ($f % 10 === 1 && $f % 100 !== 11) ? 'one' : 'other',
			'es' => match (true) {
				$n == 1 => 'one',
				$million => 'many',
				default => 'other',
			},
			'it', 'pt_pt' => match (true) {
				$i === 1 && $v === 0 => 'one',
				$million => 'many',
				default => 'other',
			},
			'pt' => match (true) {
				$i === 0 || $i === 1 => 'one',
				$million => 'many',
				default => 'other',
			},
			'fr' => match (true) {
				$i === 0 || $i === 1 => 'one',
				$million => 'many',
				default => 'other',
			},
			'cs' => match (true) {
				$i === 1 && $v === 0 => 'one',
				$i >= 2 && $i <= 4 && $v === 0 => 'few',
				$v !== 0 => 'many',
				default => 'other',
			},
			'pl' => match (true) {
				$i === 1 && $v === 0 => 'one',
				$v === 0 && self::in($i % 10, 2, 4) && !self::in($i % 100, 12, 14) => 'few',
				// "i != 1" is implied: i = 1 with v = 0 matched "one" above
				$v === 0 && (
					self::in($i % 10, 0, 1)
					|| self::in($i % 10, 5, 9)
					|| self::in($i % 100, 12, 14)
				) => 'many',
				default => 'other',
			},
			'ru' => match (true) {
				$v === 0 && $i % 10 === 1 && $i % 100 !== 11 => 'one',
				$v === 0 && self::in($i % 10, 2, 4) && !self::in($i % 100, 12, 14) => 'few',
				$v === 0 && ($i % 10 === 0 || self::in($i % 10, 5, 9) || self::in($i % 100, 11, 14)) => 'many',
				default => 'other',
			},
			'be' => match (true) {
				fmod($n, 10) == 1 && fmod($n, 100) != 11 => 'one',
				self::in(fmod($n, 10), 2, 4) && !self::in(fmod($n, 100), 12, 14) => 'few',
				fmod($n, 10) == 0 || self::in(fmod($n, 10), 5, 9) || self::in(fmod($n, 100), 11, 14) => 'many',
				default => 'other',
			},
			'hr' => match (true) {
				($v === 0 && $i % 10 === 1 && $i % 100 !== 11) || ($f % 10 === 1 && $f % 100 !== 11) => 'one',
				($v === 0 && self::in($i % 10, 2, 4) && !self::in($i % 100, 12, 14))
					|| (self::in($f % 10, 2, 4) && !self::in($f % 100, 12, 14)) => 'few',
				default => 'other',
			},
			'sl' => match (true) {
				$v === 0 && $i % 100 === 1 => 'one',
				$v === 0 && $i % 100 === 2 => 'two',
				($v === 0 && self::in($i % 100, 3, 4)) || $v !== 0 => 'few',
				default => 'other',
			},
			'lt' => match (true) {
				fmod($n, 10) == 1 && !self::in(fmod($n, 100), 11, 19) => 'one',
				self::in(fmod($n, 10), 2, 9) && !self::in(fmod($n, 100), 11, 19) => 'few',
				$f !== 0 => 'many',
				default => 'other',
			},
			'lv' => match (true) {
				fmod($n, 10) == 0 || self::in(fmod($n, 100), 11, 19) || ($v === 2 && self::in($f % 100, 11, 19)) => 'zero',
				(fmod($n, 10) == 1 && fmod($n, 100) != 11)
					|| ($v === 2 && $f % 10 === 1 && $f % 100 !== 11)
					|| ($v !== 2 && $f % 10 === 1) => 'one',
				default => 'other',
			},
			'ro' => match (true) {
				$i === 1 && $v === 0 => 'one',
				$v !== 0 || $n == 0 || ($n != 1 && self::in(fmod($n, 100), 1, 19)) => 'few',
				default => 'other',
			},
			'he' => match (true) {
				($i === 1 && $v === 0) || ($i === 0 && $v !== 0) => 'one',
				$i === 2 && $v === 0 => 'two',
				default => 'other',
			},
			'ar' => match (true) {
				$n == 0 => 'zero',
				$n == 1 => 'one',
				$n == 2 => 'two',
				self::in(fmod($n, 100), 3, 10) => 'few',
				self::in(fmod($n, 100), 11, 99) => 'many',
				default => 'other',
			},
			'ga' => match (true) {
				$n == 1 => 'one',
				$n == 2 => 'two',
				self::in($n, 3, 6) => 'few',
				self::in($n, 7, 10) => 'many',
				default => 'other',
			},
			'cy' => match (true) {
				$n == 0 => 'zero',
				$n == 1 => 'one',
				$n == 2 => 'two',
				$n == 3 => 'few',
				$n == 6 => 'many',
				default => 'other',
			},
			default => 'other',
		};
	}

	/**
	 * CLDR range check "x = a..b": x must be a whole number inside the range.
	 */
	private static function in(int|float $x, int $from, int $to): bool
	{
		return $x == floor($x) && $x >= $from && $x <= $to;
	}
}
