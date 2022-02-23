<?php
interface Reporte_Interface {
    /**
    * Devuelve un arreglo con todos los campos necesarios en la carga
    */
    public function getCamposRequeridos();
    
    /**
    * ejecuta el reporte
    * retorna la instruccion a ejecutar
    * puede ser una de las siguientes
    *        file - se envia los contenidos de result dentro de un zip via attachment
    *               result debe contener arreglo. El nombre de la llave es el nombre del archivo
    *               el contenido del item es el contenido del archivo
    *               en $additional_data podria existir una llave [filename] la cual se colocara en el
    *               header de content disposition.
    *  raw_output - se muestra en pantalla el contenido la variable $result.
    *               ignorando por completo la plantilla.
    *        flow - se mostrará el contenido de la variable $result dentro de la plantilla $_T['maincontent']
    */
    public function execute($_post,$_get,&$result,&$additional_data=array(),&$_T=array());
    
    /**
    * se ejecuta luego del execute
    * 
    * @param mixed $returnedByExecute Valor que retorno execute
    * @param mixed $_post Valor de $_POST
    * @param mixed $_get Valor de $_GET
    * @param mixed $result Valor en $result retornado por execute
    * @param mixed $additional_data Valor en $additional_data retornado por execute
    * @param mixed $_T Valor de variable TEMPLATE $_T
    */
    public function postExecute($returnedByExecute,$_post,$_get,$result,$additional_data,&$_T=array());
}