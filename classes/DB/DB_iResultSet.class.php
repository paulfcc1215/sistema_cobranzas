<?php
interface DB_iResultSet {
    public function numRows();
    public function fetchOne();
    public function fetchAll();
    public function affectedRows();
    public function seek($pos);
    public function getResource();
    public function getQuery();
    public function free();
}