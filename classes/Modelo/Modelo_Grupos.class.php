<?php

class Modelo_Grupos extends Modelo {

	function __construct($id) {
		parent::__construct('auth','auth_grupos','id_grupo');
        $query='SELECT * FROM "auth"."auth_grupos" WHERE id_grupo=\''.$this->db->escape($id).'\'';
        $q0=$this->db->query($query);
        if($this->db->numRows($q0)==0) throw new Exception(__METHOD__.'::__construct - el id '.$id.' no existe');
        $qa0=$this->db->fetchOne($q0,true);
        foreach($qa0 as $k=>$v) {
            $this->_data[$k]=$v;
        }
    }

    static function getAll($include_disabled=false,$order_by='id_grupo ASC') {
        $db=DB::getInstance();
        $query='SELECT id_grupo FROM "auth"."auth_grupos"';
        if(!$include_disabled) $query.=' WHERE "status"=\'1\'';
        $query.='ORDER BY '.$order_by;
        $q0=$db->query($query);
        if($db->numRows($q0)==0) return array();
        $ret=array();
        while($qa0=$db->fetchOne($q0)) {
            $ret[$qa0['id_grupo']]=new Modelo_Grupos($qa0['id_grupo']);
        }
        return $ret;
    }

    static function getGruposPorUsuario($usr_logname){
        
        $db=DB::getInstance();
        $ret=array();

        $query='SELECT id_grupo FROM "auth"."auth_grupos_usuarios" WHERE usr_logname=\''.$db->escape($usr_logname).'\'';
        $q0=$db->query($query);
        while($qa0=$db->fetchOne($q0)) {
            $ret[$qa0['id_grupo']]= new Modelo_Grupos($qa0['id_grupo']);
        }
        return $ret;
    }

    static function create($descripcion,$fecha_agregado,$estado,$array_privilegios) {
        $db=DB::getInstance();

        $db->startTransaction();
        
        $query='INSERT INTO "auth"."auth_grupos" (descripcion,fecha_agregado,status) VALUES (\''.$db->escape($descripcion).'\',\''.$db->escape($fecha_agregado).'\',\''.$db->escape($estado).'\') RETURNING id_grupo';
        $q0=$db->query($query);
        $qa0=$db->fetchOne($q0);
        if(is_null($qa0)){
            $db->rollback();
            return array();
        }
        $query='INSERT INTO "auth"."auth_privilegios_grupos" (define_privilegio,id_grupo) VALUES ($1,$2)';
        $pre=$db->prepare("sentencia",$query);
        if(!$pre){
            $db->rollback();
            return array();
        }
        foreach ($array_privilegios as $p) {
            $q0=$db->execute("sentencia",array($p,$qa0['id_grupo']));
            if(!$q0){
                $db->rollback();
                return array();
            }
        }
        $db->commit();

        return new Modelo_Grupos($qa0['id_grupo']);
    }

    static function deletePorUsuario($usr_logname){
        $db=DB::getInstance();
        $db->startTransaction();
        $query='DELETE FROM "auth"."auth_grupos_usuarios" WHERE usr_logname=\''.$db->escape($usr_logname).'\'';
        $q0=$db->query($query);
        $db->commit();
        return true;
    }

    static function createGruposUsuario($usr_logname,$array_grupos_usuario) {
        $db=DB::getInstance();
        $db->startTransaction();
        $query='INSERT INTO "auth"."auth_grupos_usuarios" (usr_logname,id_grupo) VALUES ($1,$2)';
        $pre=$db->prepare("sentencia",$query);
        if(!$pre){
            $db->rollback();
            return array();
        }
        foreach ($array_grupos_usuario as $value) {
            $q0=$db->execute("sentencia",array($usr_logname,$value));
            if(!$q0){
                $db->rollback();
                return array();
            }
        }
        $db->commit();

        return true;
    }

    

}