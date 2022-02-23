<?php
class SessionManager {
    static $_instance;
    private $session_name;
    private $session_data;
    
    private function __construct($session_name='generic_session_name') {
        $this->session_data=array();
        $this->session_name=$session_name;
        session_name($session_name);
        session_start();
        $this->session_data=$_SESSION['_sess_data'];
        session_write_close();
    }
    
    static function getInstance($session_name=null) {
        if(is_null(SessionManager::$_instance)) {
            if(is_null($session_name)) throw new Exception('Must define Session Name while instancing SessionName for the first time');
            SessionManager::$_instance=new SessionManager($session_name);
        }
        return SessionManager::$_instance;
    }
    
    function __set($k,$v) {
        $this->session_data[$k]=$v;
        session_start();
        $_SESSION['_sess_data'][$k]=$v;
        session_write_close();
    }
    
    function __get($k) {
        return $this->session_data[$k];
    }
    
    function destroy() {
        session_start();
        $_SESSION=array();
        session_write_close();
    }
    
    function __toString() {
        return print_r($this,true);
        
    }
    
    function _unset($k) {
        session_start();
        unset($_SESSION['_sess_data'][$k]);
        session_write_close();
        unset($this->session_data[$k]);
    }
	
	
}