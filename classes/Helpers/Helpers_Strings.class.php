<?php
class Helpers_Strings {
    static function quitar_tildes($str) {
        $replaces=array(
            'á'=>'a',
            'é'=>'e',
            'í'=>'i',
            'ó'=>'o',
            'ú'=>'u',
            'Á'=>'A',
            'É'=>'E',
            'Í'=>'I',
            'Ó'=>'O',
            'Ú'=>'U',
            'à'=>'a',
            'è'=>'e',
            'ì'=>'i',
            'ò'=>'o',
            'ù'=>'u',
            'À'=>'A',
            'È'=>'E',
            'Ì'=>'I',
            'Ò'=>'O',
            'Ù'=>'U',
            'ñ'=>'n',
            'Ñ'=>'N',        
        
        );
        foreach($replaces as $from=>$to) {
            $str=str_replace($from,$to,$str);
            $str=str_replace(utf8_decode($from),utf8_decode($to),$str);
        }
        return $str;
        
        
        
    }
    
    
    
}