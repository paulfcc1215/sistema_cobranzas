<?php
interface CargaModelo_Uploadable_Interface {
	/*
	function getOriginalFileName();
	function getOriginalFilePath();
	*/
	function processRecord(&$line);
	function pushFile($filename,$filepath);
	function getFiles();
    function getTipoCarga();
    function setTipoCarga($tipo);

	// Iterator
	function next();
	function current();
	function rewind();
	function key();
	function valid();
	
}