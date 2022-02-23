<?php
class AutoModel {
    private static $_MODEL_CACHE=array();
    protected $_schema;
    protected $_table;
    protected $_db;
    protected $_pks;
    protected $_columns;
    protected $_rels;
    protected $_extended_class;
    protected $_prepared_stms;

    protected function __construct($schema,$table,$db,$extended_class=-1) {
        if(is_null($db))
            throw new Exception('$db no puede ser nulo');
        if(!is_a($db,'DB_Interface'))
            throw new Exception('$db debe ser una clase que implemente DB_Interface');
        $this->_schema=$schema;
        $this->_table=$table;
        $this->_db=$db;
        $this->_columns=$db->getColumns($schema,$table);
        $this->_pks=array();
        foreach($this->_columns as $c) {
            if($c['is_pk']) $this->_pks[]=$c['column'];
            
        }
        $this->_rels=$db->relations()->getRelations($schema,$table);
        foreach($this->_rels as &$rel) {
            $aux=$rel['schema'].$rel['table'];
            foreach($rel['rel'] as $r) {
                $aux.=$r[0];
            }
            $rel['hash']=$aux;
        }
        $this->_extended_class=$extended_class;
        $this->_prepared_stms=array();
        $this->iniPrepareStatements();
    }
    
    private function genStmtName($stmt) {
        return $this->_schema.'_'.$this->_table.'_'.$stmt;
    }
    
    public function isStmtPrepared($name) {
        return in_array($name,$this->_prepared_stms);
    }
    
    public function prepareStmt($queryname,$query) {
        $this->_db->prepare($this->genStmtName($queryname),$query);
        $this->_prepared_stms[]=$queryname;
    }
    
    private function iniPrepareStatements() {
        
        // getBy
        $conditions=array();
        $i=0;
        foreach($this->_pks as $pk) {
            $i++;
            $conditions[]='"'.$pk.'"=$'.$i;
        }
        $query='SELECT * FROM "'.$this->_schema.'"."'.$this->_table.'" WHERE ('.implode(' AND ',$conditions).')';
        $queries['getById']=$query;
        
        // getAll
        $query='SELECT "'.implode('","',$this->_pks).'" FROM "'.$this->_schema.'"."'.$this->_table.'"';
        $queries['getAll']=$query;
        
        // delete
        $query='DELETE FROM "'.$this->_schema.'"."'.$this->_table.'" WHERE ('.implode(' AND ',$conditions).')';
        $queries['delete']=$query;
        
        
        // rels
        foreach($this->_rels as $rel) {
            $conditions=array();
            $i=1;
            foreach($rel['rel'] as $pair) {
                $conditions[]='"'.$pair[1].'"=$'.$i;
                $i++;
            }
			if(empty($rel['pks'])) {
				throw new Exception('Existe un error en las relaciones de la tabla '.$this->_schema.'.'.$this->_table.'<br><br>No hay llaves primarias para '.($rel['schema']).'.'.($rel['table']).'<hr>AutoModel no puede funcionar sin las PKs');
			}
            $query='SELECT "'.implode('","',$rel['pks']).'" FROM "'.$rel['schema'].'"."'.$rel['table'].'"
            WHERE
            (
            '.implode(' AND ',$conditions).'
            )';
            $queries['relget'.$rel['hash']]=$query;
        }
        
        foreach($queries as $queryname=>$query) {
            $this->prepareStmt($queryname,$query);
        }
    }
    
    public static function getInstance($schema,$table,$db,$extended_class=-1) {
        if(!is_a($db,'DB_Interface'))
            throw new Exception(__METHOD__.' - $db debe ser una clase que implemente DB_Interface');
        
        $conn_string=$db->getConnStringHash();
        if(
            array_key_exists($conn_string,AutoModel::$_MODEL_CACHE)
            && array_key_exists($schema,AutoModel::$_MODEL_CACHE[$conn_string])
            && array_key_exists($table,AutoModel::$_MODEL_CACHE[$conn_string][$schema])
            && array_key_exists($extended_class,AutoModel::$_MODEL_CACHE[$conn_string][$schema][$table])
        ) {
            return AutoModel::$_MODEL_CACHE[$conn_string][$schema][$table][$extended_class];
        }
        AutoModel::$_MODEL_CACHE[$conn_string][$schema][$table][$extended_class] = new AutoModel($schema,$table,$db,$extended_class);
        return AutoModel::$_MODEL_CACHE[$conn_string][$schema][$table][$extended_class];
    }
    
    public function getById($pks_values) {
        if(!is_array($pks_values)) $pks_values=array($pks_values);
        $q0=$this->_db->execute($this->genStmtName('getById'),$pks_values);
        if($q0->numRows()==0) return false;

        $data=$q0->current();
        if($this->_extended_class!=-1) {
            return eval('return new '.$this->_extended_class.'($this,$data);');
        }else{
            return new AutoModelRecord($this,$data);
        }
        
    }
    
    public function getByAndCond($conditions,$order_by=null) {
        $_conditions=array();
        foreach($conditions as $k=>$v) {
            if(is_null($v)) {
                $_conditions[]='"'.$k.'"=\''.($v).'\'';
            }else{
                $_conditions[]='"'.$k.'"=\''.$this->_db->escape($v).'\'';
            }
        }
        $query='SELECT "'.implode('","',$this->_pks).'" FROM "'.$this->_schema.'"."'.$this->_table.'" WHERE ('.implode(' AND ',$_conditions).')';
        if(!is_null($order_by)) {
            $query.=' ORDER BY '.$order_by;
        }
        $ret=array();
        foreach($this->_db->query($query) as $rec) {
            $ret[]=$this->getById($rec);
        }
        return $ret;
    }
    
    public function getAll($order_by=null) {
		if(is_null($order_by)) {
			$recs=$this->execute('getAll',array());
		}else{
			$recs=$this->_db->query('SELECT "'.implode('","',$this->_pks).'" FROM "'.$this->_schema.'"."'.$this->_table.'" ORDER BY '.$order_by);
		}
        $ret=array();
        foreach($recs as $rec) {
            $ret[]=$this->getById($rec);
        }
        return $ret;
        
    }
    
    public function execute($stmt_name,$params) {
        return $this->_db->execute($this->genStmtName($stmt_name),$params);
    }
    
    public function delete($pks_values) {
        if(!is_array($pks_values)) $pks_values=array($pks_values);
        return $this->execute('delete',$pks_values);
    }
    
    public function insert($columns,$return_created_record=true) {
        if(!is_array($columns))
            throw new Exception('$columns debe ser un array');
        $queryname='insert_'.implode('_',array_keys($columns)).'_'.$this->_schema.'_'.$this->_table;
        if(!$this->isStmtPrepared($queryname)) {
            $query='INSERT INTO "'.$this->_schema.'"."'.$this->_table.'" ("'.implode('","',array_keys($columns)).'") VALUES (';
            $vals=array();
            $i=0;
            foreach($columns as $c) {
                $i++;
                $vals[]='$'.$i;
            }
            $query.=implode(',',$vals).') ';
            $query.=' RETURNING ("'.implode('","',$this->_pks).'")';
            $this->prepareStmt($queryname,$query);
        }
        $rec=$this->execute($queryname,$columns);
        if(!$rec || $rec->numRows()==0)
            throw new Exception('Ocurrio un error al insertar');
        if($return_created_record) {
            $rec=$this->getById($rec->current(),true);
            return $rec;
        }
        return true;
    }


    // getters
    public function getSchema() {
        return $this->_schema;
    }
    public function getTable() {
        return $this->_table;
    }
    public function getDb() {
        return $this->_db;
    }
    public function getPks() {
        return $this->_pks;
    }
    public function getColumns() {
        return $this->_columns;
    }
    public function getRels() {
        return $this->_rels;
    }
    public function getExtendedClass() {
        return $this->_extended_class;
    }
    public function getPreparedStms() {
        return $this->_prepared_stms;
    }    
    
    
}
