<?php

namespace wUFr\Localizer;

interface TranslatorInterface
{
	/**
	 * Translate a key from a locale file.
	 *
	 * @param string $file Locale file path relative to the language directory, without ".php"
	 * @param string $key Translation key inside the file
	 * @param array<string,mixed> $params Placeholder values plus the reserved `_counter`, `_gender` and `_raw`
	 */
	public function locale(string $file, string $key, array $params = []): string;

	/**
	 * Whether the key exists in the file for the current language or one of its fallbacks.
	 */
	public function has(string $file, string $key): bool;
}
