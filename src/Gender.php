<?php

namespace wUFr;

/**
 * Represents the possible gender values for translations.
 *
 * Pass a case directly as `_gender`; its value selects the variant:
 *
 *     $translator->locale('users/profile', 'bio', ['_gender' => Gender::Female]);
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
	 *
	 * @deprecated Returns English only. Put pronouns into your locale files as
	 *             gender variants instead; this method will be removed in 3.0.
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
	 *
	 * @deprecated Returns English only. Put pronouns into your locale files as
	 *             gender variants instead; this method will be removed in 3.0.
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
	 *
	 * @deprecated Returns English only. Put pronouns into your locale files as
	 *             gender variants instead; this method will be removed in 3.0.
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