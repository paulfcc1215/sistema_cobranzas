<?php

class CargaModelo_Item_Cuota extends CargaModelo_Item_Abstract {

    // private $numero_cuota;
    // private $fecha_vencimiento;
    // private $capital_cuota;

    function __construct() {
        $this->_data=array(
            'numero_cuota'=>null,
            'fecha_vencimiento'=>null,
            'capital_cuota'=>null
        );
    }
    
    function set($k,$v) {
        if (!in_array($k,array_keys($this->_data))) throw new exception('No se conoce atributo "'.$k.'" en clase: "'.get_called_class().'"');
        if ($k=='numero_cuota'){
            if (!is_numeric($v)) throw new exception('El atributo "'.$k.'" debe ser numérico, en clase: "'.get_called_class().'"');
        }
        if ($k=='capital_cuota'){
            if (!is_numeric($v)) throw new exception('El atributo "'.$k.'" debe ser numérico, en clase: "'.get_called_class().'"');
        }
        $this->_data[$k]=$v;
    }

    function __set($k,$v) {
        throw new exception('Método mágico __set($k,$v) no implementado, utilizar '.get_called_class().'::set($atributo,$valor)');
    }
    
    function validate() {
        die('validate');
    }
}