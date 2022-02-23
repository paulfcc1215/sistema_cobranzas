<?php
class Helpers_ClassValidators {
    
    static function enforce_interface($target_interface,$target_class,$skip_methods=array()) {
        GLOBAL $_CACHE_REFLECTION;
        if(is_null($_CACHE_REFLECTION)) $_CACHE_REFLECTION=array();
        if(!is_string($target_interface))
            throw new Exception(__METHOD__.' - target_interface debe ser string');
        
        if(!array_key_exists($target_interface,$_CACHE_REFLECTION)) {
            $rf_interface=new ReflectionClass($target_interface);
            $methods_iface=$rf_interface->getMethods();
            foreach($methods_iface as $m) {
                $_CACHE_REFLECTION[$target_interface][$m->name]=Helpers_Reflection::get_method_params($target_interface,$m->name);
            }
        }
        if(is_object($target_class))
            $target_class=get_class($target_class);
        
        if(!in_array($target_interface,class_implements($target_class)))
            throw new Exception(__METHOD__.' - La clase "'.$target_class.'" debe implementar la interface "'.$target_interface.'"');
                
        $rf_class=new ReflectionClass($target_class);
        $methods_class=$rf_class->getMethods();
        $class_methods=array();
        foreach($methods_class as $m) {
            $method_params=Helpers_Reflection::get_method_params($target_class,$m->name);
            foreach($_CACHE_REFLECTION[$target_interface][$m->name] as $rp) {
                if(!in_array($rp,$method_params))
                    throw new Exception(__METHOD__.' - El método '.$m->name.' de la clase "'.(is_string($target_class)?$target_class:get_class($target_class)).'" requiere exactamente '.count($_CACHE_REFLECTION[$target_interface][$m->name]).' parámetros con los siguientes nombres: "'.implode(', ',$_CACHE_REFLECTION[$target_interface][$m->name]).'"');
            }
        }
    }
    
    static function validate_reporte($target_class) {
        Helpers_ClassValidators::enforce_interface('Reportes_Interface',$target_class);
    }
    
    
    static function validate_instrumento($target_class) {
        

        $methods=Helpers_Reflection::get_methods($target_class);

        // comienzan validaciones
        // debe implementar Instrumentos_Interface
        Helpers_ClassValidators::enforce_interface('Instrumentos_Interface',$target_class);
        
        // debe ser hijo de Instrumentos
        if(!Helpers_Reflection::is_child_of($target_class,'Instrumentos'))
            throw new Exception('La clase "'.get_class($target_class).'" debe ser hijo de la clase "Instrumentos"');
        // campos requeridos debe ser array
        if(!Helpers_Reflection::get_return_type($target_class,'getCamposUtilizablesCarga','array',array(),$campos_requeridos))
            throw new Exception('El método getCamposUtilizablesCarga de la clase "'.get_class($target_class).'" debe retornar un array');

        foreach($campos_requeridos as $v) {
            if(!is_string($v))
                throw new Exception('El método getCamposUtilizablesCarga de la clase "'.get_class($target_class).'" debe retornar un array con las llaves "'.implode(',',$required_keys).'" y todos sus tipos deben ser String');
        }
        
            
        /*
        // evaluamos si este instrumento utiliza la carga generica
        if(!Helpers_Reflection::get_return_type($target_class,'utilizaCargaGenerica','boolean',array(),$carga_generica))
            throw new Exception('El método utilizaCargaGenerica de la clase "'.get_class($target_class).'" debe retornar booleano');
        
        if(!$carga_generica) {
            // si no utiliza la carga generica,
            // debe tener un metodo declarado llamado cargaEspecial
            if(!in_array('cargaEspecial',get_class_methods($target_class)))
                throw new Exception('Si '.get_class($target_class).'->utilizaCargaGenerica()==true, el método cargaEspecial debe existir');
        }
        */
        
        /*
        $instr_data=array(
            'id_instrumento_tipo'=>$target_class_tipo->id_instrumento_tipo,
            'clase'=>get_class($target_class),
            'nombre_instrumento_tipo'=>$nombre_interno,
            'nombre'=>$nombre,
            'campos_requeridos'=>$campos_requeridos
        );
        $_INSTRUMENTOS[$target_class_tipo->id_instrumento_tipo]=$instr_data;
        unset($target_class);
        */
    
    
    
    
    
        
        
        
        
        
        
        
        
        
        
        
        
        
    }
    
    
}