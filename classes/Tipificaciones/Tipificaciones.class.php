<?php
class Tipificaciones {

    private $nombre_catalogo;
    private $fecha_creacion;
    private $id_catalogo;
    private $_tipificaciones;

    private function __construct($id) {
        $db=DB::getInstance();
        $query='SELECT * FROM "'._DB_SCHEMA_DEFAULT.'"."catalogo_tipificaciones" WHERE id_cat_tipificaciones=\''.$db->escape($id).'\'';
        $q0=$db->query($query);
        $qa0=$db->fetchOne($q0);
        $this->nombre_catalogo=$qa0['nombre_catalogo'];
        $this->fecha_creacion=$qa0['fecha_creacion'];
        $this->id_catalogo=$id;

        $query='SELECT * FROM "'._DB_SCHEMA_DEFAULT.'".tipificaciones WHERE id_cat_tipificaciones='.$qa0['id_cat_tipificaciones'].' ORDER BY peso DESC';
        $q0=$db->query($query);
        while($qa0=$db->fetchOne($q0,false)) {
            $id=$qa0['id_tipificacion'];
            //unset($qa0['id_tipificacion']);

            $qa0['_st']=array();

            $query='SELECT * FROM "'._DB_SCHEMA_DEFAULT.'".sub_tipificaciones WHERE id_tipificacion=\''.$db->escape($id).'\' ORDER BY peso DESC';
            $q1=$db->query($query);
            while($qa1=$db->fetchOne($q1,false)) {
                $sid=$qa1['id_sub_tipificacion'];
                //unset($qa1['id_sub_tipificacion']);
                //unset($qa1['id_tipificacion']);
                $qa0['_st'][$sid]=$qa1;
            }
            $db->free($q1);
            $this->_tipificaciones[$id]=$qa0;
        }
    }

    function tieneSubTipificaciones($id_tipificacion) {
        return (count($this->_tipificaciones[$id_tipificacion]['_st'])>0);
    }

    function getTipificacion($id_tipificacion) {
        return $this->_tipificaciones[$id_tipificacion];
    }

    function getSubTipificacion($id_sub_tipificacion) {
        foreach($this->_tipificaciones as $t) {
            if(array_key_exists($id_sub_tipificacion,$t['_st']))
                return $t['_st'][$id_sub_tipificacion];
        }
        return false;
    }

    function getSubTipificacion2($id_tipificacion,$id_sub_tipificacion) {
        if(!array_key_exists($id_tipificacion,$this->_tipificaciones))
            return false;
        if(!array_key_exists($id_sub_tipificacion,$this->_tipificaciones[$id_tipificacion]['_st']))
            return false;
        return $this->_tipificaciones[$id_tipificacion]['_st'][$id_sub_tipificacion];
    }

    function getAll() {
        return $this->_tipificaciones;
    }
    function getNombreCatalogo() {
        return $this->nombre_catalogo;
    }
    function getFechaCreacion() {
        return $this->fecha_creacion;
    }
    function getIdCatalogo() {
        return $this->id_catalogo;
    }

    static function getByInstrumentoTipo($id_instrumento_tipo) {
        $db=DB::getInstance();
        $query='SELECT * FROM "'._DB_SCHEMA_DEFAULT.'"."cat_instrumentos_tipificaciones" WHERE id_instrumento_tipo=\''.$db->escape($id_instrumento_tipo).'\'';
        $q0=$db->query($query);
        if($db->numRows($q0)==0)
            throw new Exception('El instrumento con id "'.$id_instrumento_tipo.'" no tiene catalogo de tipificaciones asociado (Utilizar las tablas "catalogo_tipificaciones" y "cat_instrumentos_tipificaciones")');
        $qa0=$db->fetchOne($q0);
        return new Tipificaciones($qa0['id_cat_tipificaciones']);
    }

    static function existeIdTipificacion($id_tipificacion) {
        $db=DB::getInstance();
        $q0=$db->query('SELECT id_tipificacion FROM "'._DB_SCHEMA_DEFAULT.'".tipificaciones WHERE id_tipificacion=\''.$db->escape($id_tipificacion).'\'');
        $ret=$db->numRows($q0);
        $db->free($q0);
        return $ret==1;
    }

    static function existeIdSubTipificacion($id_sub_tipificacion) {
        $db=DB::getInstance();
        $q0=$db->query('SELECT id_sub_tipificacion FROM "'._DB_SCHEMA_DEFAULT.'".sub_tipificaciones WHERE id_sub_tipificacion=\''.$db->escape($id_sub_tipificacion).'\'');
        $ret=$db->numRows($q0);
        $db->free($q0);
        return $ret==1;
    }

    static function getTipificacionSubtipificacion($id_tipificacion,$id_sub_tipificacion=null) {
        GLOBAL $_CACHE_TIPIF;
        $db=DB::getInstance();
        if(!array_key_exists('t',$_CACHE_TIPIF)) $_CACHE_TIPIF['t']=array();
        if(!array_key_exists('s',$_CACHE_TIPIF)) $_CACHE_TIPIF['s']=array();
        $ret=array(
            'tipificacion'=>null,
            'sub_tipificacion'=>null,
        );
        $acum=0;
        if(array_key_exists($id_tipificacion,$_CACHE_TIPIF['t'])) {
            $ret['tipificacion']=$_CACHE_TIPIF['t'][$id_tipificacion];
        }else{
            $q0=$db->query('SELECT * FROM "'._DB_SCHEMA_DEFAULT.'".tipificaciones WHERE id_tipificacion=\''.$db->escape($id_tipificacion).'\'');
            $ret['tipificacion']=$db->fetchOne($q0);
            $_CACHE_TIPIF['t'][$id_tipificacion]=$ret['tipificacion'];
        }
        $acum+=$ret['tipificacion']['peso'];

        if(!is_null($id_sub_tipificacion)) {
            if(array_key_exists($id_sub_tipificacion,$_CACHE_TIPIF['s'])) {
                $ret['sub_tipificacion']=$_CACHE_TIPIF['s'][$id_sub_tipificacion];
            }else{
                $q0=$db->query('SELECT * FROM "'._DB_SCHEMA_DEFAULT.'".sub_tipificaciones WHERE id_sub_tipificacion=\''.$db->escape($id_sub_tipificacion).'\'');
                $ret['sub_tipificacion']=$db->fetchOne($q0);
                $_CACHE_TIPIF['s'][$id_sub_tipificacion]=$ret['sub_tipificacion'];
            }
            $acum+=$ret['sub_tipificacion']['peso'];
        }
        $ret['peso_final']=$acum;
        return $ret;
    }
    
    static function getTipificacionByIdSubTipificacion($id_sub_tipificacion) {
        $db=DB::getInstance();
        $q0=$db->query('SELECT * FROM "'._DB_SCHEMA_DEFAULT.'".sub_tipificaciones WHERE id_sub_tipificacion=\''.$db->escape($id_sub_tipificacion).'\'');
        if($db->numRows($q0)==0) return false;
        $st=$db->fetchOne($q0);
        $query='SELECT * FROM "'._DB_SCHEMA_DEFAULT.'".tipificaciones WHERE id_tipificacion=\''.$st['id_tipificacion'].'\'';
        $q0=$db->query($query);
        $tip=$db->fetchOne($q0);
        $tip['st']=$st;
        return $tip;        
        
    }
	
	static function getTipificacionesByProceso($id_proceso){
		$ret = array();
		$db = DB::getInstance();
		$q='SELECT * 
            FROM gestiones.tipificacion t
            WHERE t.id_cat_tipificacion IN(
			    SELECT tc.id_cat_tipificacion 
                FROM campanas.proceso p
			        JOIN campanas.campana c USING (id_campana)
			        JOIN campanas.tipificacion_cat_campana tc on (c.id_campana=tc.id_campana)
                WHERE id_proceso='.$db->escape($id_proceso).')';
        $q0=$db->query($q);
		while ($qa0 = $db->fetchOne($q0)){
            $ret[$qa0['id_tipificacion']]=$qa0;
            //get tipificacion_metadata
            $metadata = array();
            $q1 = $db->query('SELECT * FROM gestiones.tipificacion_metadata WHERE id_tipificacion='.$qa0['id_tipificacion']);
            while($qa1 = $db->fetchOne($q1)){
                $metadata[$qa1['id_tipificacion_metadata']]=$qa1;
            }
            $ret[$qa0['id_tipificacion']]['_metadata']=$metadata;
        }
		return $ret;
	}

}