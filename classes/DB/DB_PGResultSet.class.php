<?php
class DB_PGResultSet implements DB_iResultSet, Iterator {
    private $q_res;
    private $query;
    private $ptr;
    private $num_rows;
    private $affected_rows;
    private $db;
    
    function __construct($db,$q_resource,$query='') {
        $this->q_res=$q_resource;
        $this->num_rows=pg_num_rows($q_resource);
        $this->affected_rows=pg_affected_rows($q_resource);
        $this->query=$query;
        $this->ptr=0;
        $this->db=$db;
    }
    
    function __destruct() {
        $this->free($this->q_res);
    }
    
    public function free() {
        $this->db->free($this->q_res);
    }
    
    // iResultSet implementation
    public function numRows() {
        return $this->num_rows;
    }

    public function affectedRows() {
        return $this->affected_rows;
    }

    public function seek($pos) {
        if($pos>$this->num_rows) throw new Exception('OutOfBound Exception');
        $this->ptr=$pos;
        pg_result_seek($this->q_res,$pos);
    }

    public function fetchOne() {
        return $this->current();
    }

    public function fetchAll() {
        $ret=array();
        while($this->valid()) {
            $ret[]=$this->current();
            $this->next();
        }
        $this->rewind();
        return $ret;
    }
    
    public function getResource() {
        return $this->q_res;
    }
    
    public function getQuery() {
        return $this->query;
    }
    
    // Iterator implementation
    public function current () {
        $ret=pg_fetch_assoc($this->q_res);
        pg_result_seek($this->q_res,$this->ptr);
        return $ret;
    }
    
    public function key() {
        return $this->ptr;
    }
    
    public function next() {
        $this->ptr++;
        pg_result_seek($this->q_res,$this->ptr);
    }
    
    public function rewind() {
        $this->ptr=0;
        pg_result_seek($this->q_res,$this->ptr);
    }
    
    public function valid() {
        return $this->ptr < $this->num_rows;
    }
    
    
    
    
}