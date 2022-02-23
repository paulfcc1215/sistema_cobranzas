<?php
class Helpers_Reflection {
    static function get_methods($target_class) {
        $rf_class=new ReflectionClass($target_class);
        $methods_class=$rf_class->getMethods();
        $class_methods=array();
        foreach($methods_class as $m) {
            $class_methods[]=$m;
        }
        return $class_methods;
    }

    static function get_method_params($class,$method) {
        $rf=new ReflectionMethod($class,$method);
        $params=array();
        foreach($rf->getParameters() as $p) {
            $params[]=$p->name;
        }
        return $params;
        
    }
    
    static function is_child_of($target_class,$target_parent) {
        $parents=class_parents($target_class);

        if(is_object($target_parent))
            $target_parent=get_class($target_parent);
        if(!in_array($target_parent,$parents))
            return false;
        return true;
    }
    
    static function get_return_type($target_class,$target_method,$expected_return_type,$params=array(),&$method_return=null) {
        /*
        "boolean"
        "integer"
        "double" (aus historischen Gründen wird "double" im Fall eines float zurückgegeben, und nicht einfach float.
        "string"
        "array"
        "object"
        "resource"
        "resource (closed)" von PHP 7.2.0 an
        "NULL"
        "unknown type"
        */
        
        $method_return=call_user_method_array($target_method,$target_class,$params);
        $type=gettype($method_return);
        if($type!=$expected_return_type) return false;
        return true;
        
        
    }
}