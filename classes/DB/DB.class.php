<?php
class DB {
    static $db_instance=array();
    
    private function __construct() {
        throw new Exception('Clase no instanciable');
    }
    
    static function connect($db_type='pgsql',$params,$name='0') {
        switch($db_type) {
            case 'pgsql':
                if(isset($params['pass'])) {
                    $params['password']=$params['pass'];
                    unset($params['pass']);
                }
                if($params['port']!='') {
                    $aux=new DB_PgSQL($name,$params['host'],$params['user'],$params['password'],$params['dbname'],$params['port']);
                }else{
                    $aux=new DB_PgSQL($name,$params['host'],$params['user'],$params['password'],$params['dbname']);
                }
                
            break;
        }
        DB::$db_instance[$name]=$aux;
        return DB::$db_instance;
    }
    
    static function getInstance($name=null) {
        if(!is_null($name)) {
            return DB::$db_instance[$name];
        }else{
            return DB::$db_instance['0'];
        }
    }
}