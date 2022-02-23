<?php

class Modelo_Privilegios extends Modelo {

	function __construct($id) {
		parent::__construct('auth','auth_privilegios','id_privilegio');
        $query='SELECT * FROM "auth"."auth_privilegios" WHERE id_privilegio='.$this->db->escape($id);
        $q0=$this->db->query($query);
        if($this->db->numRows($q0)==0) throw new Exception(__METHOD__.'::__construct - el id '.$id.' no existe');
        $qa0=$this->db->fetchOne($q0,true);
        foreach($qa0 as $k => $v) {
            $this->_data[$k]=$v;
        }
    }

    static function getAll($include_disabled=false,$order_by='id_privilegio ASC') {
        $db=DB::getInstance();
        $query='SELECT id_privilegio FROM "auth"."auth_privilegios"';
        if(!$include_disabled) $query.=' WHERE "status"=\'1\'';
        $query.='ORDER BY '.$order_by;
        $q0=$db->query($query);
        if($db->numRows($q0)==0) return array();
        $ret=array();
        while($qa0=$db->fetchOne($q0)) {
            $ret[$qa0['id_privilegio']]=new Modelo_Privilegios($qa0['id_privilegio']);
        }
        return $ret;
    }

    static function getPrivilegiosPorGrupoId($id_grupo){
        $db=DB::getInstance();
        $ret=array();
        $query='SELECT id_privilegio 
            FROM "auth"."auth_privilegios_grupos" pg
                JOIN "auth"."auth_privilegios" p USING(define_privilegio)
            WHERE pg.id_grupo=\''.$db->escape($id_grupo).'\'';
        $q0=$db->query($query);
        while($qa0=$db->fetchOne($q0)) {
            $ret[$qa0['id_privilegio']]= new Modelo_Privilegios($qa0['id_privilegio']);
        }
        return $ret;
    }

    static function create($descripcion,$define,$fecha_agregado,$estado){
        $db=DB::getInstance();
        $db->startTransaction();
        $query='INSERT INTO "auth"."auth_privilegios"(
            descripcion,
            define_privilegio,
            fecha_agregado,
            status
        )VALUES(
            \''.$db->escape($descripcion).'\',
            \''.$db->escape($define).'\',
            \''.$db->escape($fecha_agregado).'\',
            \''.$db->escape($estado).'\'
        ) RETURNING id_privilegio';
        $q0=$db->query($query);
        $qa0=$db->fetchOne($q0);
        if (is_null($qa0)){
            $db->rollback();
            return array();
        }
        $db->commit();
        return new Modelo_Privilegios($qa0['id_privilegio']);
    }

    static function updatePrivilegio($id_privilegio,$descripcion,$define_privilegio,$status){
        $db=DB::getInstance();
        $db->startTransaction();
        $query='UPDATE "auth"."auth_privilegios" 
        SET
            descripcion=\''.$db->escape($descripcion).'\',
            define_privilegio=\''.$db->escape($define_privilegio).'\',
            status=\''.$db->escape($status).'\'
        WHERE id_privilegio=\''.$db->escape($id_privilegio).'\'
        RETURNING id_privilegio';
        $q0 = $db->query($query);
        $qa0 = $db->fetchOne($q0);
        $db->commit();
        return new Modelo_Privilegios($qa0['id_privilegio']);
    }

}