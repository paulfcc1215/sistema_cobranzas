<?php
class PortoaguasFTP {
	private $baseUrl = 'https://monitoreo.portoaguas.gob.ec:8443';
	private $user;
	private $pass;
	private $curl;
	
	
	function __construct($user, $pass) {
		unlink('/tmp/cookies');
		$this->user = $user;
		$this->pass = $pass;
		$this->curl = curl_init();
		curl_setopt_array($this->curl,array(
			CURLOPT_COOKIEJAR=>'/tmp/cookies',
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_VERBOSE=>true,
			CURLOPT_STDERR=>STDOUT
		));
	}
	
	function _sendPost($url,$data) {
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
		$ret = curl_exec($this->curl);
		return $ret;
	}
	
	function _sendGet($url) {
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_HTTPGET, true);
		$ret = curl_exec($this->curl);
		return $ret;
	}
	
	function _getToken($data) {
		
		preg_match('#<input type="hidden" name="_token" value="(.*?)" id="token">#', $data, $matches);
		return $matches[1];
	}
	
	function login() {
		$url = $this->baseUrl.'/';
		$token = $this->_getToken($this->_sendGet($url));
		
		$url = $this->baseUrl.'/login';
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
			'X-CSRF-TOKEN: '.$token,
			'X-Requested-With: XMLHttpRequest',
		));

		$data = 'cedula='.$this->user.'&clave='.$this->pass;
		$ret = $this->_sendPost($url,$data);
		$ret = json_decode($ret, true);
		if(is_null($ret) || $ret===false)
			throw new Exception('InvalidLogin');
		if(!$ret['respuesta'])
			throw new Exception('InvalidLogin - '.$ret['sms']);
	}
	
	function browse($path) {
		$url = $this->baseUrl.'/directorio';
		$data = 'carpeta='.$path;
		$ret = $this->_sendPost($url, $data);
		$ret = json_decode($ret,true);
		return $ret;
	}
	
	function download($path) {
		$path = base64_encode($path);
		$url=$this->baseUrl.'/descargar_archivo/'.$path;
		$ret = $this->_sendGet($url);
		file_put_contents('/tmp/downloaded_portoaguas.txt',$ret);
		return $ret;
	}
}

