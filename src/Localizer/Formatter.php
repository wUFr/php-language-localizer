<?php

namespace wUFr\Localizer;

/**
 * Replaces placeholders in a translated string.
 *
 *     {name}                 the value as text
 *     {count, number}        a locale-formatted number (1 234,5 in cs_CZ)
 *     {count, number, 2}     … with exactly 2 fraction digits
 *     {count, number, integer|percent}
 *     {date, date}           a locale-formatted date: short, medium (default), long or full
 *     {date, time}           a locale-formatted time: short (default), medium, long or full
 *     {date, datetime}       both: medium date + short time by default
 *
 * All placeholders are replaced in a single pass, so a value that itself
 * contains "{other}" is never expanded again. Values are HTML-escaped unless
 * escaping is disabled or the value is marked raw. A placeholder without a
 * matching parameter is left untouched.
 *
 * Number and date formatting uses ext-intl when it is available. Without it
 * numbers are printed as plain digits and dates in ISO 8601 form.
 *
 * @internal Used by Translator; not covered by the backward compatibility promise.
 */
final class Formatter
{
	private const PLACEHOLDER = '/\{([^{},]+)(?:,\s*(number|date|time|datetime)(?:,\s*([A-Za-z0-9]+))?\s*)?\}/';

	private bool $intl;

	public function __construct(?bool $intl = null)
	{
		$this->intl = $intl ?? (class_exists(\NumberFormatter::class) && class_exists(\IntlDateFormatter::class));
	}

	/**
	 * @param array<string,mixed> $values Placeholder values
	 * @param bool|list<string> $raw True to skip escaping for every value, or the names to skip it for
	 */
	public function format(string $text, array $values, string $locale, bool $escape = true, bool|array $raw = false): string
	{
		if ($values === [] || !str_contains($text, '{')) {
			return $text;
		}

		$result = preg_replace_callback(
			self::PLACEHOLDER,
			function (array $match) use ($values, $locale, $escape, $raw): string {
				$name = $match[1];
				if (!array_key_exists($name, $values)) {
					return $match[0];
				}

				$value = $values[$name];
				$type = $match[2] ?? '';
				$style = $match[3] ?? '';

				$trusted = $value instanceof Raw || $raw === true || (is_array($raw) && in_array($name, $raw, true));
				$string = $type === '' ? $this->stringify($name, $value) : $this->typed($name, $value, $type, $style, $locale);

				return $escape && !$trusted ? self::escape($string) : $string;
			},
			$text
		);

		return $result ?? $text;
	}

	public static function escape(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	private function stringify(string $name, mixed $value): string
	{
		return match (true) {
			$value === null => '',
			is_string($value) => $value,
			is_int($value), is_float($value) => (string) $value,
			is_bool($value) => $value ? '1' : '',
			$value instanceof \BackedEnum => (string) $value->value,
			$value instanceof \UnitEnum => $value->name,
			$value instanceof \Stringable => (string) $value,
			default => $this->unsupported($name, $value),
		};
	}

	private function typed(string $name, mixed $value, string $type, string $style, string $locale): string
	{
		if ($type === 'number') {
			if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
				return $this->stringify($name, $value);
			}
			return $this->number($value, $style, $locale);
		}

		$date = self::toDate($value);
		if ($date === null) {
			return $this->stringify($name, $value);
		}

		return $this->date($date, $type, $style, $locale);
	}

	private function number(int|float|string $value, string $style, string $locale): string
	{
		$number = is_string($value) ? (float) $value : $value;
		$digits = ctype_digit($style) ? min((int) $style, 20) : null;

		if (!$this->intl) {
			return match (true) {
				$style === 'integer' => (string) (int) round((float) $number),
				$style === 'percent' => round((float) $number * 100) . '%',
				$digits !== null => number_format((float) $number, $digits, '.', ''),
				default => (string) $value,
			};
		}

		$formatter = new \NumberFormatter($locale, $style === 'percent' ? \NumberFormatter::PERCENT : \NumberFormatter::DECIMAL);
		// Match round() in the fallback: halves round away from zero (ICU defaults to half-even).
		$formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
		if ($style === 'integer') {
			$formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 0);
		} elseif ($digits !== null) {
			$formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $digits);
			$formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $digits);
		}

		$formatted = $formatter->format($number);

		return $formatted === false ? (string) $value : $formatted;
	}

	private function date(\DateTimeInterface $date, string $type, string $style, string $locale): string
	{
		$styles = [
			'short' => \IntlDateFormatter::SHORT,
			'medium' => \IntlDateFormatter::MEDIUM,
			'long' => \IntlDateFormatter::LONG,
			'full' => \IntlDateFormatter::FULL,
		];

		if (!$this->intl) {
			return $date->format(match ($type) {
				'date' => 'Y-m-d',
				'time' => 'H:i',
				default => 'Y-m-d H:i',
			});
		}

		$chosen = $styles[$style] ?? null;
		[$dateType, $timeType] = match ($type) {
			'date' => [$chosen ?? \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE],
			'time' => [\IntlDateFormatter::NONE, $chosen ?? \IntlDateFormatter::SHORT],
			default => [$chosen ?? \IntlDateFormatter::MEDIUM, $chosen ?? \IntlDateFormatter::SHORT],
		};

		try {
			$formatter = new \IntlDateFormatter($locale, $dateType, $timeType, $date->getTimezone());
			$formatted = $formatter->format($date);
		} catch (\Throwable) {
			$formatted = false;
		}

		return $formatted === false ? $date->format(DATE_ATOM) : $formatted;
	}

	private static function toDate(mixed $value): ?\DateTimeInterface
	{
		if ($value instanceof \DateTimeInterface) {
			return $value;
		}

		if (is_int($value)) {
			return (new \DateTimeImmutable('@' . $value))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
		}

		if (is_string($value) && $value !== '') {
			try {
				return new \DateTimeImmutable($value);
			} catch (\Exception) {
				return null;
			}
		}

		return null;
	}

	private function unsupported(string $name, mixed $value): string
	{
		trigger_error(
			sprintf('Translation parameter "%s" of type %s cannot be converted to a string.', $name, get_debug_type($value)),
			E_USER_WARNING
		);

		return '';
	}
}
