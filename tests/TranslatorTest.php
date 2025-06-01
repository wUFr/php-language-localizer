<?php

use PHPUnit\Framework\TestCase;
use wUFr\Translator;

class TranslatorTest extends TestCase
{
	public function testHelloWorld()
	{
		$translator = new Translator(dir: "./tests/locales/", lang: "en_US");
		$this->assertEquals('hello world', $translator->locale('testValues', 'helloWorld'));
	}

	public function testBasedOnNumber()
	{
		$translator = new Translator(dir: "./tests/locales/", lang: "en_US");
		$this->assertEquals('box', $translator->locale('testValues', 'BasedOnNumber', ['_counter' => 1]));
		$this->assertEquals('boxes', $translator->locale('testValues', 'BasedOnNumber', ['_counter' => 2]));
		$this->assertEquals('a lot of boxes', $translator->locale('testValues', 'BasedOnNumber', ['_counter' => 50]));
	}

	public function testThxText()
	{
		$translator = new Translator(dir: "./tests/locales/", lang: "en_US");
		$this->assertEquals('Thank you John for buying apple', $translator->locale('testValues', 'thxText', ['username' => 'John', 'product' => 'apple']));
	}

	public function testThxTextCounter()
	{
		$translator = new Translator(dir: "./tests/locales/", lang: "en_US");
		$this->assertEquals('Thank you John for buying a piece of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', '_counter' => 1]));
		$this->assertEquals('Thank you John for buying two of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', '_counter' => 2]));
		$this->assertEquals('Thank you John for buying 50 pieces of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', 'count' => 50 , '_counter' => 50]));
	}

	public function testGenderBased()
	{
		$translator = new Translator(dir: "./tests/locales/", lang: "en_US");
		$this->assertEquals('He is a developer', $translator->locale('genderTest', 'profession', ['_gender' => 'male']));
		$this->assertEquals('She is a developer', $translator->locale('genderTest', 'profession', ['_gender' => 'female']));
		$this->assertEquals('They are a developer', $translator->locale('genderTest', 'profession', ['_gender' => 'neutral']));
	}

	public function testGenderWithParameter()
	{
		$translator = new Translator(dir: "./tests/locales/", lang: "en_US");
		$this->assertEquals('Welcome Mr. John', $translator->locale('genderTest', 'welcome', ['_gender' => 'male', 'name' => 'John']));
		$this->assertEquals('Welcome Mrs. Jane', $translator->locale('genderTest', 'welcome', ['_gender' => 'female', 'name' => 'Jane']));
	}

	public function testGenderWithCounter()
	{
		$translator = new Translator(dir: "./tests/locales/", lang: "en_US");
		$this->assertEquals('He bought one item', $translator->locale('genderTest', 'purchase', ['_gender' => 'male', '_counter' => 1]));
		$this->assertEquals('She bought two items', $translator->locale('genderTest', 'purchase', ['_gender' => 'female', '_counter' => 2]));
		$this->assertEquals('They bought many items', $translator->locale('genderTest', 'purchase', ['_gender' => 'neutral', '_counter' => 5]));
	}
}