<?php
use PHPUnit\Framework\TestCase;
use wUFr\Translator;

class TranslatorTest extends TestCase
{
    public function testTranslation()
    {
        $translator = new Translator(dir: "./locales/", lang: "en_US");
        $this->assertEquals('hello world', $translator->locale('testValues', 'helloWorld'));
    }

    /*public function testInvalidLanguage()
    {
        $translator = new Translator(dir: "./locales/", lang: "en_US");
        $this->expectException(InvalidArgumentException::class);
        $translator->translate('Hello', 'invalid-lang');
    }*/
}