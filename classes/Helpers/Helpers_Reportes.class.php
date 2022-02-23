<?php
class Helpers_Reportes {
    private function __construct() {
        // no instanciable
    }
    
    static function extrae_idsGestion($gestiones_agrupadas) {
        $ret=array();
        foreach($gestiones_agrupadas as $k=>$v) {
            foreach($v as $vv) {
                if(!array_key_exists('id_gestion',$vv))
                    throw new Exception(__METHOD__.' - para esta funcion se debe enviar agrupado');
                    
                $ret[$k][]=$vv['id_gestion'];
            }
        }
        return $ret;
    }
    
    static function calcula_SumaTotalDuracion($gestiones) {
        $ret=array();
        foreach($gestiones as $k=>$v) {
            $acum=0;
            foreach($v as $vv) {
                if(!array_key_exists('id_gestion',$vv))
                    throw new Exception(__METHOD__.' - para esta funcion se debe enviar agrupado');
                $acum+=$vv['_duracion'];
            }
            $ret[$k]=array(
                '_duracion'=>$acum,
                '_duracion_his'=>Helpers::seconds_to_time($acum)
            );
        }
        return $ret;
    }
    
    static function loadNoGestionados($ids_subidas) {
        $catalogos_cache=array();
        $db=DB::getInstance();
        if(!is_array($ids_subidas)) $ids_subidas=array($ids_subidas);
        foreach($ids_subidas as $a) {
            if(!preg_match('#^\d+$#',$a))
                throw new Exception(__METHOD__.' - Los ids de subida deben ser numéricos');
        }

        $query='
        SELECT id_registro FROM "'._DB_SCHEMA_DEFAULT.'".registros r
        WHERE
        r.id_registro NOT IN (
            SELECT id_registro FROM "'._DB_SCHEMA_DEFAULT.'".gestiones g WHERE g.status=\'1\'
        )
        AND
        r.status=\'1\'
        AND id_subida IN ('.implode(',',$ids_subidas).')';
        
        
        $q0=$db->query($query);
        $registros=array();
        $cache_subidas=array();
        
        //$a=new SysTools_DetectAbort();
        while($qa0=$db->fetchOne($q0)) {
            //$a->heartbeat();
            $aux=new Modelo_Registros($qa0['id_registro']);
            $aux2=array_merge($aux->getBasicData(),$aux->getAllData());
            if(!array_key_exists($aux2['id_subida'],$cache_subidas)) {
                /*
                $aux3=new Modelo_Subidas($aux2['id_subida']);
                $cache_subidas[$aux2['id_subida']]=array(
                    'descripcion'=>$aux3->descripcion,
                    'fecha_subida'=>$aux3->fecha_subida,
                    'mes_subida'=>date('m',strtotime($aux3->fecha_subida))
                );
                unset($aux3);
                */
                $cache_subidas[$aux2['id_subida']]=new Modelo_Subidas($aux2['id_subida']);
                
            }
            $aux2['_subida']=$cache_subidas[$aux2['id_subida']];
            $registros[]=$aux2;
        }
        return $registros;
        
    }
    
    static function loadGestiones($ids_subidas,$desde,$hasta) {
        $catalogos_cache=array();
        $subidas_cache=array();
        $db=DB::getInstance();
        if(!is_array($ids_subidas)) $ids_subidas=array($ids_subidas);
        foreach($ids_subidas as $a) {
            if(!preg_match('#^\d+$#',$a))
                throw new Exception(__METHOD__.' - Los ids de subida deben ser numéricos');
        }

        if(!preg_match('#^\d{4}-\d{2}-\d{2}$#',$desde))
            throw new Exception(__METHOD__.' - La fecha inicial no cumple con el formato YYYY-MM-DD');
        if(!preg_match('#^\d{4}-\d{2}-\d{2}$#',$hasta))
            throw new Exception(__METHOD__.' - La fecha inicial no cumple con el formato YYYY-MM-DD');
        $query='
        SELECT id_gestion
        FROM 
        "'._DB_SCHEMA_DEFAULT.'".gestiones g
        JOIN "'._DB_SCHEMA_DEFAULT.'".registros r
        ON
        (r.id_registro=g.id_registro)
        JOIN
        "'._DB_SCHEMA_DEFAULT.'".tipificaciones t
        ON (g.id_tipificacion=t.id_tipificacion)
        LEFT JOIN
        "'._DB_SCHEMA_DEFAULT.'".sub_tipificaciones st
        ON (g.id_sub_tipificacion=st.id_sub_tipificacion)
        WHERE
        g.status=\'1\'
        AND r.status=\'1\'
        AND DATE(g.fecha_inicio)>=\''.$db->escape($desde).'\'
        AND DATE(g.fecha_inicio)<=\''.$db->escape($hasta).'\'
        ';
        
        //if(!is_null($ids_subidas) && is_array($ids_subidas) && !empty($ids_subidas)) {
            $query.='AND id_subida::text::int IN ('.implode(',',$ids_subidas).')';
       //}
        //$query.=' LIMIT 100';
        /*
        $query.='AND r.identificacion=\'00000605074582\'';
        if($_SERVER['REMOTE_ADDR']!='192.168.29.187')
            die('trabajando en reportes');
        */
       //echo $query;
       //die();
        $q0=$db->query($query);
        $gestiones=array();

        
        while($qa0=$db->fetchOne($q0)) {
            if(SysTools_DetectAbort::usingHeartBeat())
                SysTools_DetectAbort::heartbeat();
            $aux=new Modelo_Gestiones($qa0['id_gestion']);
            $gestion=$aux->getBasicData();
            
            $gestion['_duracion']=strtotime($gestion['fecha_fin'])-strtotime($gestion['fecha_inicio']);
            $gestion['_duracion_his']=Helpers::seconds_to_time($gestion['_duracion']);
            $gestion['_gestion']=$aux->getExtraData();
            $aux=new Modelo_Registros($gestion['id_registro']);
            $gestion['_registro']=$aux->getAllData();
            
            if(!array_key_exists($aux->id_subida,$subidas_cache)) {
                $subidas_cache[$aux->id_subida]=new Modelo_Subidas($aux->id_subida);
            }
            $gestion['_subida']=$subidas_cache[$aux->id_subida];
            
            
            if(array_key_exists('__catalogos',$gestion['_gestion'])) {
                foreach($gestion['_gestion']['__catalogos'] as $nombre_catalogo=>&$vv) {
                    if(!array_key_exists($vv.'-'.$nombre_catalogo,$catalogos_cache)) {
                        $catalogos_cache[$vv.'-'.$nombre_catalogo]=new Catalogo($vv);
                    }
                    $vv=$catalogos_cache[$vv.'-'.$nombre_catalogo];
                }
                unset($vv);
            }
            $gestiones[]=$gestion;
        }
        
        return $gestiones;
    }
    
    static function agrupa_IdentificacionDuracion($gestiones) {
        $gestiones=Helpers_Arrays::groupBy_callback($gestiones,function($v) {
            return $v['_registro']['identificacion'];
        });
        foreach($gestiones as $k=>&$v) {
            usort($v,function($a,$b) {
                if($a['_duracion']>$b['_duracion']) return -1;
                if($a['_duracion']<$b['_duracion']) return 1;
                return 0;
            });
            unset($v);

        }
        unset($v);
        return $gestiones;        
        
    }
    
    static function agrupa_IdentificacionFecha($gestiones) {
        $gestiones=Helpers_Arrays::groupBy_callback($gestiones,function($v) {
            return $v['_registro']['identificacion'];
        });
        foreach($gestiones as $k=>&$v) {
            usort($v,function($a,$b) {
                if(strtotime($a['fecha_inicio'])>strtotime($b['fecha_inicio'])) return -1;
                if(strtotime($a['fecha_inicio'])<strtotime($b['fecha_inicio'])) return 1;
                return 0;
            });
            unset($v);
        }
        unset($v);
        
        return $gestiones;        
        
    }
    
    static function agrupa_IdentificacionMejorGestion($gestiones) {
        $gestiones=Helpers_Arrays::groupBy_callback($gestiones,function($v) {
            return $v['_registro']['identificacion'];
        });
        foreach($gestiones as $k=>&$v) {
            usort($v,function($a,$b) {
                if($a['_tipif']['peso_final']>$b['_tipif']['peso_final']) return -1;
                if($a['_tipif']['peso_final']<$b['_tipif']['peso_final']) return 1;
                return 0;
            });
            unset($v);
        }
        unset($v);
        
        return $gestiones;        
    }
    
    
    static function filtra_Custom($gestiones,$callback,$byReference=false) {
        $aux=array();
        if($byReference) {
            foreach($gestiones as $g) {
                if($callback($g)) {
                    $aux[]=&$g;
                }
            }            
        }else{
            foreach($gestiones as $g) {
                if($callback($g)) {
                    $aux[]=$g;
                }
            }
        }
        return $aux;        
    }

    static function filtra_soloEfectivas($gestiones) {
        $aux=array();
        foreach($gestiones as $g) {
            if($g['_tipif']['tipificacion']['es_efectiva']=='1') {
                $aux[]=$g;
            }
        }
        return $aux;        
    }
    
    static function filtra_soloTipificacionTag($gestiones,$tipificacion_tag) {
        $aux=array();
        foreach($gestiones as $g) {
            if($g['_tipif']['tipificacion']['tag']==$tipificacion_tag) {
                $aux[]=$g;
            }
        }
        return $aux;
    }
    
    
    static function filtra_soloTipificacionesId($gestiones,$tipificaciones) {
        $aux=array();
        foreach($gestiones as $g) {
            if(in_array($g['_tipif']['tipificacion']['id_tipificacion'],$tipificaciones)) {
                $aux[]=$g;
            }
        }
        return $aux;
    }
    
    static function auto_validaciones($target,$validation) {
        try {
            if(is_string($validation)) {
                if(substr($validation,0,7)=='@regexp') {
                    $regexp=substr($validation,7);
                    $validation='@regexp';
                }
                switch($validation) {
                    case '@empty';
                        if($target!='')
                            throw new Exception();
                    break;
                    case '@notempty';
                        if(trim($target)=='')
                            throw new Exception();
                    break;
                    case '@numeric_or_empty';
                        if($target!='' && !preg_match('#^\d+$#',$target))
                            throw new Exception();
                    break;
                    case '@numeric';
                        if(!preg_match('#^\d+$#',$target))
                            throw new Exception();
                    break;
                    case '@nonumbers';
                        if(!preg_match('#^\d$#',$target))
                            throw new Exception();
                    break;
                    case '@regexp';
                        if(!preg_match($regexp,$target))
                            throw new Exception();
                    break;
                    default:
                        echo 'Comando no entendido "'.$validation.'"';
                        die();
                    break;
                }
            }elseif(is_array($validation)) {
                if(!in_array($target,$validation))
                            throw new Exception();
            }
            return true;
            
        }catch(Exception $e) {
            return false;
        }
    }
    
    

    
    
    
    
}