<?php

use PHPUnit\Framework\TestCase;
use wUFr\Translator;

/**
 * Tests for the Translator class
 */
class TranslatorTest extends TestCase
{
	private const TEST_DIR = "./tests/locales/";
	private const TEST_LANG = "en_US";

	/**
	 * Test basic string translation
	 */
	public function testHelloWorld()
	{
		$translator = new Translator(dir: self::TEST_DIR, lang: self::TEST_LANG);
		$this->assertEquals('hello world', $translator->locale('testValues', 'helloWorld'));
	}

	/**
	 * Test number-based translations
	 */
	public function testBasedOnNumber()
	{
		$translator = new Translator(dir: self::TEST_DIR, lang: self::TEST_LANG);
		$this->assertEquals('box', $translator->locale('testValues', 'BasedOnNumber', ['_counter' => 1]));
		$this->assertEquals('boxes', $translator->locale('testValues', 'BasedOnNumber', ['_counter' => 2]));
		$this->assertEquals('a lot of boxes', $translator->locale('testValues', 'BasedOnNumber', ['_counter' => 50]));
	}

	/**
	 * Test parameter replacement
	 */
	public function testThxText()
	{
		$translator = new Translator(dir: self::TEST_DIR, lang: self::TEST_LANG);
		$this->assertEquals('Thank you John for buying apple', $translator->locale('testValues', 'thxText', ['username' => 'John', 'product' => 'apple']));
	}

	/**
	 * Test combined counter and parameter replacement
	 */
	public function testThxTextCounter()
	{
		$translator = new Translator(dir: self::TEST_DIR, lang: self::TEST_LANG);
		$this->assertEquals('Thank you John for buying a piece of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', '_counter' => 1]));
		$this->assertEquals('Thank you John for buying two of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', '_counter' => 2]));
		$this->assertEquals('Thank you John for buying 50 pieces of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', 'count' => 50 , '_counter' => 50]));
	}

	/**
	 * Test basic gender-based translations
	 */
	public function testGenderBased()
	{
		$translator = new Translator(dir: self::TEST_DIR, lang: self::TEST_LANG);
		$this->assertEquals('He is a developer', $translator->locale('genderTest', 'profession', ['_gender' => 'male']));
		$this->assertEquals('She is a developer', $translator->locale('genderTest', 'profession', ['_gender' => 'female']));
		$this->assertEquals('They are a developer', $translator->locale('genderTest', 'profession', ['_gender' => 'neutral']));
	}

	/**
	 * Test gender-based translations with parameter replacement
	 */
	public function testGenderWithParameter()
	{
		$translator = new Translator(dir: self::TEST_DIR, lang: self::TEST_LANG);
		$this->assertEquals('Welcome Mr. John', $translator->locale('genderTest', 'welcome', ['_gender' => 'male', 'name' => 'John']));
		$this->assertEquals('Welcome Mrs. Jane', $translator->locale('genderTest', 'welcome', ['_gender' => 'female', 'name' => 'Jane']));
	}

	/**
	 * Test combined gender and counter-based translations
	 */
	public function testGenderWithCounter()
	{
		$translator = new Translator(dir: self::TEST_DIR, lang: self::TEST_LANG);
		$this->assertEquals('He bought one item', $translator->locale('genderTest', 'purchase', ['_gender' => 'male', '_counter' => 1]));
		$this->assertEquals('She bought two items', $translator->locale('genderTest', 'purchase', ['_gender' => 'female', '_counter' => 2]));
		$this->assertEquals('They bought many items', $translator->locale('genderTest', 'purchase', ['_gender' => 'neutral', '_counter' => 5]));
	}

	/**
	 * Test Entity gender-based translations (for objects, animals, babies)
	 */
	public function testEntityGender()
	{
		$translator = new Translator(dir: self::TEST_DIR, lang: self::TEST_LANG);
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
		$translator = new Translator(dir: self::TEST_DIR, lang: self::TEST_LANG);
		$this->assertEquals('Product: Router', $translator->locale('genderTest', 'welcome', ['_gender' => 'entity', 'name' => 'Router']));
		$this->assertEquals('This is its display', $translator->locale('genderTest', 'ownership', ['_gender' => 'entity', 'item' => 'display']));
	}

	/**
	 * Test Entity gender with counter translations
	 */
	public function testEntityGenderWithCounter()
	{
		$translator = new Translator(dir: self::TEST_DIR, lang: self::TEST_LANG);
		$this->assertEquals('It contains one component', $translator->locale('genderTest', 'purchase', ['_gender' => 'entity', '_counter' => 1]));
		$this->assertEquals('It contains two components', $translator->locale('genderTest', 'purchase', ['_gender' => 'entity', '_counter' => 2]));
		$this->assertEquals('It contains many components', $translator->locale('genderTest', 'purchase', ['_gender' => 'entity', '_counter' => 5]));
	}
}