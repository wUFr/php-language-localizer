<?php

namespace wUFr\Localizer;

/**
 * Marks a parameter value as trusted markup that must be inserted without
 * HTML escaping:
 *
 *     $translator->locale('mail/footer', 'unsubscribe', [
 *         'link' => new Raw('<a href="' . htmlspecialchars($url) . '">unsubscribe</a>'),
 *     ]);
 *
 * Only wrap values you built yourself. Never wrap user input.
 */
final class Raw implements \Stringable
{
	public function __construct(public readonly string $value)
	{
	}

	public function __toString(): string
	{
		return $this->value;
	}
}
