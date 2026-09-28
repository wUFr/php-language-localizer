<?php

use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use wUFr\Gender;
use wUFr\Localizer\Formatter;
use wUFr\Translator;

class FormatterTest extends TestCase
{
	private const TEST_DIR = __DIR__ . "/locales/";

	private function translator(string $lang = 'en_US'): Translator
	{
		return new Translator(self::TEST_DIR, $lang);
	}

	/**
	 * Replace non-breaking and narrow no-break spaces ICU uses as separators.
	 */
	private static function spaces(string $value): string
	{
		return str_replace(["\u{00A0}", "\u{202F}"], ' ', $value);
	}

	#[RequiresPhpExtension('intl')]
	public function testNumberPlaceholder(): void
	{
		$this->assertSame('Total: 1,234.5', $this->translator()->locale('testValues', 'number', ['amount' => 1234.5]));
		$this->assertSame('Total: 1 234,5', self::spaces((new Formatter(true))->format('Total: {amount, number}', ['amount' => 1234.5], 'cs_CZ')));
	}

	#[RequiresPhpExtension('intl')]
	public function testNumberStyles(): void
	{
		$formatter = new Formatter(true);

		$this->assertSame('1 234,50', self::spaces($formatter->format('{n, number, 2}', ['n' => 1234.5], 'cs_CZ')));
		$this->assertSame('1,235', $formatter->format('{n, number, integer}', ['n' => '1234.5'], 'en_US'));
		$this->assertSame('25%', $formatter->format('{n, number, percent}', ['n' => 0.25], 'en_US'));
	}

	#[RequiresPhpExtension('intl')]
	public function testDatePlaceholders(): void
	{
		$formatter = new Formatter(true);
		$date = new DateTimeImmutable('2026-03-05 14:30:00', new DateTimeZone('Europe/Prague'));

		$this->assertSame('5. 3. 2026', self::spaces($formatter->format('{d, date}', ['d' => $date], 'cs_CZ')));
		$this->assertSame('March 5, 2026', self::spaces($formatter->format('{d, date, long}', ['d' => $date], 'en_US')));
		$this->assertSame('14:30', $formatter->format('{d, time}', ['d' => $date], 'cs_CZ'));
		$this->assertSame('Mar 5, 2026, 2:30 PM', self::spaces($formatter->format('{d, datetime}', ['d' => $date], 'en_US')));
	}

	public function testFallbackWithoutIntl(): void
	{
		$formatter = new Formatter(false);
		$date = new DateTimeImmutable('2026-03-05 14:30:00');

		$this->assertSame('1234.5', $formatter->format('{n, number}', ['n' => 1234.5], 'cs_CZ'));
		$this->assertSame('1234.50', $formatter->format('{n, number, 2}', ['n' => 1234.5], 'cs_CZ'));
		$this->assertSame('1235', $formatter->format('{n, number, integer}', ['n' => 1234.5], 'cs_CZ'));
		$this->assertSame('25%', $formatter->format('{n, number, percent}', ['n' => 0.25], 'cs_CZ'));
		$this->assertSame('2026-03-05', $formatter->format('{d, date}', ['d' => $date], 'cs_CZ'));
		$this->assertSame('14:30', $formatter->format('{d, time}', ['d' => $date], 'cs_CZ'));
		$this->assertSame('2026-03-05 14:30', $formatter->format('{d, datetime}', ['d' => '2026-03-05 14:30:00'], 'cs_CZ'));
	}

	public function testTypedPlaceholderWithUnsuitableValueFallsBackToText(): void
	{
		$formatter = new Formatter(false);

		$this->assertSame('abc', $formatter->format('{n, number}', ['n' => 'abc'], 'en_US'));
		$this->assertSame('not a date', $formatter->format('{d, date}', ['d' => 'not a date'], 'en_US'));
		$this->assertSame('&lt;b&gt;', $formatter->format('{n, number}', ['n' => '<b>'], 'en_US'));
	}

	public function testUnknownPlaceholdersAreLeftAlone(): void
	{
		$this->assertSame(
			'Keep {unknown} and 5 as they are',
			(new Formatter(false))->format('Keep {unknown} and {count, number} as they are', ['count' => 5], 'en_US')
		);
		$this->assertSame('no {braces}', (new Formatter(false))->format('no {braces}', [], 'en_US'));
	}

	public function testValueConversion(): void
	{
		$formatter = new Formatter(false);
		$stringable = new class implements Stringable {
			public function __toString(): string
			{
				return 'stringable';
			}
		};

		$this->assertSame(
			'|1||1.5|female|stringable',
			$formatter->format('{null}|{true}|{false}|{float}|{enum}|{object}', [
				'null' => null,
				'true' => true,
				'false' => false,
				'float' => 1.5,
				'enum' => Gender::Female,
				'object' => $stringable,
			], 'en_US')
		);
	}

	public function testUnsupportedValueTriggersAWarning(): void
	{
		$warnings = [];
		set_error_handler(static function (int $errno, string $message) use (&$warnings): bool {
			$warnings[] = [$errno, $message];
			return true;
		});

		try {
			$result = (new Formatter(false))->format('[{list}]', ['list' => ['a']], 'en_US');
		} finally {
			restore_error_handler();
		}

		$this->assertSame('[]', $result);
		$this->assertSame([[E_USER_WARNING, 'Translation parameter "list" of type array cannot be converted to a string.']], $warnings);
	}
}
