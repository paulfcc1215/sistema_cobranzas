<?php
interface CargaModelo_Handler_Interface {
    public function __construct();
    public function getTipoBase();
    public function getDescripcion();
	
	public function execute($step,&$__data);
    
	public function getArchivoModelo($with_data=false);
}