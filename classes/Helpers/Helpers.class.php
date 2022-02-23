<?php
class Helpers {
    private function __construct() {
        // no instanciable
    }

    /**
    * Separa una cantidad de segundos en un arreglo correspondiente a
    * Horas, Minutos y Segundos
    * 
    * @param mixed $secs la cantidad de segundos que se desea convertir
    * @param mixed $zero_pad hacer que los números menores que 10 sean devueltos con cero al inicio
    * @return string
    */
    static function secs_to_parts($secs,$zero_pad=false) {
        $op=array(
            'h'=>(60*60),
            'm'=>(60),
            's'=>1
        );
        
        foreach($op as $k=>$o) {
            $aux=$secs;
            $aux=floor($secs/$o);
            $secs=$secs-($aux*$o);
            
            if($zero_pad) {
                $ret[$k]=sprintf("%02d",$aux);
            }else{
                $ret[$k]=$aux;
            }
        }
        $ret['zero']=false;
        if($ret['h']==0 && $ret['m']==0 && $ret['s']==0) $ret['zero']=true;
        return $ret;
        
        
    }
    
    /**
    * Calcula el dígito de verificación a agregar al final de un número
    * para que éste cumpla con el algoritmo Luhn
    * @param mixed $input Numero al que se le desea calcular luhn
    * @return mixed
    */
    static function luhn_calc_chk_digit($input) {
        $input.='x';
        $aux=array();
        for($i=0;$i<strlen($input);$i++) {
            $aux[]=$input[$i];
        }
        $calcs=array(
            'input'=>$aux,
            'doubles'=>$aux,
        );
        
        for($i=count($calcs['input'])-1;$i>=1;$i-=2) {
            $calcs['doubles'][($i-1)]*=2;
            if($calcs['doubles'][($i-1)]>9) $calcs['doubles'][($i-1)]-=9;
        }
        $sum=0;
        foreach($calcs['doubles'] as $d) {
            $sum+=$d;
        }
        $sum*=9;
        $sum=(string)$sum;
        return $sum[(strlen($sum)-1)];
        
        
        
    }

    /**
    * Valida si $input cumple con luhn o no
    * 
    * @param boolean $input
    */
    static function luhn_validate($input) {
        $chksum=Helpers::luhn_calc_chk_digit(substr($input,0,strlen($input)-1));
        if($chksum != substr($input,-1)) return false;
        return true;
    }    
    
    /**
    * Separa una hora tipo hh:mm:ss y la convierte a segundos
    * La hora debe ser formato 24 horas, es decir desde 00:00:00 hasta 23:59:59
    * @see seconds_to_time
    * 
    * @param mixed $time hora tipo hh:mm:ss
    * @returns int cantidad en segundos
    */
    static function time_to_seconds($time) {
        $acum=0;
        $weights=array(
            60*60,
            60,
            1
        );
        $time=explode(':',$time);
        foreach($time as $k=>$v) {
            $acum+=$v*$weights[$k];
        }
        return $acum;
        
    }


    /**
    * Convierte cantidad de segundos en hora tipo hh:mm:ss
    * $seconds no puede ser mayor de 86399
    * 
    * @param mixed $seconds cantidad de segundos
    * @returns string hora formato hh:mm:ss o falso si $seconds es < 0 o > 86399
    */
    static function seconds_to_time($seconds) {
        
        $weights=array(
            60*60,
            60,
            1
        );
        
        $time_parts=array();
        foreach($weights as $w) {
            $aux=$seconds/$w;
            if($aux>=1) {
                $aux=floor($aux);
                $seconds-=$aux*$w;
                $time_parts[]=sprintf("%02d",$aux);
            }else{
                $time_parts[]='00';
            }
        }
        return implode(':',$time_parts);
        
        
            
    }
    
    /**
    * Convierte un arreglo en parametros GET para ser enviados via URL
    * 
    * @param mixed $arr Arreglo a convertir
    * @param mixed $exclude Indices a omitir de $arr
    * @param mixed $include Indices y valores a agregar en la respuesta
    * @param mixed $urlencode Utiliza o no url encode para los valores y los indices
    * @return string
    */
    static function arr_to_url($arr,$exclude=array(),$include=array(),$urlencode=false) {
        $aux=array();
        foreach($arr as $k=>$v) {
            if(in_array($k,$exclude)) continue;
            $aux[$k]=$v;
        }
        foreach($include as $k=>$v) {
            $aux[$k]=$v;
        }
        $aux2=array();
        foreach($aux as $k=>$v) {
            if($urlencode) {
                $aux2[]=urlencode($k).'='.urlencode($v);
            }else{
                $aux2[]=($k).'='.($v);
            }
        }
        return implode('&',$aux2);
    }
    
    /**
    * Convierte una fecha tipo Dia Mes y Año en Año Mes Dia
    * El primer caracter encontrado que no sea número será tomado
    * como el caracter de separación
    * 
    * @param array $source
    * @return string
    */
    static function dmy2ymd($source) {
        $aux = preg_replace('#[\d]#','',$source);
        $sep = $aux[0];
        $source = explode($sep,$source);
        $ret = implode('-',array($source[2],sprintf("%02d",$source[1]),sprintf("%02d",$source[0])));
        return $ret;
        
    }
    
    /**
    * Aplica implode al arreglo $array utilizando el separador $separator
    * solo con aquellos elementos no vacíos de $array
    * 
    * @param mixed $separator
    * @param mixed $array
    * @return string
    */
    
    static function implodeNotEmpty($separator,$array) {
        $aux=array();
        foreach($array as $a) {
            $a=trim($a);
            if($a!='') $aux[]=$a;
        }
        return implode($separator,$aux);
    }
    
    /**
    * Extrae todos los teléfonos detectados en $array
    * El arreglo devuelto tiene la estructura Array(
    *   array(
    *       'tel'=>'numero_telefonico',
    *       'tipo'=>'convencional|celular',
    *       'campo'=>'nombre del campo en $array'
    *   ),
    *   array(
    *       ...
    *   ),
    *   ...
    * )
    * 
    * @param mixed $array Arreglo con el resultado, o falso si no se consiguieron teléfonos
    */
    static function extraerTelefonos($array,$filtrar_duplicados=true) {
        $ret=array(
        );
        
        $used=array();
        foreach($array as $k=>$v) {
            $v=preg_replace('#[^\d]#','',$v);
            if($v=='') continue;
            $v=preg_replace('#^0?593#','',$v);
            $v=preg_replace('#^0#','',$v);
            if(preg_match('#^[2-7]\d{7}$#',$v)) {
                if($filtrar_duplicados && in_array($v,$used)) continue;
                $used[]=$v;
                $a=array(
                    'tel'=>'0'.$v,
                    'tipo'=>'convencional',
                    'campo'=>$k,
                );
                $ret[]=$a;
            }elseif(preg_match('#^9[3-9]\d{7}$#',$v)) {
                if($filtrar_duplicados && in_array($v,$used)) continue;
                $used[]=$v;
                $a=array(
                    'tel'=>'0'.$v,
                    'tipo'=>'celular',
                    'campo'=>$k,
                );
                $ret[]=$a;                
            }
            
        }
        return $ret;
    }
	
	static function telefonoValido($tel,&$tipo=null) {
		$len=strlen($tel);
		if($len!=9 && $len!=10) return false;
		if(preg_match('#^0[2-7]\d{7}$#',$tel)) {
			$tipo='CONVENCIONAL';
			return true;
		}
		if(preg_match('#^09\d{8}$#',$tel)) {
			$tipo='CELULAR';
			return true;
		}
		return false;
	}
    
    static function mk_model(&$target_var,$table_schema,$table_name) {
        $db=Db::getInstance();
        if(empty($target_var)) {
            $columns=$db->getColumns($table_schema,$table_name);
            foreach($columns as $c) {
                $target_var[]=$c['column_name'];
            }
        }
    }

    static function parseTelefonos($telefonos) {

        $ret = array();
        $aux = array();
    
        if (is_array($telefonos)){
            $telefonos = implode(',',$telefonos);
        }
        if (preg_match_all('#[^\d]#',$telefonos,$matches)){
            $matches=array_unique($matches[0]);
            $telefonos = explode($matches[0],str_replace($matches, $matches[0], $telefonos));
            foreach ($telefonos as $t) {
                $t = trim($t);
                if ($t=='') continue;
                if (preg_match('#^\d+$#',$t)) $aux[]=$t;
            }
            $aux=array_unique($aux);
        }else{
            $aux[]=$telefonos;
        }
        
        foreach ($aux as $a) {
            $a = ltrim($a,'0');
            // if (strlen($a)==7) $a='4'.$a;
            if (substr($a,0,3)=='593') $a=substr($a,3);
            if (strlen($a)!=8 && strlen($a)!=9) continue;
            if (!preg_match('#^9[1-9]\d{7}$#',$a) && !preg_match('#^[2-7]\d{7}$#',$a)) continue;
            if (preg_match('#(.)\1{5}#',$a)) continue;
            $ret[] = '0'.$a;
        }
        return $ret;
    }

    static function is_valid_email($str){
		$matches = null;
		return (1 === preg_match('/^[A-z0-9\\._-]+@[A-z0-9][A-z0-9-]*(\\.[A-z0-9_-]+)*\\.([A-z]{2,6})$/', $str, $matches));
	}

}