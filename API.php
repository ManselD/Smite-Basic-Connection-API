<?php
class SmiteAPI{
	private $configData;
	private $session;
	private $IP;

	public function __construct($config){
		$this->configData = new stdClass();
		$this->configData = $config;
		$this->session = $this->createSession();
		$this->IP = $_SERVER['REMOTE_ADDR'];
	}

	public function makeRequest($request, $url, $format = "JSON", $decodeJSON = true){
		/* $request = "createsession";
		 * $url = "/DEV_ID/DEV_SIG/TIMESTAMP";
		 * $format = "JSON";
		 * Example: http://api.smitegame.com/smiteapi.svc/createsessionJSON/DEV_ID/DEV_SIG/TIMESTAMP
		 */

		$url = str_replace("DEV_ID", $this->configData->DEV_ID, $url);
		$url = str_replace("DEV_SIG", $this->generateSignature($request), $url);
		$url = str_replace("DEV_SES", $this->session, $url);
		$url = str_replace("TIMESTAMP", $this->getTimestamp(), $url);

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

	private function createSession(){
		if((!isset($_SESSION['SMITE_SESSION_TIMEOUT_'.$this->IP])) || ($_SESSION['SMITE_SESSION_TIMEOUT_'.$this->IP] <= time())){
			$_SESSION['SMITE_SESSION_TIMEOUT_'.$this->IP] = time()+60*15;
			unset($_SESSION['SMITE_SESSION_'.$this->IP]);
		}


		if(empty($_SESSION['SMITE_SESSION_'.$this->IP])){
			$resp = $this->makeRequest("createsession", "/DEV_ID/DEV_SIG/TIMESTAMP", "JSON", true);
			$_SESSION['SMITE_SESSION_'.$this->IP] = $resp->session_id;
			$this->session = $resp->session_id;
			return $resp->session_id;
		}else{
			return $_SESSION['SMITE_SESSION_'.$this->IP];
		}
	}
}

