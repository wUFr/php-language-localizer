<?php

namespace wUFr\Localizer;

/**
 * Describes a translation that could not be produced. Passed to the handler
 * set with Translator::setMissingHandler().
 */
final class MissingTranslation implements \Stringable
{
	public function __construct(
		public readonly MissingReason $reason,
		public readonly string $file,
		public readonly string $key,
		public readonly string $language,
		public readonly ?string $detail = null,
	) {
	}

	/**
	 * The "file.key" identifier, e.g. "common/general.welcome".
	 */
	public function identifier(): string
	{
		return $this->file . '.' . $this->key;
	}

	/**
	 * A one-line description suitable for a log message.
	 */
	public function message(): string
	{
		$message = match ($this->reason) {
			MissingReason::FileNotFound => 'Translation file not found',
			MissingReason::KeyNotFound => 'Translation key not found',
			MissingReason::InvalidName => 'Invalid translation file or language name',
			MissingReason::SelectorMissing => 'Translation needs _gender or _counter',
			MissingReason::VariantNotFound => 'Translation variant not found',
			MissingReason::InvalidCounter => 'Translation _counter is not a number',
			MissingReason::InvalidValue => 'Translation value is not a string',
		};

		$message .= ': ' . $this->identifier() . ' (lang=' . $this->language;
		if ($this->detail !== null) {
			$message .= ', ' . $this->detail;
		}

		return $message . ')';
	}

	public function __toString(): string
	{
		return $this->message();
	}
}
