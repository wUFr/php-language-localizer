<?php

namespace wUFr;

class Translator {
	/** @var array<string,array<string,mixed>> */
	public readonly array $values;

	private string $dir;
	private string $lang;

	public function __construct(
		string $dir  = "/locales/",
		string $lang = "en_US"){
			$this->values = [];
			$this->dir = $dir;
			$this->lang = $lang;
	}
	
	/**
	 * Set the directory path for localization files
	 * 
	 * @param string $dir The directory path
	 * @return self For method chaining
	 */
	public function setDirectory(string $dir): self {
		$this->dir = $dir;
		return $this;
	}
	
	/**
	 * Set the language for translations
	 * 
	 * @param string $lang The language code (e.g., "en_US")
	 * @return self For method chaining
	 */
	public function setLanguage(string $lang): self {
		$this->lang = $lang;
		return $this;
	}
	
	/**
	 * Get the current language
	 * 
	 * @return string The current language code
	 */
	public function getLanguage(): string {
		return $this->lang;
	}
	
	/**
	 * Get the current directory path
	 * 
	 * @return string The current directory path
	 */
	public function getDirectory(): string {
		return $this->dir;
	}

	/**
	 * Get a localized string based on the provided key and parameters
	 * 
	 * @param string $file The locale file to load
	 * @param string $key The translation key to retrieve
	 * @param array<string,mixed> $params Parameters for string replacement and pluralization
	 * @return string The localized string
	 */
	public function locale(
		string $file,
		string $key,
		array  $params = []
	) : string {
		$langFile = $this->dir . $this->lang. "/" .$file. ".php";

		if(!file_exists($langFile)) {
			return '<b style="color:red">lang file NOT found: ' .$file. '</b>';
		}

		// Load translation file if not already loaded
		if(!isset($this->values[$langFile])) {
			include($langFile);
			$this->values[$langFile] = $l ?? [];
		}

		if(!array_key_exists($key, $this->values[$langFile])) {
			return '<b style="color:red">lang key NOT found: ' .$file. '-' .$key. '</b>';
		}

		$value = $this->values[$langFile][$key];
		
		// Process array-based translations (counter or gender)
		if(is_array($value)) {
			$text = $this->processArrayTranslation($value, $params);
		} else {
			$text = $value;
		}

		// Replace parameters in the string
		return $this->replaceParameters($text, $params);
	}

	/**
	 * Process array-based translations for counter or gender
	 * 
	 * @param array<string|int,mixed> $value The translation array
	 * @param array<string,mixed> &$params Parameters for the translation
	 * @return string The processed translation
	 */
	private function processArrayTranslation(array $value, array &$params): string {
		return match(true) {
			isset($params['_gender']) => $this->processGenderTranslation($value, $params),
			isset($params['_counter']) => $this->processCounterTranslation($value, $params),
			default => '<b style="color:red">lang counter or gender NOT set</b>'
		};
	}

	/**
	 * Process gender-based translations
	 * 
	 * @param array<string,mixed> $value The translation array
	 * @param array<string,mixed> &$params Parameters for the translation
	 * @return string The processed translation
	 */
	private function processGenderTranslation(array $value, array &$params): string {
		$gender = $params['_gender'];
		
		// Check if gender key exists
		if(!isset($value[$gender])) {
			return '<b style="color:red">lang gender NOT found: ' .$gender. '</b>';
		}
		
		$genderValue = $value[$gender];
		
		// If gender value is an array (for combined gender+counter cases)
		if(is_array($genderValue) && isset($params['_counter'])) {
			$result = $this->processCounterTranslation($genderValue, $params);
		} else {
			$result = $genderValue;
		}
		
		// Unset gender parameter as we've processed it
		unset($params['_gender']);
		
		return $result;
	}

	/**
	 * Process counter-based translations
	 * 
	 * @param array<int,string> $value The translation array
	 * @param array<string,mixed> &$params Parameters for the translation
	 * @return string The processed translation
	 */
	private function processCounterTranslation(array $value, array &$params): string {
		$counter = $params['_counter'];
		
		$options = [];
		foreach($value as $num => $text) {
			if($num <= $counter) {
				$options[] = $text;
			}
		}
		
		// Unset counter parameter as we've processed it
		unset($params['_counter']);
		
		return end($options) ?: '';
	}

	/**
	 * Replace parameters in the translated string
	 * 
	 * @param string $text The text to process
	 * @param array<string,mixed> $params Parameters to replace
	 * @return string The text with replaced parameters
	 */
	private function replaceParameters(string $text, array $params): string {
		if(empty($params)) {
			return $text;
		}
		
		foreach($params as $key => $value) {
			$text = str_replace("{{$key}}", (string)$value, $text);
		}
		
		return $text;
	}
}