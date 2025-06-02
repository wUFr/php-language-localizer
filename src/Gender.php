<?php

namespace wUFr;

/**
 * Represents the possible gender values for translations
 */
enum Gender: string
{
	case Male = 'male';
	case Female = 'female';
	case Neutral = 'neutral';
	case Entity = 'entity';

	/**
	 * Get a human-readable description of the gender
	 */
	public function getDescription(): string
	{
		return match ($this) {
			self::Male => 'Male',
			self::Female => 'Female',
			self::Neutral => 'Gender Neutral',
			self::Entity => 'Neuter/Object/Entity'
		};
	}

	/**
	 * Get the appropriate pronoun for this gender
	 */
	public function getPronoun(): string
	{
		return match ($this) {
			self::Male => 'he',
			self::Female => 'she',
			self::Neutral => 'they',
			self::Entity => 'it'
		};
	}

	/**
	 * Get the appropriate possessive pronoun for this gender
	 */
	public function getPossessivePronoun(): string
	{
		return match ($this) {
			self::Male => 'his',
			self::Female => 'her',
			self::Neutral => 'their',
			self::Entity => 'its'
		};
	}

	/**
	 * Get the appropriate object pronoun for this gender
	 */
	public function getObjectPronoun(): string
	{
		return match ($this) {
			self::Male => 'him',
			self::Female => 'her',
			self::Neutral => 'them',
			self::Entity => 'it'
		};
	}
}