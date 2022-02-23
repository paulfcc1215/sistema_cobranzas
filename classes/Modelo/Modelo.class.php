<?php
class Modelo {
    static $_tbl_models=array();
    private $_readonly;
    private $_pk;
    protected $_modelo;
    protected $db;
    protected $_data;
    private $_schema;
    private $_table;
    
    function __construct($table_schema,$table,$primary_key,$readonly=array()) {
        $this->db=DB::getInstance();
        $this->_schema=$table_schema;
        $this->_table=$table;
        if(!array_key_exists($table,Modelo::$_tbl_models)) {
            Modelo::$_tbl_models[$table]=array();    
        }
        if(empty(Modelo::$_tbl_models[$table])) {
            Helpers::mk_model(Modelo::$_tbl_models[$table],$table_schema,$table);
        }
        $this->_modelo=Modelo::$_tbl_models[$table];
        $this->_pk=$primary_key;
        $this->_readonly=array_unique(array_merge($readonly,array($primary_key)));
        $this->_data=array();
        foreach($this->_modelo as $m) {
            $this->_data[$m]=null;
        }
    }
    
    function __get($k) {
        if(array_key_exists($k,$this->_data))
            return $this->_data[$k];
        throw new Exception('Modelo::__get no existe columna "'.$k.'"');
    }
    
    function __set($k,$v) {
        if(array_key_exists($k,$this->_data)) {
            if(in_array($k,$this->_readonly))
                throw new Exception('Modelo::__set se intento cambiar una propiedad de solo lectura "'.$k.'"');
            if(is_null($v)) {
                $query='UPDATE "'.$this->_schema.'"."'.$this->_table.'" SET "'.$k.'"=NULL WHERE "'.$this->_pk.'"=\''.$this->_data[$this->_pk].'\'';
            }else{
                $query='UPDATE "'.$this->_schema.'"."'.$this->_table.'" SET "'.$k.'"=\''.$this->db->escape($v).'\' WHERE "'.$this->_pk.'"=\''.$this->_data[$this->_pk].'\'';
            }
            $this->db->query($query);
            $this->_data[$k]=$v;
            return $this;
        }
        throw new Exception('Modelo::__set no existe columna "'.$k.'"');
    }
    
    function __toString() {
        return print_r($this,true);
    }
    
}


