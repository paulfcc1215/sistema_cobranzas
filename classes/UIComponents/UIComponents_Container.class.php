<?php
class UIComponents_Container extends UIComponents_Abstract {
    private $_tree;
    private $_drawCommonJs;
    protected $_js_common;
    protected $_js_custom;
    

    public function __construct() {
        parent::__construct();
        $this->_tree=array();
        $this->_js_common=array();
        $this->_js_custom=array();
        $this->_drawCommonJs=true;
        $this->_common_name='Contenedor Generico';
        $this->_common_description='Clase contenedor generico que permite agregar otros componentes y maneja el dibujado en cascada (uso interno)';
        $this->_component_type='Interno';
    }
    
    public function cloneComponentByIndex($index,$new_id=null) {
        if(is_null($new_id)) $new_id=uniqid();
        $this->_tree[$new_id]=clone $this->getComponentByIndex($index);
        return $this->_tree[$new_id];
    }
    
    public function drawCommonJs($value) {
        $this->_drawCommonJs=$value;
        return $this;
    }
    
    public function getTree() {
        return $this->_tree;
    }
    
    public function getComponentByIndex($index) {
        $keys=array_keys($this->_tree);
        if($index > count($index))
            throw new Exception('El indice indicado no existe');
        return $this->_tree[$keys[$index-1]];
    }
    
    public function getComponentById($id) {
        if(!array_key_exists($id,$this->_tree))
            throw new Exception('El componente indicado no existe');
        return $this->_tree[$id];
    }
    
    public function draw() {
        foreach($this->_tree as $id=>$text_or_component) {
            if(is_object($text_or_component)) {
                if(!is_a($text_or_component,'UIComponents_Abstract'))
                    throw new Exception('UIComponents_Container solo puede tener objetos que implementen UIComponents_Abstract');
                $this->_buffer[]=$text_or_component->draw();
            }else{
                $this->_buffer[]=$text_or_component;
            }
        }
        return parent::draw();
    }
    
    public function add($text_or_component,$id=null) {
        if(is_null($id)) $id=uniqid();
        if(array_key_exists($id,$this->_tree))
            throw new Exception('El componente con ID '.$id.' ya existe en el arbol');
        $this->_tree[$id]=$text_or_component;
        return $text_or_component;
    }
    
    public function __clone() {
        foreach($this->_tree as &$c) {
            if(is_object($c)) $c=clone $c;
            unset($c);
        }
    }
    
}