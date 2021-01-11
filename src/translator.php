
<?php


namespace wUFr;

class translator {


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
				// OUTPUT VALUE BASED ON COUNTER (1 = "FIRST", 2 = "SECOND", ...)
				if(is_array($this->values[$langFile][$key])){
					if(isset($params["_counter"])){
						$counter = $params["_counter"];
						$values  = $this->values[$langFile][$key];

						foreach($values as $num => $value){
							if($num<=$counter){
								$text[] = $value;
							}
						}
						$text = end($text);
					}
					else {
						$text = '<b style="color:red">lang counter NOT set</b>';
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
