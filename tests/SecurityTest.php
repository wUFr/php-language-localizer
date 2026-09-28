<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use wUFr\Localizer\MissingReason;
use wUFr\Localizer\MissingTranslation;
use wUFr\Localizer\Raw;
use wUFr\Translator;

/**
 * Path traversal, escaping and isolation guarantees.
 */
class SecurityTest extends TestCase
{
	private const TEST_DIR = __DIR__ . "/locales/";

	protected function setUp(): void
	{
		unset($GLOBALS['localizer_pwned'], $GLOBALS['localizer_scope_probe']);
	}

	private function translator(string $lang = 'en_US'): Translator
	{
		return new Translator(self::TEST_DIR, $lang);
	}

	/**
	 * @return array<string,array{string}>
	 */
	public static function maliciousFiles(): array
	{
		return [
			'parent directory' => ['../outside/pwned'],
			'nested parent directory' => ['en_US/../../outside/pwned'],
			'absolute path' => [__DIR__ . '/outside/pwned'],
			'backslash' => ['..\\outside\\pwned'],
			'null byte' => ["../outside/pwned\0"],
			'dot segment' => ['./testValues'],
			'empty segment' => ['a//b'],
			'leading slash' => ['/testValues'],
			'trailing slash' => ['testValues/'],
			'stream wrapper' => ['php://filter/resource=testValues'],
			'empty' => [''],
			'markup' => ['<img src=x onerror=alert(1)>'],
		];
	}

	#[DataProvider('maliciousFiles')]
	public function testFileNamesCannotEscapeTheLocaleDirectory(string $file): void
	{
		$reasons = [];
		$translator = $this->translator()->setMissingHandler(function (MissingTranslation $m) use (&$reasons): string {
			$reasons[] = $m->reason;
			return 'missing';
		});

		$this->assertSame('missing', $translator->locale($file, 'x'));
		$this->assertFalse($translator->has($file, 'x'));
		$this->assertArrayNotHasKey('localizer_pwned', $GLOBALS);
		$this->assertSame([MissingReason::InvalidName], $reasons);
	}

	/**
	 * @return array<string,array{string}>
	 */
	public static function maliciousLanguages(): array
	{
		return [
			'parent directory' => ['../outside'],
			'nested' => ['en_US/../../outside'],
			'absolute path' => [__DIR__ . '/outside'],
			'dot' => ['.'],
			'dot dot' => ['..'],
			'null byte' => ["en_US\0"],
			'empty' => [''],
		];
	}

	#[DataProvider('maliciousLanguages')]
	public function testLanguageNamesCannotEscapeTheLocaleDirectory(string $language): void
	{
		$reason = null;
		$translator = $this->translator($language)->setMissingHandler(function (MissingTranslation $m) use (&$reason): string {
			$reason = $m->reason;
			return 'missing';
		});

		$this->assertSame('missing', $translator->locale('pwned', 'x'));
		$this->assertArrayNotHasKey('localizer_pwned', $GLOBALS);
		$this->assertSame(MissingReason::InvalidName, $reason);
	}

	public function testInvalidLanguageStillUsesAValidFallback(): void
	{
		$translator = $this->translator('../outside')->setFallbackLanguages('en_US');
		$this->assertSame('hello world', $translator->locale('testValues', 'helloWorld'));
	}

	public function testParametersAreEscapedByDefault(): void
	{
		$result = $this->translator()->locale('testValues', 'thxText', [
			'username' => '<script>alert(1)</script>',
			'product' => '"quoted" & \'single\'',
		]);

		$this->assertSame(
			'Thank you &lt;script&gt;alert(1)&lt;/script&gt; for buying &quot;quoted&quot; &amp; &#039;single&#039;',
			$result
		);
	}

	public function testTranslationTextItselfIsTrustedMarkup(): void
	{
		$this->assertSame(
			'<strong>&lt;em&gt;Jane&lt;/em&gt;</strong>',
			$this->translator()->locale('testValues', 'markup', ['name' => '<em>Jane</em>'])
		);
	}

	public function testRawWrapperSkipsEscapingForOneValue(): void
	{
		$result = $this->translator()->locale('testValues', 'nested', [
			'a' => new Raw('<a href="/terms">terms</a>'),
			'b' => '<b>',
		]);

		$this->assertSame('<a href="/terms">terms</a> and &lt;b&gt;', $result);
	}

	public function testRawParameterListSkipsEscapingForNamedValues(): void
	{
		$result = $this->translator()->locale('testValues', 'nested', [
			'a' => '<i>a</i>',
			'b' => '<i>b</i>',
			'_raw' => ['a'],
		]);

		$this->assertSame('<i>a</i> and &lt;i&gt;b&lt;/i&gt;', $result);
	}

	public function testRawParameterTrueSkipsEscapingForTheWholeCall(): void
	{
		$result = $this->translator()->locale('testValues', 'nested', [
			'a' => '<i>a</i>',
			'b' => '<i>b</i>',
			'_raw' => true,
		]);

		$this->assertSame('<i>a</i> and <i>b</i>', $result);
	}

	public function testEscapingCanBeDisabledGlobally(): void
	{
		$translator = $this->translator()->setEscaping(false);

		$this->assertSame('<i>a</i> and <i>b</i>', $translator->locale('testValues', 'nested', ['a' => '<i>a</i>', 'b' => '<i>b</i>']));
	}

	public function testReservedParametersAreNeverPlaceholders(): void
	{
		$this->assertSame('{_raw} and x', $this->translator()->locale('testValues', 'nested', ['a' => '{_raw}', 'b' => 'x', '_raw' => ['a']]));
	}

	public function testPlaceholdersAreReplacedInASinglePass(): void
	{
		$result = $this->translator()->locale('testValues', 'thxText', [
			'username' => '{product}',
			'product' => 'apple',
		]);

		$this->assertSame('Thank you {product} for buying apple', $result);
	}

	public function testMissingTranslationOutputIsEscaped(): void
	{
		$result = $this->translator()->locale('testValues', '<img src=x onerror=alert(1)>');

		$this->assertSame('testValues.&lt;img src=x onerror=alert(1)&gt;', $result);
		$this->assertStringNotContainsString('color:red', $result);
	}

	public function testGenderValueIsNotReflectedInTheOutput(): void
	{
		$result = $this->translator()->locale('genderTest', 'profession', ['_gender' => '<script>']);

		$this->assertSame('genderTest.profession', $result);
	}

	public function testLocaleFileRunsInAnIsolatedScope(): void
	{
		$this->assertSame('probed', $this->translator()->locale('scope', 'probe', ['secret' => 'x']));
		$this->assertSame(['this' => false, 'params' => false, 'key' => false], $GLOBALS['localizer_scope_probe']);
	}
}
