<?php
class ModeloCustom_CamposMetadata {
    private $id_producto_campo;
    private $_modelo;
    
    function __construct($id_producto_campo) {
        $this->id_producto_campo=$id_producto_campo;
        $this->_modelo=AutoModel::getInstance('productos','productos_campos_metadata',Db::getInstance());
    }
    
    function __get($k) {
        $rec=$this->_modelo->getByAndCond(array('id_producto_campo'=>$this->id_producto_campo,'value'=>$k));
        if(empty($rec)) return false;
        return true;
    }
    
    function __set($k,$v) {
        $k=uniqid();
        $rec=$this->_modelo->insert(array(
            'id_producto_campo'=>$this->id_producto_campo,
            'value'=>$k
        ));
    }
    
    function getAll() {
        $recs=$this->_modelo->getByAndCond(array('id_producto_campo'=>$this->id_producto_campo));
        $ret=array();
        foreach($recs as $m) {
            $ret[]=$m->value;
        }
        return $ret;
    }
}