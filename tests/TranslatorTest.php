<?php
use PHPUnit\Framework\TestCase;
use wUFr\Translator;

class TranslatorTest extends TestCase
{
    public function testTranslation()
    {
        $translator = new Translator();
        $this->assertEquals('Hola', $translator->translate('Hello', 'es'));
    }

    public function testInvalidLanguage()
    {
        $translator = new Translator();
        $this->expectException(InvalidArgumentException::class);
        $translator->translate('Hello', 'invalid-lang');
    }
}