<?php
class AutoModelRecord {
    private $_modelo;
    private $_data;
    
    function __construct($modelo_instance,$data) {
        $this->_modelo=$modelo_instance;
        $this->_data=$data;
    }
    
    function __get($k) {
        $found=false;
        foreach($this->_modelo->getColumns() as $col) {
            if($col['column']==$k) {
                $found=true;
                break;
            }
        }
        if(!$found)
            throw new Exception('No existe la columna "'.$k.'" en la tabla "'.$this->_modelo->getSchema().'"."'.$this->_modelo->getTable().'"');
        return $this->_data[$k];
    }
    
    function __set($k,$v) {
        $found=false;
        foreach($this->_modelo->getColumns() as $col) {
            if($col['column']==$k) {
                $found=true;
                break;
            }
        }
        if(!$found)
            throw new Exception('No existe la columna "'.$k.'" en la tabla "'.$this->_modelo->getSchema().'"."'.$this->_modelo->getTable().'"');
        $stmt_name='updateColumn'.$k;
        if(!$this->_modelo->isStmtPrepared($stmt_name)) {
            $conditions=array();
            $i=1;
            foreach($this->_modelo->getPks() as $pk) {
                $conditions[]='"'.$pk.'"=$'.$i;
                $i++;
            }
            $query='UPDATE "'.$this->_modelo->getSchema().'"."'.$this->_modelo->getTable().'" SET "'.$col['column'].'"=$'.$i.' WHERE ('.implode(' AND ',$conditions).')';
            $this->_modelo->prepareStmt($stmt_name,$query);
        }
        $params=array();
        foreach($this->_modelo->getPks() as $pk) {
            $params[]=$this->_data[$pk];
        }
        $params[]=$v;
        $this->_modelo->execute($stmt_name,$params);
        return $v;
    }
    
    public function getData() {
        return $this->_data;
    }
    public function getArray() {
        return $this->_data;
    }
    public function toArray() {
        return $this->_data;
    }
    
    
    function relGet($table,$schema=null,$extended_class=-1,$source_columns=null) {
        return $this->fkGet($table,$schema,$extended_class,$source_columns);
    }
    
    function fkGet($table,$schema=null,$extended_class=-1,$source_columns=null) {
        if(is_null($schema)) {
            $schema=$this->_modelo->getSchema();
        }
        $rels=array();
        foreach($this->_modelo->getRels() as $rel) {
            if($rel['schema']!=$schema) continue;
            if($rel['table']!=$table) continue;
            if(!is_null($source_columns))
                throw new Exception(__METHOD__.' - Not implemented yet!');
            $rels[]=$rel;
        }
        if(count($rels)>1)
            throw new Exception(__METHOD__.' - Se encontró mas de una relacion que cumple con las caracteristicas de los parametros enviados a fkGetAll. '
            .'Se debe enviar mejores parametros que permitan filtrar adecuadamente');
        $target_rel=$rels[0];
        
        $params=array();
        foreach($target_rel['rel'] as $pair) {
            $params[]=$this->_data[$pair[1]];
        }
        $ret=array();
        
        $next_model=AutoModel::getInstance($target_rel['schema'],$target_rel['table'],$this->_modelo->getDb(),$extended_class);
        $ret=array();
        foreach($this->_modelo->execute('relget'.$target_rel['hash'],$params) as $rec) {
            $ret[]=$next_model->getById($rec);
        }
        return $ret;
    }
    
    function getPk() {
        $ret=array();
        $i=0;
        foreach($this->_modelo->getPks() as $pk) {
            $ret[$pk]=$this->_data[$pk];
            $ret[$i]=$this->_data[$pk];
            $i++;
        }
        return $ret;
    }
    
    function delete() {
        $pks=array();
        foreach($this->_modelo->getPks() as $pk) {
            $params[]=$this->_data[$pk];
        }
        $this->_modelo->delete($params);
       
    }
    
    function __call($fname,$arguments) {
        throw new Exception($fname.' no definido!');
    }
}