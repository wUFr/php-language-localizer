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
		$this->assertEquals('Thank you John for buying a piece of apple', $translator->locale('testValues', 'thxText', ['username' => 'John', 'product' => 'apple']));
	}

	public function testThxTextCounter()
	{
		$translator = new Translator(dir: "./tests/locales/", lang: "en_US");
		$this->assertEquals('Thank you John for buying a piece of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', '_counter' => 1]));
		$this->assertEquals('Thank you John for buying two of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', '_counter' => 2]));
		$this->assertEquals('Thank you John for buying 50 pieces of apple', $translator->locale('testValues', 'thxTextCounter', ['username' => 'John', 'product' => 'apple', '_counter' => 50]));
	}
}