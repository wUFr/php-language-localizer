<?php

use PHPUnit\Framework\TestCase;
use wUFr\Gender;

class GenderTest extends TestCase
{
	public function testValues(): void
	{
		$this->assertSame(['male', 'female', 'neutral', 'entity'], array_map(static fn(Gender $g): string => $g->value, Gender::cases()));
		$this->assertSame(Gender::Entity, Gender::from('entity'));
	}

	public function testDescriptions(): void
	{
		$this->assertSame('Male', Gender::Male->getDescription());
		$this->assertSame('Neuter/Object/Entity', Gender::Entity->getDescription());
	}

	public function testEnglishPronouns(): void
	{
		$this->assertSame('she', Gender::Female->getPronoun());
		$this->assertSame('their', Gender::Neutral->getPossessivePronoun());
		$this->assertSame('it', Gender::Entity->getObjectPronoun());
	}
}
