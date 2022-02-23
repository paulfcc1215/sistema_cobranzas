<?php
class Helpers_Arrays {
    
    static function groupBy_callback($array,$callback_function) {
        $ret=array();
        foreach($array as $k=>$v) {
            $key=$callback_function($v);
            $ret[$key][]=$v;
        }
        return $ret;
    }
    
    private static function _arr_collapse($arr,$result=array(),$prepend='') {
        foreach($arr as $k=>$v) {
            if(!is_array($v)) {
                $result[]=$prepend.'['.$k.']='.$v;
            }else{
                $result=array_merge($result,Helpers_Arrays::_arr_collapse($v,$result,$prepend.'['.$k.']'));
            }
        }
        return $result;
    }
    static function arr_collapse($arr) {
        $prepend='';
        return Helpers_Arrays::_arr_collapse($arr,array(),$prepend);
    }
    
}