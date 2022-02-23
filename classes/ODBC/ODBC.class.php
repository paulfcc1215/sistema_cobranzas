<?php
class ODBC {
	function __construct($dsn,$user,$password) {
		$this->odbc=odbc_connect($dsn,$user,$password);
		if(!$this->odbc)
			throw new Exception('Couldn connect to dsn '.$dsn);
	}
	
	function query($query) {
		return new ODBC_Query($this->odbc,$query);
	}
}
