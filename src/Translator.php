<?php

namespace wUFr;

use wUFr\Localizer\Formatter;
use wUFr\Localizer\LocaleLoader;
use wUFr\Localizer\MissingReason;
use wUFr\Localizer\MissingTranslation;
use wUFr\Localizer\PluralRules;
use wUFr\Localizer\TranslatorInterface;

class Translator implements TranslatorInterface {
	/** Reserved parameter: the number that selects a plural variant */
	public const COUNTER = '_counter';

	/** Reserved parameter: the key (or Gender enum) that selects a gender variant */
	public const GENDER = '_gender';

	/** Reserved parameter: true, or a list of parameter names, to insert without HTML escaping */
	public const RAW = '_raw';

	private const RESERVED = [self::COUNTER, self::GENDER, self::RAW];

	private LocaleLoader $loader;

	private string $lang;

	/** @var list<string> */
	private array $fallbackLanguages = [];

	private bool $escape = true;

	/** @var (\Closure(MissingTranslation):string)|null */
	private ?\Closure $missingHandler = null;

	private PluralRules $pluralRules;

	private Formatter $formatter;

	/**
	 * @param string|list<string> $dir Locale directory, or several directories, highest priority first
	 * @param string $lang Language directory name (e.g. "en_US")
	 */
	public function __construct(
		string|array $dir = 'locales/',
		string $lang = 'en_US') {
		$this->setDirectory($dir);
		$this->lang = $lang;
		$this->pluralRules = new PluralRules();
		$this->formatter = new Formatter();
	}

	/**
	 * Set the directory path for localization files.
	 *
	 * Pass several directories (highest priority first) to layer them: a file
	 * is merged across every directory that has it, so the first directory
	 * can override single keys and leave the rest to the others.
	 *
	 * @param string|array<array-key,mixed> $dir The directory path(s), a list of strings
	 * @return self For method chaining
	 */
	public function setDirectory(string|array $dir): self {
		$dirs = [];
		foreach (is_array($dir) ? $dir : [$dir] as $path) {
			if (!is_string($path)) {
				throw new \InvalidArgumentException('The locale directory must be a string or a non-empty list of strings.');
			}
			$dirs[] = $path;
		}

		if ($dirs === []) {
			throw new \InvalidArgumentException('The locale directory must be a string or a non-empty list of strings.');
		}

		$this->loader = new LocaleLoader($dirs);
		return $this;
	}

	/**
	 * Set the language for translations.
	 *
	 * The name must be a plain directory name (letters, digits, "_" and "-").
	 * Any other value, such as one containing "/" or "..", never resolves to a
	 * file: every translation is then reported as missing.
	 *
	 * @param string $lang The language code (e.g., "en_US")
	 * @return self For method chaining
	 */
	public function setLanguage(string $lang): self {
		$this->lang = $lang;
		return $this;
	}

	/**
	 * Languages to try, in order, when the current language lacks a file or key.
	 *
	 * @return self For method chaining
	 */
	public function setFallbackLanguages(string ...$languages): self {
		$this->fallbackLanguages = array_values($languages);
		return $this;
	}

	/**
	 * Escape parameter values for HTML (default: on). Turn it off only when the
	 * output never reaches HTML, e.g. plain-text e-mails or CLI output.
	 *
	 * @return self For method chaining
	 */
	public function setEscaping(bool $escape): self {
		$this->escape = $escape;
		return $this;
	}

	/**
	 * Decide what a missing translation renders as. The handler receives a
	 * MissingTranslation and returns the string to output (it is not escaped,
	 * so escape anything you build from it). It may also log or throw.
	 *
	 * Without a handler (or with null) the "file.key" identifier is returned.
	 *
	 * @param (callable(MissingTranslation):string)|null $handler
	 * @return self For method chaining
	 */
	public function setMissingHandler(?callable $handler): self {
		$this->missingHandler = $handler === null ? null : \Closure::fromCallable($handler);
		return $this;
	}

	/**
	 * Replace the plural rules, e.g. with an instance that has extra languages defined.
	 *
	 * @return self For method chaining
	 */
	public function setPluralRules(PluralRules $pluralRules): self {
		$this->pluralRules = $pluralRules;
		return $this;
	}

	/**
	 * Get the current language
	 *
	 * @return string The current language code
	 */
	public function getLanguage(): string {
		return $this->lang;
	}

	/**
	 * @return list<string>
	 */
	public function getFallbackLanguages(): array {
		return $this->fallbackLanguages;
	}

	/**
	 * Get the current directory path (the highest-priority one when several are set)
	 *
	 * @return string The current directory path
	 */
	public function getDirectory(): string {
		return $this->loader->directories()[0];
	}

	/**
	 * @return list<string> All locale directories, highest priority first
	 */
	public function getDirectories(): array {
		return $this->loader->directories();
	}

	public function isEscaping(): bool {
		return $this->escape;
	}

	public function getPluralRules(): PluralRules {
		return $this->pluralRules;
	}

	/**
	 * Get a localized string based on the provided key and parameters
	 *
	 * @param string $file The locale file to load (e.g. "common/general")
	 * @param string $key The translation key to retrieve
	 * @param array<string,mixed> $params Placeholder values plus the reserved `_counter`, `_gender` and `_raw`
	 * @return string The localized string
	 */
	public function locale(
		string $file,
		string $key,
		array $params = []
	): string {
		$resolved = $this->resolve($file, $key, $params);

		if ($resolved instanceof MissingTranslation) {
			return $this->handleMissing($resolved);
		}

		[$text, $language] = $resolved;

		return $this->formatter->format(
			$text,
			self::placeholderValues($params),
			$language,
			$this->escape,
			self::rawOption($params)
		);
	}

	/**
	 * Whether the key exists in the file for the current language or one of its fallbacks
	 */
	public function has(string $file, string $key): bool {
		return !$this->lookup($file, $key) instanceof MissingTranslation;
	}

	/**
	 * @param array<string,mixed> $params
	 * @return array{0:string,1:string}|MissingTranslation The text and the language it came from
	 */
	private function resolve(string $file, string $key, array $params): array|MissingTranslation {
		$found = $this->lookup($file, $key);
		if ($found instanceof MissingTranslation) {
			return $found;
		}

		[$value, $language] = $found;

		if (is_array($value)) {
			$value = $this->processArrayTranslation($value, $params, $file, $key, $language);
			if ($value instanceof MissingTranslation) {
				return $value;
			}
		}

		if (is_string($value)) {
			return [$value, $language];
		}

		if (is_int($value) || is_float($value) || $value instanceof \Stringable) {
			return [(string) $value, $language];
		}

		return new MissingTranslation(MissingReason::InvalidValue, $file, $key, $language, 'type=' . get_debug_type($value));
	}

	/**
	 * Find the raw value of a key in the current language, then in each fallback language.
	 *
	 * @return array{0:mixed,1:string}|MissingTranslation The value and the language it came from
	 */
	private function lookup(string $file, string $key): array|MissingTranslation {
		if (!LocaleLoader::isValidFile($file)) {
			return new MissingTranslation(MissingReason::InvalidName, $file, $key, $this->lang, 'invalid file name');
		}

		$fileFound = false;
		$invalidLanguage = false;

		foreach (array_unique([$this->lang, ...$this->fallbackLanguages]) as $language) {
			if (!LocaleLoader::isValidLanguage($language)) {
				$invalidLanguage = true;
				continue;
			}

			$values = $this->loader->load($language, $file);
			if ($values === null) {
				continue;
			}

			$fileFound = true;
			if (array_key_exists($key, $values)) {
				return [$values[$key], $language];
			}
		}

		return match (true) {
			$fileFound => new MissingTranslation(MissingReason::KeyNotFound, $file, $key, $this->lang),
			$invalidLanguage => new MissingTranslation(MissingReason::InvalidName, $file, $key, $this->lang, 'invalid language name'),
			default => new MissingTranslation(MissingReason::FileNotFound, $file, $key, $this->lang),
		};
	}

	/**
	 * Process array-based translations for counter or gender
	 *
	 * @param array<array-key,mixed> $value The translation array
	 * @param array<string,mixed> $params Parameters for the translation
	 * @return mixed The selected variant
	 */
	private function processArrayTranslation(array $value, array $params, string $file, string $key, string $language): mixed {
		if (isset($params[self::GENDER])) {
			$gender = self::genderKey($params[self::GENDER]);

			if ($gender === null || !array_key_exists($gender, $value)) {
				return new MissingTranslation(MissingReason::VariantNotFound, $file, $key, $language, 'gender=' . ($gender ?? get_debug_type($params[self::GENDER])));
			}

			$value = $value[$gender];
			if (!is_array($value)) {
				return $value;
			}

			// A gender variant that is itself an array needs a counter too.
			if (!isset($params[self::COUNTER])) {
				return new MissingTranslation(MissingReason::SelectorMissing, $file, $key, $language, 'gender=' . $gender . ' needs _counter');
			}
		}

		if (isset($params[self::COUNTER])) {
			return $this->processCounterTranslation($value, $params[self::COUNTER], $file, $key, $language);
		}

		return new MissingTranslation(MissingReason::SelectorMissing, $file, $key, $language);
	}

	/**
	 * Select a plural variant.
	 *
	 * With CLDR category keys ("zero", "one", "two", "few", "many", "other")
	 * the language's plural rules pick the category; integer keys then match
	 * that exact number only. Otherwise integer keys are thresholds: the
	 * variant with the highest key that is <= the counter wins, and a counter
	 * below every key uses the lowest one.
	 *
	 * @param array<array-key,mixed> $value The translation array
	 * @return mixed The selected variant
	 */
	private function processCounterTranslation(array $value, mixed $counter, string $file, string $key, string $language): mixed {
		if (!self::isNumber($counter)) {
			return new MissingTranslation(MissingReason::InvalidCounter, $file, $key, $language, 'type=' . get_debug_type($counter));
		}

		/** @var int|float|string $counter */
		$number = (float) $counter;

		if (array_intersect(array_keys($value), PluralRules::CATEGORIES) !== []) {
			if ($number === floor($number) && abs($number) <= PHP_INT_MAX && array_key_exists((int) $number, $value)) {
				return $value[(int) $number];
			}

			$category = $this->pluralRules->category($language, $counter);
			if (array_key_exists($category, $value)) {
				return $value[$category];
			}

			if (array_key_exists('other', $value)) {
				return $value['other'];
			}

			return new MissingTranslation(MissingReason::VariantNotFound, $file, $key, $language, 'category=' . $category);
		}

		$thresholds = [];
		foreach ($value as $threshold => $text) {
			if (is_int($threshold) || is_numeric($threshold)) {
				$thresholds[] = [(float) $threshold, $text];
			}
		}

		if ($thresholds === []) {
			return new MissingTranslation(MissingReason::VariantNotFound, $file, $key, $language, 'no numeric variants');
		}

		usort($thresholds, static fn(array $a, array $b): int => $a[0] <=> $b[0]);

		$selected = $thresholds[0][1];
		foreach ($thresholds as [$threshold, $text]) {
			if ($threshold > $number) {
				break;
			}
			$selected = $text;
		}

		return $selected;
	}

	private function handleMissing(MissingTranslation $missing): string {
		if ($this->missingHandler !== null) {
			return ($this->missingHandler)($missing);
		}

		$identifier = $missing->identifier();

		return $this->escape ? Formatter::escape($identifier) : $identifier;
	}

	private static function genderKey(mixed $gender): ?string {
		return match (true) {
			$gender instanceof \BackedEnum => (string) $gender->value,
			$gender instanceof \UnitEnum => $gender->name,
			is_string($gender), is_int($gender) => (string) $gender,
			$gender instanceof \Stringable => (string) $gender,
			default => null,
		};
	}

	private static function isNumber(mixed $value): bool {
		return is_int($value)
			|| (is_float($value) && is_finite($value))
			|| (is_string($value) && is_numeric($value) && is_finite((float) $value));
	}

	/**
	 * Placeholder values: everything except the reserved parameters, plus
	 * `count` taken from `_counter` when not passed explicitly.
	 *
	 * @param array<string,mixed> $params
	 * @return array<string,mixed>
	 */
	private static function placeholderValues(array $params): array {
		$values = array_diff_key($params, array_flip(self::RESERVED));

		if (!array_key_exists('count', $values) && isset($params[self::COUNTER])) {
			$values['count'] = $params[self::COUNTER];
		}

		return $values;
	}

	/**
	 * @param array<string,mixed> $params
	 * @return bool|list<string>
	 */
	private static function rawOption(array $params): bool|array {
		$raw = $params[self::RAW] ?? false;

		if (is_array($raw)) {
			return array_values(array_filter($raw, 'is_string'));
		}

		return $raw === true;
	}
}
