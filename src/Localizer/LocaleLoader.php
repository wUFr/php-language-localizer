<?php

namespace wUFr\Localizer;

/**
 * Finds, validates and loads locale files.
 *
 * Several locale directories can be chained (highest priority first). A file
 * is then merged across every directory that has it, so a higher-priority
 * directory may override single keys and leave the rest of the file to the
 * lower ones.
 *
 * File and language names are validated before any path is built, so a name
 * coming from user input can never reach outside the locale directories.
 *
 * @internal Used by Translator; not covered by the backward compatibility promise.
 */
final class LocaleLoader
{
	/** A language directory name: "en_US", "cs", "pt-BR", "sr_Latn_RS", … */
	private const LANGUAGE_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9_-]{0,34}$/D';

	/** One path segment of a file name. No leading dot, so "." and ".." are impossible. */
	private const SEGMENT_PATTERN = '/^[A-Za-z0-9_-][A-Za-z0-9_.-]{0,127}$/D';

	/** @var list<string> */
	private array $dirs;

	/** @var array<string,array<array-key,mixed>|null> Loaded files, keyed by "language|file" */
	private array $cache = [];

	/**
	 * @param list<string> $dirs Locale directories, highest priority first
	 */
	public function __construct(array $dirs)
	{
		$this->dirs = $dirs;
	}

	/**
	 * @return list<string>
	 */
	public function directories(): array
	{
		return $this->dirs;
	}

	public static function isValidLanguage(string $language): bool
	{
		return preg_match(self::LANGUAGE_PATTERN, $language) === 1;
	}

	public static function isValidFile(string $file): bool
	{
		if ($file === '' || strlen($file) > 1024) {
			return false;
		}

		foreach (explode('/', $file) as $segment) {
			if (preg_match(self::SEGMENT_PATTERN, $segment) !== 1) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Load a locale file merged across all directories.
	 *
	 * Callers must validate both names first (isValidLanguage(), isValidFile()).
	 *
	 * @return array<array-key,mixed>|null Null when no directory has the file
	 */
	public function load(string $language, string $file): ?array
	{
		if (!self::isValidLanguage($language) || !self::isValidFile($file)) {
			throw new \InvalidArgumentException('Invalid locale file or language name.');
		}

		$cacheKey = $language . '|' . $file;
		if (array_key_exists($cacheKey, $this->cache)) {
			return $this->cache[$cacheKey];
		}

		$merged = null;

		// Lowest priority first, so every higher-priority directory overrides it.
		foreach (array_reverse($this->dirs) as $dir) {
			$path = self::join($dir, $language . '/' . $file . '.php');
			if (!is_file($path)) {
				continue;
			}

			// array_replace (not array_merge) keeps numeric keys such as "404".
			$merged = array_replace($merged ?? [], self::read($path));
		}

		return $this->cache[$cacheKey] = $merged;
	}

	/**
	 * Include a locale file in an isolated scope. The file may either return
	 * the array or assign it to `$l`.
	 *
	 * @return array<array-key,mixed>
	 */
	private static function read(string $path): array
	{
		return (static function (string $__path): array {
			$l = null;
			$returned = include $__path;

			if (is_array($returned)) {
				return $returned;
			}

			/** @var mixed $l The file may have assigned it */
			return is_array($l) ? $l : [];
		})($path);
	}

	private static function join(string $dir, string $relative): string
	{
		if ($dir === '') {
			return './' . $relative;
		}

		if (!self::isAbsolute($dir) && !str_starts_with($dir, './') && !str_starts_with($dir, '../')) {
			// Anchor bare relative paths to the working directory, so include
			// never resolves them through include_path to a different file than
			// the one is_file() checked.
			$dir = './' . $dir;
		}

		return rtrim($dir, '/\\') . '/' . $relative;
	}

	private static function isAbsolute(string $path): bool
	{
		return str_starts_with($path, '/')
			|| str_starts_with($path, '\\')
			|| preg_match('~^[A-Za-z]:[\\\\/]~', $path) === 1
			|| str_contains($path, '://');
	}
}
