<?php
class DB_Relations implements DB_iRelations {
    private static $_CACHE=array();
    private $db;
    
    function __construct($db) {
        $this->db=$db;
        
    }
    
    public function getRelations($schema,$table) {
        $cache_key=crc32(
            (is_null($schema)?'':$schema)
            .(is_null($table)?'':$table)
        );
        if(array_key_exists($cache_key,self::$_CACHE)) {
            return self::$_CACHE[$cache_key];
        }else{
            $query='
                SELECT
                pt_schema.nspname AS pt_schema,
                pt.relname AS pt_name,
                ct_schema.nspname AS ct_schema,
                ct.relname AS ct_name,
                pt_columns.attname AS pt_column,
                ct_columns.attname AS ct_column

                FROM
                pg_constraint "constraint"
                JOIN pg_class ct ON (ct.oid="constraint".conrelid)
                JOIN pg_namespace ct_schema ON (ct_schema.oid=ct.relnamespace)
                JOIN pg_class pt ON (pt.oid="constraint".confrelid)
                JOIN pg_namespace pt_schema ON (pt_schema.oid=pt.relnamespace)

                JOIN unnest(conkey) WITH ORDINALITY AS ct_columns_num (num_value,ordinality) ON (TRUE)
                JOIN pg_attribute ct_columns ON (ct_columns.attrelid=ct.oid AND ct_columns.attnum=ct_columns_num.num_value)


                JOIN unnest(confkey) WITH ORDINALITY AS pt_columns_num (num_value,ordinality) ON (pt_columns_num.ordinality=ct_columns_num.ordinality)
                JOIN pg_attribute pt_columns ON (pt_columns.attrelid=pt.oid AND pt_columns.attnum=pt_columns_num.num_value)


                WHERE
                contype=\'f\'
            ';
            
            if(!is_null($schema)) {
                $query.='AND (pt_schema.nspname=\''.$this->db->escape($schema).'\' OR ct_schema.nspname=\''.$this->db->escape($schema).'\')'."\r\n";
            }
            
            if(!is_null($table)) {
                $query.='AND (pt.relname=\''.$this->db->escape($table).'\' OR ct.relname=\''.$this->db->escape($table).'\')'."\r\n";
            }
            
            foreach($this->db->query($query) as $k=>$v) {
                $pivot=null;
                if($v['pt_schema']==$schema && $v['pt_name']==$table) {
                    $pivot='ct';
                    $pivot2='pt';
                }elseif($v['ct_schema']==$schema && $v['ct_name']==$table) {
                    $pivot='pt';
                    $pivot2='ct';
                }else{
                    throw new Exception(__METHOD__.' - No se pudo determinar la dirección de la Foreign Key');
                }
                $aux[$v[$pivot.'_schema']][$v[$pivot.'_name']][]=array($v[$pivot2.'_column'],$v[$pivot.'_column']);
            }
            foreach($aux as $fk_schema=>$fk_tables) {
                foreach($fk_tables as $fk_table=>$fk_rels) {
                    $pks=$this->db->getPrimaryKeys($fk_schema,$fk_table);
                    $tpl=array(
                        'pks'=>$pks,
                        'schema'=>$fk_schema,
                        'table'=>$fk_table,
                        'rel'=>array()
                    );
                    foreach($fk_rels as $fk_rel) {
                        $tpl['rel'][]=$fk_rel;
                    }
                    $ret[]=$tpl;
                }
            }
            self::$_CACHE[$cache_key]=$ret;
            return $ret;
        }
    }
    
    
    
}