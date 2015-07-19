<?php
class SmiteAPI{
	private $configData;

	public function __construct($config){
		$this->configData = new stdClass();
		$this->configData = $config;
		$this->createSession();
	}

	public function makeRequest($request, $url, $format = "JSON", $decodeJSON = true){
		/* $request = "createsession";
		 * $url = "/DEV_ID/DEV_SIG/TIMESTAMP";
		 * $format = "JSON";
		 * Example: http://api.smitegame.com/smiteapi.svc/createsessionJSON/DEV_ID/DEV_SIG/TIMESTAMP
		 */

		$url = str_replace("DEV_ID", $this->configData->DEV_ID, $url);
		$url = str_replace("DEV_SIG", $this->generateSignature($request), $url);
		$url = str_replace("DEV_SES", $this->createSession(), $url);
		$url = str_replace("TIMESTAMP", $this->getTimestamp(), $url);

		//echo $this->configData->SITE_URL.$request.$format.$url;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->configData->SITE_URL.$request.$format.$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		$resp = curl_exec($ch);
		curl_close($ch);

		if($decodeJSON){
			//If the second parameter is true, it'll return the results as an array, otherwise a stdClass.
			$resp = json_decode($resp, false);
		}
		return $resp;
	}

	public function secureString($str){
		//First it turns data into the HTML equivalent, then removes slashes, then spaces.
		return trim(stripslashes(htmlspecialchars($str)));
	}

	private function getTimestamp(){
		return gmdate('YmdHis');
	}

	private function generateSignature($request){
		return md5($this->configData->DEV_ID.$request.$this->configData->API_KEY.$this->getTimestamp());
	}

	public function getSessionID(){
		if(!isset($_SESSION['SMITE_SESSION'])){
			return $this->createSession();
		}else{
			return $_SESSION['SMITE_SESSION'];
		}
	}

	private function createSession(){
		if(!isset($_SESSION['SMITE_SESSION_TIMEOUT'])){
			if(!isset($_SESSION['SMITE_SESSION'])){
				$_SESSION['SMITE_SESSION_TIMEOUT'] = time() + 60 * 15;
			}

			$resp = $this->makeRequest("createsession", "/DEV_ID/DEV_SIG/TIMESTAMP", "JSON", true);
			$_SESSION['SMITE_SESSION'] = $resp->session_id;
			return $resp->session;
		}else{
			if(time() >= $_SESSION['SMITE_SESSION_TIMEOUT']){
				$_SESSION['SMITE_SESSION_TIMEOUT'] = time()+60*15;
				$resp = $this->makeRequest("createsession", "/DEV_ID/DEV_SIG/TIMESTAMP", "JSON", true);
				$_SESSION['SMITE_SESSION'] = $resp->session_id;
				return $resp->session_id;
			}else{
				return $_SESSION['SMITE_SESSION'];
			}
		}
	}
}