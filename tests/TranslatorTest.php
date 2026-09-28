<?php

use PHPUnit\Framework\TestCase;
use wUFr\Gender;
use wUFr\Localizer\MissingReason;
use wUFr\Localizer\MissingTranslation;
use wUFr\Localizer\TranslatorInterface;
use wUFr\Translator;

/**
 * Tests for the Translator class
 */
class TranslatorTest extends TestCase
{
	private const TEST_DIR = __DIR__ . "/locales/";
	private const OVERLAY_DIR = __DIR__ . "/locales-overlay/";
	private const TEST_LANG = "en_US";

	private function translator(string $lang = self::TEST_LANG): Translator
	{
		return new Translator(dir: self::TEST_DIR, lang: $lang);
	}

	/**
	 * Test basic string translation
	 */
	public function testHelloWorld()
	{
		$translator = $this->translator();
		$this->assertEquals('hello world', $translator->locale('testValues', 'helloWorld'));
	}

	/**
	 * Test number-based translations
	 */
	public function testBasedOnNumber()
	{
		$translator = $this->translator();
		$this->assertEquals('box', $translator->locale('testValues', 'BasedOnNumber', ['_counter' => 1]));
		$this->assertEquals('boxes', $translator->locale('testValues', 'BasedOnNumber', ['_counter' => 2]));
		$this->assertEquals('a lot of boxes', $translator->locale('testValues', 'BasedOnNumber', ['_counter' => 50]));
	}

	/**
	 * Test parameter replacement
	 */
	public function testThxText()
	{
		$translator = $this->translator();
		$this->assertEquals('Thank you John for buying apple', $translator->locale('testValues', 'thxText', ['username' => 'John', 'product' => 'apple']));
	}

	/**
	 * Test combined counter and parameter replacement
	 */
	public function testThxTextCounter()
	{
		$translator = $this->translator();
		$this->assertEquals('Thank you John for buying a piece of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', '_counter' => 1]));
		$this->assertEquals('Thank you John for buying two of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', '_counter' => 2]));
		$this->assertEquals('Thank you John for buying 50 pieces of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', 'count' => 50, '_counter' => 50]));
	}

	/**
	 * Test basic gender-based translations
	 */
	public function testGenderBased()
	{
		$translator = $this->translator();
		$this->assertEquals('He is a developer', $translator->locale('genderTest', 'profession', ['_gender' => 'male']));
		$this->assertEquals('She is a developer', $translator->locale('genderTest', 'profession', ['_gender' => 'female']));
		$this->assertEquals('They are a developer', $translator->locale('genderTest', 'profession', ['_gender' => 'neutral']));
	}

	/**
	 * Test gender-based translations with parameter replacement
	 */
	public function testGenderWithParameter()
	{
		$translator = $this->translator();
		$this->assertEquals('Welcome Mr. John', $translator->locale('genderTest', 'welcome', ['_gender' => 'male', 'name' => 'John']));
		$this->assertEquals('Welcome Mrs. Jane', $translator->locale('genderTest', 'welcome', ['_gender' => 'female', 'name' => 'Jane']));
	}

	/**
	 * Test combined gender and counter-based translations
	 */
	public function testGenderWithCounter()
	{
		$translator = $this->translator();
		$this->assertEquals('He bought one item', $translator->locale('genderTest', 'purchase', ['_gender' => 'male', '_counter' => 1]));
		$this->assertEquals('She bought two items', $translator->locale('genderTest', 'purchase', ['_gender' => 'female', '_counter' => 2]));
		$this->assertEquals('They bought many items', $translator->locale('genderTest', 'purchase', ['_gender' => 'neutral', '_counter' => 5]));
	}

	/**
	 * Test Entity gender-based translations (for objects, animals, babies)
	 */
	public function testEntityGender()
	{
		$translator = $this->translator();
		$this->assertEquals('It is a machine', $translator->locale('genderTest', 'profession', ['_gender' => 'entity']));
		$this->assertEquals('It is an object', $translator->locale('genderTest', 'objectDescription', ['_gender' => 'entity']));
		$this->assertEquals('It is a dog', $translator->locale('genderTest', 'animalDescription', ['_gender' => 'entity']));
		$this->assertEquals('It\'s a baby', $translator->locale('genderTest', 'babyDescription', ['_gender' => 'entity']));
	}

	/**
	 * Test Entity gender with parameter replacement
	 */
	public function testEntityGenderWithParameter()
	{
		$translator = $this->translator();
		$this->assertEquals('Product: Router', $translator->locale('genderTest', 'welcome', ['_gender' => 'entity', 'name' => 'Router']));
		$this->assertEquals('This is its display', $translator->locale('genderTest', 'ownership', ['_gender' => 'entity', 'item' => 'display']));
	}

	/**
	 * Test Entity gender with counter translations
	 */
	public function testEntityGenderWithCounter()
	{
		$translator = $this->translator();
		$this->assertEquals('It contains one component', $translator->locale('genderTest', 'purchase', ['_gender' => 'entity', '_counter' => 1]));
		$this->assertEquals('It contains two components', $translator->locale('genderTest', 'purchase', ['_gender' => 'entity', '_counter' => 2]));
		$this->assertEquals('It contains many components', $translator->locale('genderTest', 'purchase', ['_gender' => 'entity', '_counter' => 5]));
	}

	public function testImplementsInterface()
	{
		$this->assertInstanceOf(TranslatorInterface::class, $this->translator());
	}

	public function testGenderEnumCanBePassedDirectly()
	{
		$translator = $this->translator();
		$this->assertSame('She is a developer', $translator->locale('genderTest', 'profession', ['_gender' => Gender::Female]));
		$this->assertSame('It contains two components', $translator->locale('genderTest', 'purchase', ['_gender' => Gender::Entity, '_counter' => 2]));
	}

	public function testCounterIgnoresTheOrderOfThresholds()
	{
		$translator = $this->translator();
		$this->assertSame('one', $translator->locale('testValues', 'unsorted', ['_counter' => 1]));
		$this->assertSame('few', $translator->locale('testValues', 'unsorted', ['_counter' => 3]));
		$this->assertSame('many', $translator->locale('testValues', 'unsorted', ['_counter' => 10]));
	}

	public function testCounterBelowTheLowestThresholdUsesTheLowestVariant()
	{
		$translator = $this->translator();
		$this->assertSame('item', $translator->locale('testValues', 'noZero', ['_counter' => 0]));
		$this->assertSame('item', $translator->locale('testValues', 'noZero', ['_counter' => -3]));
		$this->assertSame('no items', $translator->locale('testValues', 'withZero', ['_counter' => 0]));
	}

	public function testCounterAcceptsFloatsAndNumericStrings()
	{
		$translator = $this->translator();
		$this->assertSame('one', $translator->locale('testValues', 'unsorted', ['_counter' => 1.5]));
		$this->assertSame('few', $translator->locale('testValues', 'unsorted', ['_counter' => '4']));
	}

	public function testNonNumericCounterIsReportedAsMissing()
	{
		$reasons = [];
		$translator = $this->translator()->setMissingHandler(function (MissingTranslation $missing) use (&$reasons): string {
			$reasons[] = $missing->reason;
			return 'missing';
		});

		$this->assertSame('missing', $translator->locale('testValues', 'unsorted', ['_counter' => 'abc']));
		$this->assertSame('missing', $translator->locale('testValues', 'unsorted', ['_counter' => ['1']]));
		$this->assertSame('missing', $translator->locale('testValues', 'unsorted', ['_counter' => NAN]));
		$this->assertSame([MissingReason::InvalidCounter, MissingReason::InvalidCounter, MissingReason::InvalidCounter], $reasons);
	}

	public function testCounterIsAvailableAsCountPlaceholder()
	{
		$translator = $this->translator();
		$this->assertSame('7 items', $translator->locale('testValues', 'withZero', ['_counter' => 7]));
		$this->assertSame('seven items', $translator->locale('testValues', 'withZero', ['_counter' => 7, 'count' => 'seven']));
	}

	public function testPluralCategories()
	{
		$translator = $this->translator();
		$this->assertSame('no files', $translator->locale('testValues', 'categories', ['_counter' => 0]));
		$this->assertSame('1 file', $translator->locale('testValues', 'categories', ['_counter' => 1]));
		$this->assertSame('2 files', $translator->locale('testValues', 'categories', ['_counter' => 2]));
		$this->assertSame('1.0 files', $translator->locale('testValues', 'categories', ['_counter' => '1.0']));
		$this->assertSame('1 things', $translator->locale('testValues', 'onlyOther', ['_counter' => 1]));
	}

	public function testCzechPluralCategories()
	{
		$translator = $this->translator('cs_CZ');
		$this->assertSame('1 soubor', $translator->locale('plural', 'files', ['_counter' => 1]));
		$this->assertSame('3 soubory', $translator->locale('plural', 'files', ['_counter' => 3]));
		$this->assertSame('5 souborů', $translator->locale('plural', 'files', ['_counter' => 5]));
		$this->assertSame('0 souborů', $translator->locale('plural', 'files', ['_counter' => 0]));
		$this->assertStringEndsWith(' souboru', $translator->locale('plural', 'files', ['_counter' => 1.5]));
		$this->assertSame('souborů', $translator->locale('plural', 'thresholds', ['_counter' => 0]));
		$this->assertSame('soubory', $translator->locale('plural', 'thresholds', ['_counter' => 4]));
	}

	public function testPolishPluralCategories()
	{
		$translator = $this->translator('pl_PL');
		$this->assertSame('1 plik', $translator->locale('plural', 'files', ['_counter' => 1]));
		$this->assertSame('22 pliki', $translator->locale('plural', 'files', ['_counter' => 22]));
		$this->assertSame('25 plików', $translator->locale('plural', 'files', ['_counter' => 25]));
		$this->assertSame('12 plików', $translator->locale('plural', 'files', ['_counter' => 12]));
		$this->assertSame('1.5 pliku', $translator->locale('plural', 'files', ['_counter' => 1.5]));
	}

	public function testArrayValueWithoutSelectorIsReportedAsMissing()
	{
		$translator = $this->translator();
		$this->assertSame('testValues.BasedOnNumber', $translator->locale('testValues', 'BasedOnNumber'));
	}

	public function testGenderVariantThatNeedsACounterDoesNotThrow()
	{
		$missing = null;
		$translator = $this->translator()->setMissingHandler(function (MissingTranslation $m) use (&$missing): string {
			$missing = $m;
			return 'fallback';
		});

		$this->assertSame('fallback', $translator->locale('genderTest', 'purchase', ['_gender' => 'male']));
		$this->assertSame(MissingReason::SelectorMissing, $missing?->reason);
	}

	public function testUnknownGenderIsReportedAsMissing()
	{
		$missing = null;
		$translator = $this->translator()->setMissingHandler(function (MissingTranslation $m) use (&$missing): string {
			$missing = $m;
			return 'fallback';
		});

		$this->assertSame('fallback', $translator->locale('genderTest', 'animalDescription', ['_gender' => 'neutral']));
		$this->assertSame(MissingReason::VariantNotFound, $missing?->reason);
		$this->assertSame('genderTest.animalDescription', $missing->identifier());
	}

	public function testMissingFileAndKeyReturnTheIdentifier()
	{
		$translator = $this->translator();
		$this->assertSame('nonexistent.key', $translator->locale('nonexistent', 'key'));
		$this->assertSame('testValues.nope', $translator->locale('testValues', 'nope'));
		$this->assertStringNotContainsString('<', $translator->locale('nonexistent', 'key'));
	}

	public function testMissingHandlerReceivesTheReason()
	{
		$seen = [];
		$translator = $this->translator()->setMissingHandler(function (MissingTranslation $m) use (&$seen): string {
			$seen[] = [$m->reason, $m->file, $m->key, $m->language];
			return '[' . $m->identifier() . ']';
		});

		$this->assertSame('[nonexistent.key]', $translator->locale('nonexistent', 'key'));
		$this->assertSame('[testValues.nope]', $translator->locale('testValues', 'nope'));
		$this->assertSame([
			[MissingReason::FileNotFound, 'nonexistent', 'key', 'en_US'],
			[MissingReason::KeyNotFound, 'testValues', 'nope', 'en_US'],
		], $seen);
	}

	public function testMissingHandlerMayThrow()
	{
		$translator = $this->translator()->setMissingHandler(static function (MissingTranslation $m): string {
			throw new RuntimeException($m->message());
		});

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Translation key not found: testValues.nope (lang=en_US)');
		$translator->locale('testValues', 'nope');
	}

	public function testMissingHandlerCanBeReset()
	{
		$translator = $this->translator()->setMissingHandler(static fn(): string => 'custom')->setMissingHandler(null);
		$this->assertSame('testValues.nope', $translator->locale('testValues', 'nope'));
	}

	public function testNonStringValueIsCastOrReported()
	{
		$translator = $this->translator();
		$this->assertSame('42', $translator->locale('testValues', 'numberValue'));
	}

	public function testHas()
	{
		$translator = $this->translator();
		$this->assertTrue($translator->has('testValues', 'helloWorld'));
		$this->assertTrue($translator->has('testValues', 'BasedOnNumber'));
		$this->assertFalse($translator->has('testValues', 'nope'));
		$this->assertFalse($translator->has('nonexistent', 'helloWorld'));
		$this->assertFalse($translator->has('../outside/pwned', 'x'));
	}

	public function testFallbackLanguages()
	{
		$translator = $this->translator('de_DE')->setFallbackLanguages('en_US');

		$this->assertSame(['en_US'], $translator->getFallbackLanguages());
		$this->assertSame('Deutsch überall', $translator->locale('partial', 'everywhere'));
		$this->assertSame('English only', $translator->locale('partial', 'englishOnly'));
		$this->assertSame('hello world', $translator->locale('testValues', 'helloWorld'));
		$this->assertTrue($translator->has('partial', 'englishOnly'));
		$this->assertSame('partial.nowhere', $translator->locale('partial', 'nowhere'));
	}

	public function testWithoutFallbackLanguagesTheKeyIsMissing()
	{
		$translator = $this->translator('de_DE');
		$this->assertSame('partial.englishOnly', $translator->locale('partial', 'englishOnly'));
	}

	public function testDirectoryChainMergesFilesPerKey()
	{
		$translator = new Translator([self::OVERLAY_DIR, self::TEST_DIR], 'en_US');

		$this->assertSame(self::OVERLAY_DIR, $translator->getDirectory());
		$this->assertSame([self::OVERLAY_DIR, self::TEST_DIR], $translator->getDirectories());
		$this->assertSame('overlay value', $translator->locale('testValues', 'overridden'));
		$this->assertSame('only in overlay', $translator->locale('testValues', 'overlayOnly'));
		$this->assertSame('only in core', $translator->locale('testValues', 'coreOnly'));
		$this->assertSame('Hello from a returned array', $translator->locale('returned', 'greeting'));
	}

	public function testDirectoryChainKeepsNumericKeys()
	{
		$translator = new Translator([self::OVERLAY_DIR, self::TEST_DIR], 'en_US');

		$this->assertSame('Nothing here', $translator->locale('numericKeys', '404'));
		$this->assertSame('Server error', $translator->locale('numericKeys', '500'));
	}

	public function testDirectoryWithoutTrailingSlash()
	{
		$translator = new Translator(rtrim(self::TEST_DIR, '/'), 'en_US');
		$this->assertSame('hello world', $translator->locale('testValues', 'helloWorld'));
	}

	public function testRelativeDirectory()
	{
		$cwd = getcwd();
		chdir(__DIR__);
		try {
			$this->assertSame('hello world', (new Translator('locales', 'en_US'))->locale('testValues', 'helloWorld'));
			$this->assertSame('hello world', (new Translator('./locales/', 'en_US'))->locale('testValues', 'helloWorld'));
		} finally {
			chdir($cwd);
		}
	}

	public function testInvalidDirectoryConfigurationThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		new Translator([], 'en_US');
	}

	public function testLocaleFileMayReturnItsArray()
	{
		$this->assertSame('Hello from a returned array', $this->translator()->locale('returned', 'greeting'));
	}

	public function testLocaleFileWithoutAnArrayHasNoKeys()
	{
		$this->assertSame('invalid.anything', $this->translator()->locale('invalid', 'anything'));
	}

	public function testSettersAndGetters()
	{
		$translator = new Translator();
		$this->assertSame('locales/', $translator->getDirectory());
		$this->assertSame('en_US', $translator->getLanguage());
		$this->assertTrue($translator->isEscaping());

		$translator->setDirectory(self::TEST_DIR)->setLanguage('cs_CZ')->setEscaping(false);
		$this->assertSame(self::TEST_DIR, $translator->getDirectory());
		$this->assertSame('cs_CZ', $translator->getLanguage());
		$this->assertFalse($translator->isEscaping());
	}

	public function testLanguageSwitchUsesTheOtherFiles()
	{
		$translator = $this->translator('cs_CZ');
		$this->assertSame('1 soubor', $translator->locale('plural', 'files', ['_counter' => 1]));

		$translator->setLanguage('pl_PL');
		$this->assertSame('1 plik', $translator->locale('plural', 'files', ['_counter' => 1]));
	}
}
