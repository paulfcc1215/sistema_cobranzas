<?php
class Catalogo implements Iterator {
    private $_M=array();
    private $_data=array();
    private $_catalogo_row;
    private $_pk;
    private $_db;
    private $_tag;
    private $_internal_ptr;
    private $_internal_ptr_keys;

    function __construct($id,$db,$version=0x1457) {
        if(!is_a($db,'DB_Interface'))
            throw new Exception('$db debe ser una instancia de DB_Interface');
        $this->_db=$db;
        $this->_M['catalogo']=AutoModel::getInstance('catalogos','catalogo',$db);
        $this->_M['catalogo_version']=AutoModel::getInstance('catalogos','catalogo_version',$db);
        $this->_M['catalogo_data']=AutoModel::getInstance('catalogos','catalogo_data',$db);
        $this->_catalogo_row=$this->_M['catalogo']->getById($id);
        
        if(!$this->_catalogo_row)
            throw new Exception(__METHOD__.' - Catálogo con id "'.$id.'" no existe');
        
        $this->_pk=$this->_catalogo_row->campo_clave;
        $this->_tag=$this->_catalogo_row->tag;
        $this->_versiones_row=$this->_catalogo_row->fkGet('catalogo_version');
        
        if($version==0x1457) {
            $q0=$db->query('
            SELECT 
                id_catalogo_version,version,fecha_creacion,fecha_modificacion
            FROM
                catalogos.catalogo_version
            WHERE
                id_catalogo=\''.$db->escape($id).'\'
            ORDER BY
                version DESC
            LIMIT 1
            ');
        }else{
            $q0=$db->query('
            SELECT 
                id_catalogo_version,version,fecha_creacion,fecha_modificacion
            FROM
                catalogos.catalogo_version
            WHERE
                id_catalogo=\''.$db->escape($id).'\'
                AND version=\''.$db->escape($version).'\'
            ');
        }
        if($q0->numRows()==0)
            throw new Exception(__METHOD__.' - No se encontraron versiones disponibles para el catalogo id "'.$id.'"');
        $this->version=$q0->current();

        $this->_cache_path=_VOLATILE_CACHE_PATH.'/'.'cat_'.$this->_catalogo_row->id_catalogo.'_'.$this->version['version'].'_'.crc32($this->version['version'].$this->version['fecha_modificacion']);

        if(!$this->loadFromCache()) {
            $rows=$this->_M['catalogo_data']->getByAndCond(array('id_catalogo_version'=>$this->version['id_catalogo_version']),'row ASC,campo ASC');
            foreach($rows as $r) {
                $this->_data[$r->row][$r->campo]=$r->valor;
            }
            $this->dumpToCache();
        }
        $this->_internal_ptr_keys=array_keys($this->_data);
    }

    private function loadFromCache() {
        if(!_USE_VOLATILE_CACHE) return false;

        if(is_readable($this->_cache_path)) {
            $this->_data=file_get_contents($this->_cache_path);
            $this->_data=unserialize($this->_data);
            if($this->_data!==false) return true;
        }
        return false;
    }

    private function dumpToCache() {
        if(!_USE_VOLATILE_CACHE) return;
        file_put_contents($this->_cache_path,serialize($this->_data));
    }

    function getStructure() {
        return array_keys($this->_data[array_keys($this->_data)[0]]);
    }

    function getByIndex($index) {
        return $this->_data[array_keys($this->_data)[$index]];
    }

    function getByPk($pk) {
        foreach($this->_data as $k=>$v) {
            if($v[$this->_pk]==$pk) return $v;
        }
        return null;
    }

    function get($pk) {
        return $this->getByPk($pk);
    }

    function getByRow($row) {
        return $this->_data[$row];
    }

    function getAll() {
        return $this->_data;
    }

    function getIdCatalogo() {
        return $this->_catalogo_row->id_catalogo;
    }

    function getPk() {
        return $this->_pk;
    }
    
    public function getTag() {
        return $this->_tag;
    }

    function insert($pk,$data) {
        if(array_key_exists($this->_pk,$data))
            throw new Exception(__METHOD__.' - En $data no puede existir la columna primary key');

        $this->_db->startTransaction();
        $max=$this->_db->query('SELECT MAX(row) m FROM "catalogos"."catalogo_data" WHERE id_catalogo_version=\''.$this->version['id_catalogo_version'].'\'')->current()['m'];
        $next_row=$max+1;
        $this->_M['catalogo_data']->insert(
            array(
                'id_catalogo_version'=>$this->version['id_catalogo_version'],
                'campo'=>$this->_pk,
                'valor'=>$pk,
                'row'=>$next_row,
                'status'=>'1'
            )
        );
        foreach($data as $k=>$v) {
            $this->_M['catalogo_data']->insert(
                array(
                    'id_catalogo_version'=>$this->version['id_catalogo_version'],
                    'campo'=>$k,
                    'valor'=>$v,
                    'row'=>$next_row,
                    'status'=>'1'
                )
            );
        }
        $this->_data[$next_row]=array_merge(array($this->_pk=>$pk),$data);
        $this->dumpToCache();
        $this->_db->commit();
        return true;
    }
    
    function __get($k) {
        return $this->_catalogo_row->$k;
    }
    
    static public function getVersionList($id_catalogo,$db) {
        if(!is_a($db,'DB_Interface'))
            throw new Exception('$db debe ser una instancia de DB_Interface');
        $q0=$db->query('SELECT * FROM catalogos.catalogo_version WHERE id_catalogo=\''.$db->escape($id_catalogo).'\' ORDER BY version DESC');
        $ret=array();
        foreach($q0 as $v) {
            $ret[$v['id_catalogo_version']]=$v;
        }
        return $ret;
    }
    
    static public function getList($db) {
        if(!is_a($db,'DB_Interface'))
            throw new Exception('$db debe ser una instancia de DB_Interface');
        $q0=$db->query('SELECT cg.nombre AS nombre_grupo,c.* FROM catalogos.catalogo c JOIN catalogos.catalogo_grupo cg USING (id_catalogo_grupo) ORDER BY cg.nombre,c.nombre');
        $ret=array();
        foreach($q0 as $c) {
            $aux=$c;
            $aux['versiones']=array();
            $q1=$db->query('SELECT * FROM catalogos.catalogo_version WHERE id_catalogo='.$aux['id_catalogo'].' ORDER BY version DESC');
            foreach($q1 as $v) {
                $aux['versiones'][]=$v;
            }
            unset($aux['nombre_grupo']);
            $ret[$c['nombre_grupo']][]=$aux;
        }
        

        return $ret;
    }
    
    // Iterator implementation
    public function current () {
        return $this->_data[$this->_internal_ptr_keys[$this->_internal_ptr]];
    }
    
    public function key() {
        return $this->_internal_ptr_keys[$this->_internal_ptr];
    }
    
    public function next() {
        $this->_internal_ptr++;
    }
    
    public function rewind() {
        $this->_internal_ptr=0;
        
    }
    
    public function valid() {
        return($this->_internal_ptr<count($this->_data));
    }
    
}