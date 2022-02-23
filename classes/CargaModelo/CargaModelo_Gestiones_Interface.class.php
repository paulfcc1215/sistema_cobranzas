<?php
interface CargaModelo_Gestiones_Interface {
	function processRecord(&$line);
	function pushFile($filename,$filepath);
	function getFiles();

	// Iterator
	function next();
	function current();
	function rewind();
	function key();
	function valid();
	
}