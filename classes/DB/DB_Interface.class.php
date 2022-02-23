<?php
interface DB_Interface {
    public function query($query,$throw_exception=false);
    public function escape($string);
    public function fetchOneRow($resource,$freeResult=false);
    public function fetchOne($resource,$freeResult=false);
    public function fetchAll($resource,$freeResult=true);
    public function startTransaction($throw_exception=false);
    public function commit($throw_exception=false);
    public function rollback($throw_exception=false);
    public function isInTransaction();
    public function numRows($resource);
    public function getColumns($schema_name,$table_name);
    public function prepare($stmt_name,$query);
    public function execute($stmt_name,$params);
    public function affectedRows($resource);
    public function getPrimaryKeys($schema,$table);
    public function getTables($schema);
    public function getNonNullableColumns($schema,$table);
    public function relations();
    public function getConnStringHash();
    public function getConnName();
    
}