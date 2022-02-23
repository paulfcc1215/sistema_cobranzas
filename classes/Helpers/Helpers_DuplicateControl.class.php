<?php
class Helpers_DuplicateControl {
    private $db;
    private $tmp_table;
    private $tmp_stmt_insert;
    private $tmp_stmt_select;
    private $tmp_key;
    function __construct() {
        $this->tmp_key=uniqid().'_'.rand(1000,9999);
        
        $this->db=DB::getInstance();
        $this->tmp_table='tmp_'.$this->tmp_key;
        
        $this->db->query('CREATE TEMPORARY TABLE '.$this->tmp_table.' ("content" text)');
        $this->db->query('CREATE INDEX ON '.$this->tmp_table.' USING BTREE ("content")');
        
        $this->tmp_stmt_insert='qi'.$this->tmp_key;
        $this->tmp_stmt_select='qs'.$this->tmp_key;
        $this->db->prepare($this->tmp_stmt_insert,'INSERT INTO '.$this->tmp_table.' VALUES ($1)');
        $this->db->prepare($this->tmp_stmt_select,'SELECT COUNT(*) AS c FROM '.$this->tmp_table.' WHERE "content"=$1');
    }
    
    function __destruct() {
        $this->db->query('DROP TABLE '.$this->tmp_table);
    }
    
    function push($content) {
        $this->db->execute($this->tmp_stmt_insert,array($content));
    }

    function exists($content) {
        $c=$this->count($content);
        if($c>0) return true;
        return false;
    }
    
    function count($content) {
        $q0=$this->db->execute($this->tmp_stmt_select,array($content));
        $qa0=$this->db->fetchOne($q0);
        return $qa0['c'];
    }
    
    function getAll() {
        $q0=$this->db->query('SELECT * FROM '.$this->tmp_table);
        return $this->db->fetchAll($q0);
    }
}