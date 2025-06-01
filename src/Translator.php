<?php

namespace wUFr;

class Translator {


	public $values = [];

	public function __construct(
		private string $dir  = "/locales/",
		private string $lang = "en_US"){
	}

	public function locale(
		string $file,
		string $key,
		array  $params = []
	) : string {

		$langFile = $this->dir . $this->lang. "/" .$file. ".php";

		if(file_exists($langFile)){

			// SET VALUES IF THEY ARE NOT SET YET
			if(!isset($this->values[$langFile])){
				include($langFile);
				$this->values[$langFile] = $l;
			}


			if(array_key_exists($key, $this->values[$langFile])){
				// OUTPUT VALUE BASED ON COUNTER (1 = "FIRST", 2 = "SECOND", ...) OR GENDER
				if(is_array($this->values[$langFile][$key])){
					// Check for gender-based translation
					if(isset($params["_gender"])) {
						$gender = $params["_gender"];
						
						// Check if gender key exists
						if(isset($this->values[$langFile][$key][$gender])) {
							$value = $this->values[$langFile][$key][$gender];
							
							// If gender value is an array (for combined gender+counter cases)
							if(is_array($value) && isset($params["_counter"])) {
								$counter = $params["_counter"];
								
								$textOptions = [];
								foreach($value as $num => $text) {
									if($num <= $counter) {
										$textOptions[] = $text;
									}
								}
								$text = end($textOptions);
							} else {
								$text = $value;
							}
							
							// Unset gender parameter as we've processed it
							unset($params["_gender"]);
						} else {
							$text = '<b style="color:red">lang gender NOT found: ' .$gender. '</b>';
						}
					}
					// Handle counter-based translations (existing functionality)
					elseif(isset($params["_counter"])){
						$counter = $params["_counter"];
						$values  = $this->values[$langFile][$key];
						
						$text = [];
						foreach($values as $num => $value){
							if($num <= $counter){
								$text[] = $value;
							}
						}
						$text = end($text);
					}
					else {
						$text = '<b style="color:red">lang counter or gender NOT set</b>';
					}
				}
				else {
					$text = $this->values[$langFile][$key];
				}


				// UNSET COUNTER, WE DONT NEED IT ANYMORE AND
				// IT WILL SAVE PERFORMANCE, IF WE DON'T HAVE ANY VALUES TO SEARCH AND REPLACE
				unset($params["_counter"]);

				// CHECK FOR PARAMS TO BE REPLACED, IF THERE ARE ANY
				if(count($params)){
					$langText = $text;

					foreach($params as $replaceKey => $replaceValue){
						$replaceKey = "{".$replaceKey."}";
						$langText    = str_replace($replaceKey, $replaceValue, $langText);
					}

					$text = $langText;
				}
			}
			else {
				$text = '<b style="color:red">lang key NOT found: ' .$file. '-' .$key. '</b>';
			}
		}
		else {
			$text = '<b style="color:red">lang file NOT found: ' .$file. '</b>';
		}

		return $text;
	}

}