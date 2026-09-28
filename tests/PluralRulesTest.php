<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use wUFr\Localizer\PluralRules;

class PluralRulesTest extends TestCase
{
	/**
	 * @return array<string,array{string,int|float|string,string}>
	 */
	public static function cases(): array
	{
		return [
			'en 1' => ['en_US', 1, 'one'],
			'en 0' => ['en_US', 0, 'other'],
			'en 2' => ['en_US', 2, 'other'],
			'en 1.0' => ['en_US', '1.0', 'other'],
			'en float 1.0' => ['en_US', 1.0, 'one'],
			'en -1' => ['en', -1, 'one'],
			'de 1' => ['de_DE', 1, 'one'],
			'cs 1' => ['cs_CZ', 1, 'one'],
			'cs 2' => ['cs_CZ', 2, 'few'],
			'cs 4' => ['cs_CZ', 4, 'few'],
			'cs 5' => ['cs_CZ', 5, 'other'],
			'cs 0' => ['cs_CZ', 0, 'other'],
			'cs 22' => ['cs_CZ', 22, 'other'],
			'cs 1.5' => ['cs_CZ', 1.5, 'many'],
			'sk 3' => ['sk_SK', 3, 'few'],
			'pl 1' => ['pl_PL', 1, 'one'],
			'pl 2' => ['pl_PL', 2, 'few'],
			'pl 5' => ['pl_PL', 5, 'many'],
			'pl 12' => ['pl_PL', 12, 'many'],
			'pl 22' => ['pl_PL', 22, 'few'],
			'pl 21' => ['pl_PL', 21, 'many'],
			'pl 0' => ['pl_PL', 0, 'many'],
			'pl 1.5' => ['pl_PL', 1.5, 'other'],
			'ru 1' => ['ru_RU', 1, 'one'],
			'ru 21' => ['ru_RU', 21, 'one'],
			'ru 11' => ['ru_RU', 11, 'many'],
			'ru 3' => ['ru_RU', 3, 'few'],
			'ru 24' => ['ru_RU', 24, 'few'],
			'ru 14' => ['ru_RU', 14, 'many'],
			'ru 25' => ['ru_RU', 25, 'many'],
			'ru 1.5' => ['ru_RU', 1.5, 'other'],
			'uk 101' => ['uk_UA', 101, 'one'],
			'be 1.0' => ['be_BY', '1.0', 'one'],
			'hr 21' => ['hr_HR', 21, 'one'],
			'hr 23' => ['hr_HR', 23, 'few'],
			'hr 5' => ['hr_HR', 5, 'other'],
			'sr latin 2' => ['sr_Latn_RS', 2, 'few'],
			'sl 101' => ['sl_SI', 101, 'one'],
			'sl 102' => ['sl_SI', 102, 'two'],
			'sl 3' => ['sl_SI', 3, 'few'],
			'sl 5' => ['sl_SI', 5, 'other'],
			'fr 0' => ['fr_FR', 0, 'one'],
			'fr 1.5' => ['fr_FR', 1.5, 'one'],
			'fr 2' => ['fr_FR', 2, 'other'],
			'fr 1000000' => ['fr_FR', 1000000, 'many'],
			'es 1' => ['es_ES', 1, 'one'],
			'es 1000000' => ['es', 1000000, 'many'],
			'pt 0' => ['pt_BR', 0, 'one'],
			'pt-PT 0' => ['pt_PT', 0, 'other'],
			'pt-PT dash' => ['pt-PT', 1, 'one'],
			'it 1' => ['it_IT', 1, 'one'],
			'lt 11' => ['lt_LT', 11, 'other'],
			'lt 21' => ['lt_LT', 21, 'one'],
			'lt 2' => ['lt_LT', 2, 'few'],
			'lt 0.5' => ['lt_LT', 0.5, 'many'],
			'lv 0' => ['lv_LV', 0, 'zero'],
			'lv 1' => ['lv_LV', 1, 'one'],
			'lv 2' => ['lv_LV', 2, 'other'],
			'ro 1' => ['ro_RO', 1, 'one'],
			'ro 5' => ['ro_RO', 5, 'few'],
			'ro 20' => ['ro_RO', 20, 'other'],
			'ro 101' => ['ro_RO', 101, 'few'],
			'he 2' => ['he_IL', 2, 'two'],
			'ar 0' => ['ar', 0, 'zero'],
			'ar 3' => ['ar', 3, 'few'],
			'ar 11' => ['ar', 11, 'many'],
			'ar 100' => ['ar', 100, 'other'],
			'ga 7' => ['ga', 7, 'many'],
			'cy 6' => ['cy', 6, 'many'],
			'da 0.1' => ['da_DK', 0.1, 'one'],
			'is 21' => ['is_IS', 21, 'one'],
			'is 11' => ['is_IS', 11, 'other'],
			'hi 0' => ['hi_IN', 0, 'one'],
			'hu 1' => ['hu_HU', 1, 'one'],
			'ja 1' => ['ja_JP', 1, 'other'],
			'zh 1' => ['zh_Hans_CN', 1, 'other'],
			'unknown 1' => ['xx_XX', 1, 'one'],
			'unknown 2' => ['xx_XX', 2, 'other'],
			'numeric string' => ['cs_CZ', ' 3 ', 'few'],
			'exponent' => ['en', '1e0', 'one'],
			'huge' => ['cs_CZ', 1.0E+25, 'other'],
		];
	}

	#[DataProvider('cases')]
	public function testCategory(string $locale, int|float|string $number, string $expected): void
	{
		$this->assertSame($expected, (new PluralRules())->category($locale, $number));
	}

	public function testCustomRule(): void
	{
		$rules = (new PluralRules())
			->define('xx', static fn(int|float|string $n): string => (float) $n === 2.0 ? 'two' : 'other')
			->define('yy_YY', static fn(): string => 'invalid');

		$this->assertSame('two', $rules->category('xx_XX', 2));
		$this->assertSame('other', $rules->category('xx', 3));
		$this->assertSame('other', $rules->category('yy_YY', 1));
		$this->assertSame('one', $rules->category('yy', 1));
	}
}
